<?php
declare(strict_types=1);
namespace App\Controller;

use App\Entity\User;
use App\Enum\EventNameEnum;
use Doctrine\ORM\EntityManager;
use Framework\Http\Objects\Request;
use Framework\Http\Objects\Response;
use Framework\Http\Validators\CSRFValidator;
use Framework\Http\ViewEngine\ViewEngineInterface;

class AuthenticationController extends CoreAbstractController
{
    protected CSRFValidator $CSRFValidator;
    public function __construct(ViewEngineInterface $viewEngine, EntityManager $entityManager)
    {
        $this->CSRFValidator = new CSRFValidator();
        parent::__construct($viewEngine, $entityManager);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function loginIndex(Request $request): Response
    {
        $error = $this->getFlash($request, "auth.error");
        $lastUsername = $request->session()->get("last_username");

        return $this->render("login", [
            'error' => $error,
            'last_username' => $lastUsername
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function login(Request $request): Response
    {
        if(!$this->CSRFValidator->handle($request)) {
            $this->setFlash($request, "auth.error", "Invalid CSRF token");
            return $this->redirect("/login");
        }

        $email = $request->getBody("email");
        $password = $request->getBody("password");

        if(!$email || !$password) {
            $this->setFlash($request, "auth.error", "Email and password are required");
            return $this->redirect("/login");
        }
        $request->session()->set("last_username", $email);

        $usersRepository = $this->entityManager->getRepository(User::class);
        $user = $usersRepository->findOneBy([
            "email" => $email
        ]);
        if(!$user) {
            $this->setFlash($request, "auth.error", "Invalid email or password");
            return $this->redirect("/login");
        }

        if(!password_verify($password, $user->getPassword())) {
            $this->auditLogService->log("users.auth.failure", [
                'user_agent' => $request->getHeader("User-Agent"),
                'ip' => $request->getUserIp()
            ], "Authorization failure for user {$user->getId()}", user: $user);

            $this->eventBusService->triggerEvent(
                EventNameEnum::USER_AUTH_FAILURE,
                [
                    'userId' => $user->getId(),
                    'userEmail' => $user->getEmail(),
                    'userAgent' => $request->getHeader("User-Agent"),
                    'ip' => $request->getUserIp()
                ]
            );

            $this->setFlash($request, "auth.error", "Invalid email or password");
            return $this->redirect("/login");
        }

        $this->eventBusService->triggerEvent(
            EventNameEnum::USER_AUTH_SUCCESS,
            [
                'userId' => $user->getId(),
                'userEmail' => $user->getEmail(),
                'userAgent' => $request->getHeader("User-Agent"),
                'ip' => $request->getUserIp()
            ]
        );

        $this->auditLogService->log("users.auth.success", [
            'user_agent' => $request->getHeader("User-Agent"),
            'ip' => $request->getUserIp()
        ], "Authorization success for user {$user->getId()}", user: $user);

        $request->session()->set("user_id", $user->getId());

        return $this->redirect("/app/");
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function logout(Request $request): Response
    {
        $request->session()->remove("user_id");
        $request->session()->destroy();
        return $this->redirect("/login");
    }
}