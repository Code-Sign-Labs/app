<?php

namespace App\Controller\App;

use App\Controller\CoreAbstractController;
use App\Entity\Webhook;
use App\Entity\WebhookDelivery;
use App\Enum\EventNameEnum;
use App\Enum\WebhookDeliveryStatusEnum;
use App\Enum\WebhookTypeEnum;
use App\Service\EventBusService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Framework\Http\Objects\Request;
use Framework\Http\Objects\Response;
use Framework\Http\Validators\CSRFValidator;
use Framework\Http\ViewEngine\ViewEngineInterface;
use Random\RandomException;
use Throwable;

class WebhookController extends CoreAbstractController
{
    protected CSRFValidator $CSRFValidator;
    protected EntityRepository $webhookRepository;
    public function __construct(ViewEngineInterface $viewEngine, EntityManager $entityManager)
    {
        $this->CSRFValidator = new CSRFValidator();
        $this->webhookRepository = $entityManager->getRepository(Webhook::class);
        parent::__construct($viewEngine, $entityManager);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $user = $request->getAttributes()['user'];
        $error = $this->getFlash($request, "webhooks.list.error");

        $currentPage = max(1, (int) $request->getQuery("page", 1));
        $perPage = 10;
        $totalWebhooks = $this->webhookRepository->count();
        $totalPages = max(1, (int) ceil($totalWebhooks / $perPage));
        $currentPage = min($currentPage, $totalPages);
        $offset = ($currentPage - 1) * $perPage;

        $webhooks = $this->webhookRepository->findBy([], orderBy: ['createdAt' => 'DESC'], limit: $perPage, offset: $offset);

        return $this->render("app/webhook/list.twig", [
            'user' => $user,
            'webhooks' => $webhooks,
            'pagination' => [
                'currentPage' => $currentPage,
                'totalPages' => $totalPages,
                'totalItems' => $totalWebhooks,
                'from' => $totalWebhooks === 0 ? 0 : $offset + 1,
                'to' => $totalWebhooks === 0 ? 0 : min($offset + $perPage, $totalWebhooks),
                'previousUrl' => $currentPage > 1 ? $this->buildPaginationUrl([], $currentPage - 1) : null,
                'nextUrl' => $currentPage < $totalPages ? $this->buildPaginationUrl([], $currentPage + 1) : null,
                'pages' => $this->buildPaginationPages([], $currentPage, $totalPages),
            ],
            'error' => $error,
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function indexCreate(Request $request): Response
    {
        $user = $request->getAttributes()['user'];
        $error = $this->getFlash($request, "webhook.create.error");

        return $this->render("app/webhook/create.twig", [
            'user' => $user,
            'events' => EventNameEnum::cases(),
            'webhook_types' => WebhookTypeEnum::cases(),
            'error' => $error,
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws RandomException
     */
    public function create(Request $request): Response
    {
        if(!$this->CSRFValidator->handle($request)) {
            $this->setFlash($request, "webbhook.create.error", "Invalid CSRF token");
            return $this->redirect("/app/webhooks-new");
        }

        $user = $request->getAttributes()['user'];

        $name = $request->getBody("name");
        $url = $request->getBody("url");
        $type = $request->getBody("type");

        if(!$name || !$url || !$type) {
            $this->setFlash($request, "webhook.create.error", "Invalid webhook request");
            return $this->redirect("/app/webhooks-new");
        }

        if(WebhookTypeEnum::tryFrom($type) === null) {
            $this->setFlash($request, "webhook.create.error", "Invalid webhook type");
            return $this->redirect("/app/webhooks-new");
        }

        $events = $request->getBody("events");
        foreach($events as $event) {
            if(EventNameEnum::tryFrom($event) === null) {
                $this->setFlash($request, "webhook.create.error", "Invalid webhook event " . $event);
                return $this->redirect("/app/webhooks-new");
            }
        }

        $customPayload = $request->getBody("customPayload");

        $headers = $request->getBody('headers');
        $formattedHeaders = [];
        if(gettype($headers) === "array") {
            foreach($headers as $header) {
                $formattedHeaders[] = $header[1];
            }
        }


        $webhook = new Webhook();
        $webhook->setName($name)
            ->setUrl($url)
            ->setEvents($events)
            ->setType(WebhookTypeEnum::tryFrom($type))
            ->setCustomPayload($customPayload ? json_decode($customPayload, true) : [])
            ->setHeaders($formattedHeaders)
            ->setSecret("whsec_" . bin2hex(random_bytes(32)));

        try {
            $this->entityManager->persist($webhook);
            $this->entityManager->flush();

            $this->auditLogService->log("webhook.create.success", [
                'user_agent' => $request->getHeader('User-Agent'),
                'ip' =>$request->getUserIp()
            ], "New webhook with id {$webhook->getId()} has been created", user: $user);

            $this->eventBusService->triggerEvent(
                EventNameEnum::WEBHOOK_CREATE_SUCCESS,
                [
                    'webhookId' => $webhook->getId(),
                    "webhookName" => $webhook->getName(),
                    "events" => implode(", ", $webhook->getEvents()),
                    'userAgent' => $request->getHeader('User-Agent'),
                    'ip' =>$request->getUserIp()
                ]
            );

            return $this->redirect("/app/webhooks");
        } catch(Throwable $e) {
            if($e instanceof ORMException) {
                $this->setFlash($request, "webhook.create.error", $e->getMessage());
                return $this->redirect("/app/webhooks-new");
            }
            $this->setFlash($request, "webhook.create.error", $e->getMessage());

            $this->auditLogService->log("webhook.create.failure", [
                'user_agent' => $request->getHeader('User-Agent'),
                'ip' =>$request->getUserIp()
            ], "New webhook could not be created!", user: $user);

            $this->eventBusService->triggerEvent(
                EventNameEnum::WEBHOOK_CREATE_FAILURE,
                [
                    'error' => $e->getMessage(),
                    'userAgent' => $request->getHeader('User-Agent'),
                    'ip' =>$request->getUserIp()
                ]
            );

            return $this->redirect("/app/webhooks-new");
        }
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function details(Request $request): Response
    {
        $webhookId = $request->getParams()['webhookId'];
        $user = $request->getAttributes()['user'];

        $webhook = $this->webhookRepository->find($webhookId);

        $webhookDeliveries = $webhook->getWebhookDeliveries();
        $successWebhooks = 0;
        $failedWebhooks = 0;

        $failed24h = 0;
        /**
         * @var WebhookDelivery $webhookDelivery
         */
        foreach($webhookDeliveries as $webhookDelivery) {
            if($webhookDelivery->getStatus() === WebhookDeliveryStatusEnum::success) {
                $successWebhooks++;
            } else {
                if($webhookDelivery->getDeliveryAt() && $webhookDelivery->getDeliveryAt() > (new \DateTime())->modify('-24 hours')) {
                    $failed24h++;
                }
                $failedWebhooks++;
            }
        }

        // Based on failed webhooks and success webhooks
        $success_rate = $successWebhooks + $failedWebhooks > 0 ? ($successWebhooks / ($successWebhooks + $failedWebhooks)) * 100 : 100;

        $webhookDeliveryRepository = $this->entityManager->getRepository(WebhookDelivery::class);

        return $this->render("app/webhook/details.twig", [
            'user' => $user,
            'webhook' => $webhook,
            'success_rate' => $success_rate,
            'fail_24h' => $failed24h,
            'webhookDeliveries' => $webhookDeliveryRepository->findBy([
                'webhook' => $webhook
            ], orderBy: ['createdAt' => 'DESC'], limit: 5)
        ]);
    }


    /**
     * @param Request $request
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function delete(Request $request): Response
    {
        $webhookId = $request->getParams()['webhookId'];
        $user = $request->getAttributes()['user'];

        $webhook = $this->webhookRepository->find($webhookId);
        if(!$webhook) {
            return $this->redirect("/app/webhooks");
        }

        $webhookDeliveries = $webhook->getWebhookDeliveries();
        try {
            foreach($webhookDeliveries as $webhookDelivery) {
                $this->entityManager->remove($webhookDelivery);
            }
            $this->entityManager->remove($webhook);
            $this->entityManager->flush();

            $this->auditLogService->log("webhook.delete.success", [
                'user_agent' => $request->getHeader('User-Agent'),
                'ip' =>$request->getUserIp()
            ], "Webhook with id {$webhookId} has been deleted", user: $user);

            $this->eventBusService->triggerEvent(
                EventNameEnum::WEBHOOK_DELETE_SUCCESS,
                [
                    'webhookId' => $webhook->getId(),
                    "webhookName" => $webhook->getName(),
                    "events" => implode(", ", $webhook->getEvents()),
                    'userAgent' => $request->getHeader('User-Agent'),
                    'ip' =>$request->getUserIp()
                ],
            );

            return $this->redirect("/app/webhooks");
        } catch(Throwable $e) {
            if($e instanceof ORMException) {
                $this->setFlash($request, "webhook.list.error", $e->getMessage());
                return $this->redirect("/app/webhooks");
            }

            $this->setFlash($request, "webhook.list.error", $e->getMessage());
            $this->auditLogService->log("webhook.delete.failure", [
                'user_agent' => $request->getHeader('User-Agent'),
                'ip' =>$request->getUserIp()
            ], "Webhook could not be deleted!", user: $user);

            $this->eventBusService->triggerEvent(
                EventNameEnum::WEBHOOK_DELETE_FAILURE,
                [
                    'error' => $e->getMessage(),
                    "webhookId" => $webhook->getId(),
                    "webhookName" => $webhook->getName(),
                    'userAgent' => $request->getHeader('User-Agent'),
                    'ip' =>$request->getUserIp()
                ]
            );

            return $this->redirect("/app/webhooks");
        }
    }
}