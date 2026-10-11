<?php
declare(strict_types=1);

namespace App\Controller\API\v2;

use App\Controller\API\CoreApiController;
use App\Entity\Customer;
use App\Entity\LicenseKey;
use App\Enum\LicenseKeyBillingTypeEnum;
use App\Enum\LicenseKeyStatusEnum;
use App\Service\MailingService;
use App\Service\StripeService;
use Carbon\Carbon;
use DateTime;
use Doctrine\ORM\EntityManager;
use Framework\Http\Objects\Request;
use Framework\Http\Objects\Response;
use Framework\Http\ViewEngine\ViewEngineInterface;
use Framework\Messenger\MessageDispatcher;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use Stripe\Webhook;

class StripeWebhookController extends CoreApiController
{
    protected StripeService $stripeService;
    protected MailingService $mailingService;
    public function __construct(ViewEngineInterface $viewEngine, EntityManager $entityManager, protected MessageDispatcher $dispatcher)
    {
        $this->stripeService = new StripeService();
        $this->mailingService = new MailingService($this->dispatcher);
        parent::__construct($viewEngine, $entityManager);
    }

    public function webhook(Request $request): Response
    {
        $payload = @file_get_contents('php://input');
        $event = "";

        try {
            $event = Event::constructFrom(
                json_decode($payload, true)
            );
        } catch (\UnexpectedValueException $e) {
            return $this->error(
                "INVALID_PAYLOAD",
                "Invalid payload",
                400
            );
        }

        try {
            $signatureHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'];
            $event = Webhook::constructEvent(
                $payload, $signatureHeader, $_ENV['STRIPE_WEBHOOK_SECRET'],
            );
        } catch(SignatureVerificationException $e) {
            return $this->error(
                "INVALID_SIGNATURE",
                "Invalid signature",
                400
            );
        }

        switch($event->type) {
            case 'setup_intent.succeeded':
                $paymentMethod = $event->data->object;

                $customer = $paymentMethod->customer;
                $customer = $this->entityManager->getRepository(Customer::class)->findOneBy([
                    'stripeCustomerId' => $customer
                ]);

                $this->mailingService->sendMail("email/add_payment_method_confirm", "Payment Method Attached", $customer->getEmail(), [
                    'customer' => [
                        'label' => $customer->getLabel(),
                    ],
                ]);

                break;
            case 'invoice.payment_succeeded':
                $session = $event->data->object;

                if(!$session->subscription) {
                    return $this->error(
                        "MISSING_SUBSCRIPTION_ID",
                        "Missing subscription ID in the session object.",
                        400
                    );
                }

                $subscription = $this->stripeService->getClient()->subscriptions->retrieve(
                    $session->subscription,
                    []
                );

                $subscriptionId = $subscription->id;
                if(!$subscriptionId) {
                    return $this->error(
                        "MISSING_SUBSCRIPTION_ID",
                        "Missing subscription ID in the session object.",
                        400
                    );
                }

                $licenses = $this->entityManager->getRepository(LicenseKey::class)->findBy([
                    'stripeSubscriptionId' => $subscriptionId
                ]);

                /**
                 * @var LicenseKey $license
                 */
                foreach ($licenses as $license) {
                    if($license->getStatus() === LicenseKeyStatusEnum::active) continue;
                    $licenseBillingType = $license->getBillingType();
                    $validUntil = (new DateTime())->modify("+1 " . $licenseBillingType->value);

                    $license->setStatus(LicenseKeyStatusEnum::active);
                    $license->setExpiresAt($validUntil);
                    $this->entityManager->persist($license);
                }


                $this->mailingService->sendMail("email/payment_confirmation", "Payment Confirmation", $license->getCustomer()->getEmail(), [
                    'subscription_id' => $subscriptionId,
                    'customer' => [
                        'label' => $license->getCustomer()->getLabel(),
                    ],
                    'product' => [
                        'name' => $license->getProduct()->getName(),
                    ],
                    'valid_until' => $validUntil->format('Y-m-d H:i:s'),
                ]);

                $this->entityManager->flush();
                break;
            default:
                return $this->error(
                    "UNHANDLED_EVENT",
                    "Unhandled event type: {$event->type}",
                    400
                );
        }

        return $this->success([
            'message' => 'Webhook received and processed successfully.',
            'event_type' => $event->type
        ]);
    }
}