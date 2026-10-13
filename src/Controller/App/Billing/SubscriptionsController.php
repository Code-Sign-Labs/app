<?php
declare(strict_types=1);

namespace App\Controller\App\Billing;

use App\Controller\CoreAbstractController;
use App\Entity\LicenseKey;
use App\Enum\LicenseKeyStatusEnum;
use App\Service\StripeService;
use DateTime;
use Doctrine\ORM\EntityManager;
use Framework\Http\Objects\Request;
use Framework\Http\Objects\Response;
use Framework\Http\Validators\CSRFValidator;
use Framework\Http\ViewEngine\ViewEngineInterface;
use Framework\Messenger\MessageDispatcher;
use Throwable;
use Traversable;

class SubscriptionsController extends CoreAbstractController
{
    protected StripeService $stripeService;
    protected CSRFValidator $CSRFValidator;

    public function __construct(ViewEngineInterface $viewEngine, EntityManager $entityManager, protected MessageDispatcher $dispatcher)
    {
        $this->stripeService = new StripeService();
        $this->CSRFValidator = new CSRFValidator();
        parent::__construct($viewEngine, $entityManager);
    }

    public function list(Request $request): Response
    {
        $user = $request->getAttributes()['user'];
        $error = $this->getFlash($request, 'subscriptions.list.error');
        $success = $this->getFlash($request, 'subscriptions.list.success');

        try {
            $subscriptions = $this->stripeService->getClient()->subscriptions->all([
                'limit' => 100,
                'status' => 'all',
                'expand' => ['data.customer', 'data.items.data.price', 'data.latest_invoice'],
            ]);
            $subscriptions = $this->stripeCollectionToArray($subscriptions);
        } catch (Throwable $exception) {
            $subscriptions = [];
            $error = 'Failed to load subscriptions from Stripe: ' . $exception->getMessage();
        }

        return $this->render('app/billing/subscriptions/list.twig', [
            'user' => $user,
            'error' => $error,
            'success' => $success,
            'subscriptions' => array_map(fn ($subscription) => $this->mapSubscription($subscription), $subscriptions),
        ]);
    }

    public function details(Request $request): Response
    {
        $user = $request->getAttributes()['user'];
        $subscriptionId = $request->getParams()['subscriptionId'];
        $error = $this->getFlash($request, 'subscriptions.details.error');
        $success = $this->getFlash($request, 'subscriptions.details.success');

        try {
            $subscription = $this->stripeService->getClient()->subscriptions->retrieve($subscriptionId, [
                'expand' => ['customer', 'items.data.price.product', 'latest_invoice'],
            ]);
            if (!$subscription) {
                return $this->redirect('/app/billing/subscriptions');
            }

            $invoices = $this->stripeService->getClient()->invoices->all([
                'subscription' => $subscriptionId,
                'limit' => 20,
                'expand' => ['data.customer', 'data.subscription', 'data.charge', 'data.payment_intent'],
            ]);
            $invoices = array_map(fn ($invoice) => $this->mapInvoice($invoice), $this->stripeCollectionToArray($invoices));

            $localLicenses = $this->entityManager->getRepository(LicenseKey::class)->findBy([
                'stripeSubscriptionId' => $subscriptionId,
            ]);
        } catch (Throwable $exception) {
            $this->setFlash($request, 'subscriptions.list.error', 'Failed to load subscription: ' . $exception->getMessage());
            return $this->redirect('/app/billing/subscriptions');
        }

        return $this->render('app/billing/subscriptions/details.twig', [
            'user' => $user,
            'error' => $error,
            'success' => $success,
            'subscription' => $this->mapSubscription($subscription),
            'invoices' => $invoices,
            'localLicenses' => $localLicenses,
        ]);
    }

    public function cancel(Request $request): Response
    {
        $subscriptionId = $request->getParams()['subscriptionId'];

        if (!$this->CSRFValidator->handle($request)) {
            $this->setFlash($request, 'subscriptions.details.error', 'Invalid CSRF token.');
            return $this->redirect("/app/billing/subscriptions/{$subscriptionId}");
        }

        $user = $request->getAttributes()['user'];

        try {
            $subscription = $this->stripeService->getClient()->subscriptions->cancel($subscriptionId, []);
            if (!$subscription) {
                $this->setFlash($request, 'subscriptions.details.error', 'Subscription not found in Stripe.');
                return $this->redirect('/app/billing/subscriptions');
            }

            $localLicenses = $this->entityManager->getRepository(LicenseKey::class)->findBy([
                'stripeSubscriptionId' => $subscriptionId,
            ]);
            foreach ($localLicenses as $license) {
                if ($license->getStatus() !== LicenseKeyStatusEnum::revoked) {
                    $license->setStatus(LicenseKeyStatusEnum::revoked)
                        ->setExpiresAt(new DateTime());
                    $this->entityManager->persist($license);
                }
            }
            $this->entityManager->flush();

            $this->auditLogService->log('billing.subscription.cancel.success', [
                'user_agent' => $request->getHeader('User-Agent'),
                'ip' => $request->getUserIp(),
            ], "Subscription {$subscriptionId} has been cancelled.", $user);

            $this->setFlash($request, 'subscriptions.details.success', "Subscription {$subscriptionId} cancelled successfully.");
            return $this->redirect("/app/billing/subscriptions/{$subscriptionId}");
        } catch (Throwable $exception) {
            $this->auditLogService->log('billing.subscription.cancel.failure', [
                'user_agent' => $request->getHeader('User-Agent'),
                'ip' => $request->getUserIp(),
            ], "Subscription {$subscriptionId} could not be cancelled.", $user);

            $this->setFlash($request, 'subscriptions.details.error', 'Failed to cancel subscription: ' . $exception->getMessage());
            return $this->redirect("/app/billing/subscriptions/{$subscriptionId}");
        }
    }

    private function mapSubscription(object $subscription): array
    {
        $items = [];
        $subscriptionItems = $subscription->items?->data ?? [];
        foreach ($subscriptionItems as $item) {
            $items[] = $this->mapSubscriptionItem($item);
        }

        $firstItem = $items[0] ?? null;

        return [
            'id' => $subscription->id ?? null,
            'status' => $subscription->status ?? null,
            'cancelAtPeriodEnd' => (bool)($subscription->cancel_at_period_end ?? false),
            // Changed to pull from the item level
            'currentPeriodStart' => $firstItem['currentPeriodStart'] ?? null,
            'currentPeriodEnd' => $firstItem['currentPeriodEnd'] ?? null,
            'canceledAt' => $this->formatStripeTimestamp($subscription->canceled_at ?? null),
            'trialEnd' => $this->formatStripeTimestamp($subscription->trial_end ?? null),
            'customer' => $this->mapCustomer($subscription->customer ?? null),
            'latestInvoiceId' => $this->stripeObjectId($subscription->latest_invoice ?? null),
            'items' => $items,
            'productName' => $firstItem['productName'] ?? null,
            'priceAmount' => $firstItem['unitAmount'] ?? null,
            'priceAmountFormatted' => isset($firstItem['unitAmount']) ? number_format(((int) $firstItem['unitAmount']) / 100, 2, '.', '') : null,
            'currency' => $firstItem['currency'] ?? null,
            'interval' => $firstItem['interval'] ?? null,
        ];
    }

    private function mapSubscriptionItem(object $item): array
    {
        $price = $item->price ?? null;
        $product = $price?->product ?? null;

        return [
            'id' => $item->id ?? null,
            'quantity' => $item->quantity ?? 1,
            'unitAmount' => $price?->unit_amount ?? null,
            'currency' => $price?->currency ?? null,
            'interval' => $price?->recurring?->interval ?? null,
            'priceId' => $price?->id ?? null,
            'productId' => $this->stripeObjectId($product),
            'productName' => $this->stripeObjectName($product),
            'currentPeriodStart' => $this->formatStripeTimestamp($item->current_period_start ?? null),
            'currentPeriodEnd' => $this->formatStripeTimestamp($item->current_period_end ?? null),
        ];
    }

    private function mapInvoice(object $invoice): array
    {
        $charge = $invoice->charge ?? null;
        $paymentIntent = $invoice->payment_intent ?? null;
        $subscription = $invoice->subscription ?? null;
        $customer = $invoice->customer ?? null;

        $amountPaid = (int)($invoice->amount_paid ?? 0);
        $amountRefunded = (int)($charge->amount_refunded ?? 0);

        return [
            'id' => $invoice->id ?? null,
            'number' => $invoice->number ?? null,
            'status' => $invoice->status ?? null,
            'customer' => $this->mapCustomer($customer),
            'subscriptionId' => $this->stripeObjectId($subscription),
            'paymentIntentId' => $this->stripeObjectId($paymentIntent),
            'chargeId' => $this->stripeObjectId($charge),
            'amountPaid' => $amountPaid,
            'amountPaidFormatted' => number_format($amountPaid / 100, 2, '.', ''),
            'amountRefunded' => $amountRefunded,
            'amountRefundedFormatted' => number_format($amountRefunded / 100, 2, '.', ''),
            'currency' => strtoupper((string)($invoice->currency ?? '')),
            'createdAt' => $this->formatStripeTimestamp($invoice->created ?? null),
            'paidAt' => $this->formatStripeTimestamp($invoice->status_transitions?->paid_at ?? null),
            'refunded' => $amountRefunded > 0,
            'canRefund' => $amountPaid > 0 && $amountRefunded < $amountPaid,
        ];
    }

    private function mapCustomer(mixed $customer): array
    {
        if (is_object($customer)) {
            return [
                'id' => $customer->id ?? null,
                'name' => $customer->name ?? $customer->email ?? $customer->id ?? 'Unknown customer',
                'email' => $customer->email ?? null,
            ];
        }

        return [
            'id' => is_string($customer) ? $customer : null,
            'name' => is_string($customer) ? $customer : 'Unknown customer',
            'email' => null,
        ];
    }

    private function stripeObjectId(mixed $value): ?string
    {
        if (is_object($value) && isset($value->id)) {
            return (string) $value->id;
        }

        return is_string($value) ? $value : null;
    }

    private function stripeObjectName(mixed $value): ?string
    {
        if (is_object($value)) {
            return $value->name ?? $value->description ?? $value->id ?? null;
        }

        return is_string($value) ? $value : null;
    }

    private function formatStripeTimestamp(mixed $timestamp): ?string
    {
        if (!is_numeric($timestamp) || (int) $timestamp <= 0) {
            return null;
        }

        return (new DateTime())
            ->setTimestamp((int) $timestamp)
            ->format('Y-m-d H:i:s');
    }

    private function stripeCollectionToArray(mixed $collection): array
    {
        if (is_array($collection)) {
            return $collection;
        }

        if ($collection instanceof Traversable) {
            return iterator_to_array($collection, false);
        }

        if (is_object($collection) && isset($collection->data) && is_array($collection->data)) {
            return $collection->data;
        }

        return [];
    }
}


