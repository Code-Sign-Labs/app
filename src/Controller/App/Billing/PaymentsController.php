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

class PaymentsController extends CoreAbstractController
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
        $error = $this->getFlash($request, 'payments.list.error');
        $success = $this->getFlash($request, 'payments.list.success');

        try {
            $invoices = $this->stripeService->getClient()->invoices->all([
                'limit' => 100,
                'status' => 'paid',
                'expand' => ['data.customer', 'data.subscription', 'data.charge', 'data.payment_intent'],
            ]);
            $invoices = $this->stripeCollectionToArray($invoices);
        } catch (Throwable $exception) {
            $invoices = [];
            $error = 'Failed to load payments from Stripe: ' . $exception->getMessage();
        }

        return $this->render('app/billing/payments/list.twig', [
            'user' => $user,
            'error' => $error,
            'success' => $success,
            'payments' => array_map(fn ($invoice) => $this->mapInvoice($invoice), $invoices),
        ]);
    }

    public function refund(Request $request): Response
    {
        $invoiceId = $request->getParams()['invoiceId'];

        if (!$this->CSRFValidator->handle($request)) {
            $this->setFlash($request, 'payments.list.error', 'Invalid CSRF token.');
            return $this->redirect('/app/billing/payments');
        }

        $user = $request->getAttributes()['user'];

        try {
            $invoice = $this->stripeService->getClient()->invoices->retrieve($invoiceId, [
                'expand' => ['customer', 'subscription', 'charge', 'payment_intent'],
            ]);
            if (!$invoice) {
                $this->setFlash($request, 'payments.list.error', 'Payment not found in Stripe.');
                return $this->redirect('/app/billing/payments');
            }

            $paymentIntentId = $this->stripeObjectId($invoice->payment_intent ?? null);
            $chargeId = $this->stripeObjectId($invoice->charge ?? null);

            if (!$paymentIntentId && !$chargeId) {
                $this->setFlash($request, 'payments.list.error', 'This payment cannot be refunded because Stripe did not return a charge or payment intent.');
                return $this->redirect('/app/billing/payments');
            }

            $refundParams = [];
            if ($paymentIntentId) {
                $refundParams['payment_intent'] = $paymentIntentId;
            } else {
                $refundParams['charge'] = $chargeId;
            }

            $refund = $this->stripeService->getClient()->refunds->create($refundParams);

            $subscriptionId = $this->stripeObjectId($invoice->subscription ?? null);
            if ($subscriptionId) {
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
            }

            $this->auditLogService->log('billing.payment.refund.success', [
                'user_agent' => $request->getHeader('User-Agent'),
                'ip' => $request->getUserIp(),
            ], sprintf('Payment %s has been refunded in Stripe.', $invoiceId), $user);

            $this->setFlash($request, 'payments.list.success', sprintf('Payment %s refunded successfully%s.', $invoiceId, isset($refund->id) ? ' (refund id: ' . $refund->id . ')' : ''));
            return $this->redirect('/app/billing/payments');
        } catch (Throwable $exception) {
            $this->auditLogService->log('billing.payment.refund.failure', [
                'user_agent' => $request->getHeader('User-Agent'),
                'ip' => $request->getUserIp(),
            ], sprintf('Payment %s could not be refunded.', $invoiceId), $user);

            $this->setFlash($request, 'payments.list.error', 'Failed to refund payment: ' . $exception->getMessage());
            return $this->redirect('/app/billing/payments');
        }
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

