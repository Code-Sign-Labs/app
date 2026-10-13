<?php
declare(strict_types=1);

namespace App\Controller\App\Billing;

use App\Controller\CoreAbstractController;
use App\Entity\Customer;
use App\Entity\LicenseKey;
use App\Entity\Product;
use App\Enum\EventNameEnum;
use App\Enum\LicenseKeyStatusEnum;
use App\Service\ConfigService;
use App\Service\MailingService;
use App\Service\StripeService;
use Doctrine\ORM\EntityManager;
use Framework\Http\Objects\Request;
use Framework\Http\Objects\Response;
use Framework\Http\Validators\CSRFValidator;
use Framework\Http\ViewEngine\ViewEngineInterface;
use Framework\Messenger\MessageDispatcher;
use Stripe\Collection;
use Stripe\Customer as StripeCustomer;
use Throwable;

class CustomersController extends CoreAbstractController
{
    protected StripeService $stripeService;
    protected CSRFValidator $CSRFValidator;
    protected MailingService $mailingService;
    protected ConfigService $configService;
    public function __construct(ViewEngineInterface $viewEngine, EntityManager $entityManager, protected MessageDispatcher $dispatcher)
    {
        $this->configService = new ConfigService($entityManager);
        $this->mailingService = new MailingService($this->dispatcher);
        $this->CSRFValidator = new CSRFValidator();
        $this->stripeService = new StripeService();
        parent::__construct($viewEngine, $entityManager);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function list(Request $request): Response
    {
        $user = $request->getAttributes()['user'];
        $error = $this->getFlash($request, "customers.list.error");
        $success = $this->getFlash($request, "customers.list.success");

        $customers = $this->entityManager->getRepository(Customer::class)->findAll();

        return $this->render('app/billing/customers/list.twig', [
            'user' => $user,
            'error' => $error,
            'success' => $success,
            'customers' => $customers
        ]);
    }

    /**
     * @param Customer $customer
     * @return ?Collection
     */
    protected function getSubscriptions(Customer $customer): ?Collection
    {
        try {
            return $this->stripeService->getClient()
                ->subscriptions->all([
                    'customer' => $customer->getStripeCustomerId(),
                    'limit' => 100
                ]);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param string $id
     * @return Product|null
     */
    protected function getProductById(string $id): ?Product
    {
        return $this->entityManager->getRepository(Product::class)->find($id);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function details(Request $request): Response
    {
        $user = $request->getAttributes()['user'];
        $customerId = $request->getParams()['id'];
        $error = $this->getFlash($request, "customers.details.error");
        $success = $this->getFlash($request, "customers.details.success");

        $customer = $this->entityManager->getRepository(Customer::class)->find($customerId);

        if (!$customer) {
            return $this->redirect("/app/billing/customers");
        }

        $products = [];
        foreach($this->entityManager->getRepository(Product::class)->findAll() as $product) {
            $products[$product->getStripeProductId()] = $product;
        }

        return $this->render('app/billing/customers/details.twig', [
            'user' => $user,
            'error' => $error,
            'success' => $success,
            'customer' => $customer,
            'subscriptions' => $this->getSubscriptions($customer),
            'products' => $products,
            'licenses' => $this->entityManager->getRepository(LicenseKey::class)->findBy(['customer' => $customer])
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function indexCreate(Request $request): Response
    {
        $user = $request->getAttributes()['user'];
        $error = $this->getFlash($request, "customers.new.error");
        $success = $this->getFlash($request, "customers.new.success");

        return $this->render('app/billing/customers/create.twig', [
            'user' => $user,
            'error' => $error,
            'success' => $success
        ]);
    }

    public function create(Request $request): Response
    {
        if(!$this->CSRFValidator->handle($request)) {
            return $this->redirect("/app/billing/customers-new");
        }
        $user = $request->getAttributes()['user'];

        $label = $request->getBody('label');
        $email = $request->getBody('email');
        $address = $request->getBody('address');
        $country = $request->getBody('country');
        $postalCode = $request->getBody('postal_code');
        $currency =  $request->getBody('currency');
        $city = $request->getBody('city');

        if(!$label || !$email || !$address || !$country || !$postalCode || !$currency || !$city) {
            $this->setFlash($request, "customers.new.error", "All fields are required.");
            return $this->redirect("/app/billing/customers-new");
        }

        $vatNumber = $request->getBody('vat_number');
        $notes = $request->getBody('notes');

        // Create Stripe customer
        try {
            $stripeCustomer = $this->stripeService->getClient()->customers->create([
                'name' => $label,
                'email' => $email,
                'tax' => [
                    'ip_address' => $request->getUserIp(),
                ],
                'address' => [
                    'line1' => $address,
                    'country' => $country,
                    'city' => $city,
                    'postal_code' => $postalCode
                ]
            ]);

            if($vatNumber) {
                $taxId = $this->stripeService->getClient()->customers->createTaxId($stripeCustomer->id, [
                    'type' => $_ENV["STRIPE_TAX_ID_TYPE"],
                    'value' => $vatNumber
                ]);
            }

            $portalSession = $this->stripeService->getClient()->billingPortal->sessions->create([
                'customer' => $stripeCustomer->id,
                'flow_data' => [
                    'type' => 'payment_method_update',
                    'after_completion' => [
                        'type' => 'hosted_confirmation',
                        'hosted_confirmation' => [
                            'custom_message' => 'Your payment method has been updated successfully.'
                        ]
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            $this->setFlash($request, "customers.new.error", "Failed to create Stripe customer: " . $e->getMessage());
            return $this->redirect("/app/billing/customers-new");
        }

        // Create local customer entity
        $customer = new Customer();
        $customer->setLabel($label)
            ->setEmail($email)
            ->setaddress($address)
            ->setCountry($country)
            ->setPostalCode($postalCode)
            ->setCurrency($currency)
            ->setNotes($notes)
            ->setVatNumber($vatNumber)
            ->setCity($city)
            ->setStripeCustomerId($stripeCustomer->id);

        try {
            $this->entityManager->persist($customer);
            $this->entityManager->flush();

            $this->eventBusService->triggerEvent(EventNameEnum::CUSTOMER_CREATE_SUCCESS, [
                "author" => $user->getEmail(),
                "ip" => $request->getUserIp(),
                "userAgent" => $request->getHeader("User-Agent"),
            ]);

            $this->auditLogService->log("customer.create.success", [
                'user_agent' => $request->getHeader("User-Agent"),
                'ip' => $request->getUserIp()
            ], "Customer created successfully for customer with id {$customer->getId()}", $user);

            $config = $this->configService->getConfig();

            $this->mailingService->sendMail(
                "email/customer_created",
                "Welcome to ". $config->getInstanceName(),
                $email,
                [
                    'customer' => [
                        'label' => $customer->getLabel(),
                    ],
                    'app_name' => $config->getInstanceName(),
                    'portal_url' => $portalSession->url
                ]
            );
        } catch (\Exception $e) {
            $this->eventBusService->triggerEvent(EventNameEnum::CUSTOMER_CREATE_FAILURE, [
                "author" => $user->getEmail(),
                "ip" => $request->getUserIp(),
                "userAgent" => $request->getHeader("User-Agent"),
            ]);

            $this->auditLogService->log("customer.create.failure", [
                'user_agent' => $request->getHeader("User-Agent"),
                'ip' => $request->getUserIp()
            ], "Customer creation failed for customer with id {$customer->getId()}", $user);

            try {
                $this->stripeService->getClient()->customers->delete($stripeCustomer->id);
            } catch (\Exception $ex) {
            }
            $this->setFlash($request, "customers.new.error", "Failed to save customer: " . $e->getMessage());
            return $this->redirect("/app/billing/customers-new");
        }

        $this->setFlash($request, "customers.new.success", "Customer created successfully.");
        return $this->redirect("/app/billing/customers/{$customer->getId()}");
    }

    public function indexEdit(Request $request): Response
    {
        $user = $request->getAttributes()['user'];
        $customerId = $request->getParams()['id'];
        $error = $this->getFlash($request, "customers.edit.error");

        $customer = $this->entityManager->getRepository(Customer::class)->find($customerId);
        if (!$customer) {
            return $this->redirect("/app/billing/customers");
        }

        return $this->render('app/billing/customers/edit.twig', [
            'user' => $user,
            'error' => $error,
            'customer' => $customer,
        ]);
    }

    public function edit(Request $request): Response
    {
        $customerId = $request->getParams()['id'];
        $customer = $this->entityManager->getRepository(Customer::class)->find($customerId);
        if (!$customer) {
            return $this->redirect("/app/billing/customers");
        }

        if (!$this->CSRFValidator->handle($request)) {
            $this->setFlash($request, "customers.edit.error", "Invalid CSRF token.");
            return $this->redirect("/app/billing/customers/{$customerId}/edit");
        }

        $user = $request->getAttributes()['user'];

        $label = $request->getBody('label');
        $email = $request->getBody('email');
        $address = $request->getBody('address');
        $country = $request->getBody('country');
        $postalCode = $request->getBody('postal_code');
        $currency = $request->getBody('currency');
        $city = $request->getBody('city');

        if(!$label || !$email || !$address || !$country || !$postalCode || !$currency || !$city) {
            $this->setFlash($request, "customers.edit.error", "All fields are required.");
            return $this->redirect("/app/billing/customers/{$customerId}/edit");
        }

        $vatNumber = $request->getBody('vat_number');
        $notes = $request->getBody('notes');

        try {
            $this->stripeService->getClient()->customers->update($customer->getStripeCustomerId(), [
                'name' => $label,
                'email' => $email,
                'address' => [
                    'line1' => $address,
                    'country' => $country,
                    'city' => $city,
                    'postal_code' => $postalCode
                ]
            ]);

            $customer->setLabel($label)
                ->setEmail($email)
                ->setAddress($address)
                ->setCountry($country)
                ->setPostalCode($postalCode)
                ->setCurrency($currency)
                ->setNotes($notes)
                ->setVatNumber($vatNumber)
                ->setCity($city);

            $this->entityManager->flush();

            $this->auditLogService->log("customer.edit.success", [
                'user_agent' => $request->getHeader("User-Agent"),
                'ip' => $request->getUserIp()
            ], "Customer with id {$customer->getId()} has been updated.", $user);

            $this->eventBusService->triggerEvent(EventNameEnum::CUSTOMER_EDIT_SUCCESS, [
                "author" => $user->getEmail(),
                "ip" => $request->getUserIp(),
                "userAgent" => $request->getHeader("User-Agent"),
                "customerId" => $customer->getId(),
                "customerLabel" => $customer->getLabel(),
            ]);

            return $this->redirect("/app/billing/customers/{$customer->getId()}");
        } catch (Throwable $e) {
            $this->auditLogService->log("customer.edit.failure", [
                'user_agent' => $request->getHeader("User-Agent"),
                'ip' => $request->getUserIp()
            ], "Customer with id {$customer->getId()} could not be updated.", $user);

            $this->eventBusService->triggerEvent(EventNameEnum::CUSTOMER_EDIT_FAILURE, [
                "author" => $user->getEmail(),
                "ip" => $request->getUserIp(),
                "userAgent" => $request->getHeader("User-Agent"),
                "customerId" => $customer->getId(),
                "customerLabel" => $customer->getLabel(),
            ]);

            $this->setFlash($request, "customers.edit.error", "Something went wrong: " . $e->getMessage());
            return $this->redirect("/app/billing/customers/{$customerId}/edit");
        }
    }

    public function delete(Request $request): Response
    {
        $customerId = $request->getParams()['id'];
        $user = $request->getAttributes()['user'];

        $customer = $this->entityManager->getRepository(Customer::class)->find($customerId);
        if (!$customer) {
            return $this->redirect("/app/billing/customers");
        }

        try {
            if ($customer->getStripeCustomerId()) {
                $this->stripeService->getClient()->customers->delete($customer->getStripeCustomerId());
            }

            $licenseKeys = $this->entityManager->getRepository(LicenseKey::class)->findBy(['customer' => $customer]);
            foreach ($licenseKeys as $licenseKey) {
                $licenseKey->setStatus(LicenseKeyStatusEnum::revoked);
                $this->entityManager->persist($licenseKey);
            }

            $this->entityManager->remove($customer);
            $this->entityManager->flush();

            $this->auditLogService->log("customer.delete.success", [
                'user_agent' => $request->getHeader("User-Agent"),
                'ip' => $request->getUserIp()
            ], "Customer with id {$customer->getId()} has been deleted.", $user);

            $this->eventBusService->triggerEvent(EventNameEnum::CUSTOMER_DELETE_SUCCESS, [
                "author" => $user->getEmail(),
                "ip" => $request->getUserIp(),
                "userAgent" => $request->getHeader("User-Agent"),
                "customerId" => $customer->getId(),
                "customerLabel" => $customer->getLabel(),
            ]);

            $this->setFlash($request, "customers.list.success", "Customer deleted successfully.");
            return $this->redirect("/app/billing/customers");
        } catch (Throwable $e) {
            $this->auditLogService->log("customer.delete.failure", [
                'user_agent' => $request->getHeader("User-Agent"),
                'ip' => $request->getUserIp()
            ], "Customer with id {$customer->getId()} could not be deleted.", $user);

            $this->eventBusService->triggerEvent(EventNameEnum::CUSTOMER_DELETE_FAILURE, [
                "author" => $user->getEmail(),
                "ip" => $request->getUserIp(),
                "userAgent" => $request->getHeader("User-Agent"),
                "customerId" => $customer->getId(),
                "customerLabel" => $customer->getLabel(),
            ]);

            $this->setFlash($request, "customers.details.error", "Something went wrong: " . $e->getMessage());
            return $this->redirect("/app/billing/customers/{$customerId}");
        }
    }
}