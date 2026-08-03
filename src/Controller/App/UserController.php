<?php

namespace App\Controller\App;

use App\Controller\CoreAbstractController;
use App\Entity\User;
use App\Service\ConfigService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Framework\Http\Objects\Request;
use Framework\Http\Objects\Response;
use Framework\Http\Validators\CSRFValidator;
use Framework\Http\ViewEngine\ViewEngineInterface;
use Throwable;

class UserController extends CoreAbstractController
{
    protected CSRFValidator $CSRFValidator;
    protected ConfigService $configService;

    public function __construct(ViewEngineInterface $viewEngine, EntityManager $entityManager)
    {
        $this->CSRFValidator = new CSRFValidator();
        $this->configService = new ConfigService($entityManager);
        parent::__construct($viewEngine, $entityManager);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function list(Request $request): Response
    {
        $user = $request->getAttributes()['user'];
        $usersRepository = $this->entityManager->getRepository(User::class);
        $users = $usersRepository->findAll();
        $success = $this->getFlash($request, "user.list.success");
        $error = $this->getFlash($request, "user.list.error");

        return $this->render("app/user/list.twig", [
            'user' => $user,
            'users' => $users,
            'success' => $success,
            'error' => $error
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function details(Request $request): Response
    {
        $user = $request->getAttributes()['user'];
        $userId = $request->getParams()['userId'];
        $error = $this->getFlash($request, "user.details.error");
        $success = $this->getFlash($request, "user.details.success");

        $usersRepository = $this->entityManager->getRepository(User::class);
        $userTarget = $usersRepository->find($userId);
        if(!$userTarget) return $this->redirect("/app/users");

        $logs = $userTarget->getAuditLogs()->toArray();
        usort($logs, function ($a, $b) {
            return $b->getCreatedAt()->getTimestamp() - $a->getCreatedAt()->getTimestamp();
        });

        return $this->render("app/user/details.twig", [
            'user' => $user,
            'userTarget' => $userTarget,
            'logs' => $logs,
            'error' => $error,
            'success' => $success
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
        $user = $request->getAttributes()['user'];
        $userId = $request->getParams()['userId'];

        if($userId == $user->getId()) {
            $this->setFlash($request, "user.details.error", "You can't delete yourself.");
            return $this->redirect("/app/users/" . $userId);
        }

        $usersRepository = $this->entityManager->getRepository(User::class);
        $userTarget = $usersRepository->find($userId);

        if(!$userTarget) return $this->redirect("/app/users");

        try {
            $this->entityManager->remove($userTarget);
            $this->entityManager->flush();

            $this->setFlash($request, "user.list.success", "User deleted.");

            $this->auditLogService->log("users.delete.success", [
                'user_agent' => $request->getHeader("User-Agent"),
                'ip' => $request->getUserIp()
            ], "User with id {$userTarget->getId()} has been deleted.", $user);
        } catch (Throwable $e) {
            $this->setFlash($request, "user.list.error", "Something went wrong: " . $e->getMessage());

            $this->auditLogService->log("users.delete.failure", [
                'user_agent' => $request->getHeader("User-Agent"),
                'ip' => $request->getUserIp()
            ], "User with id {$user->getId()} could not be deleted.", $user);
        }

        return $this->redirect("/app/users");
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function editIndex(Request $request): Response
    {
        $user = $request->getAttributes()['user'];
        $userId = $request->getParams()['userId'];

        $usersRepository = $this->entityManager->getRepository(User::class);
        $userTarget = $usersRepository->find($userId);

        if(!$userTarget) return $this->redirect("/app/users");

        $error = $this->getFlash($request, "user.edit.error");

        return $this->render("app/user/edit.twig", [
            'user' => $user,
            'userTarget' => $userTarget,
            'config' => $this->configService->getConfig(),
            'error' => $error
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function edit(Request $request): Response
    {
        $user = $request->getAttributes()['user'];
        $userId = $request->getParams()['userId'];

        $usersRepository = $this->entityManager->getRepository(User::class);
        $userTarget = $usersRepository->find($userId);

        if(!$userTarget) return $this->redirect("/app/users");

        if(!$this->CSRFValidator->handle($request)) {
            $this->setFlash($request, "user.edit.error", "Invalid CSRF token.");
            return $this->redirect("/app/users/" . $user->getId() . "/edit");
        }

        $name = $request->getBody('name');
        $email = $request->getBody('email');
        if(!$name || !$email) {
            $this->setFlash($request, "user.edit.error", "Please enter a name and email.");
            return $this->redirect("/app/users/" . $user->getId() . "/edit");
        }

        if($userTarget->getEmail() !== $email) {
            $userSomeone = $usersRepository->findOneBy([
                'email' => $email
            ]);
            if($userSomeone) {
                $this->setFlash($request, "user.edit.error", "This email is already used by another user.");
                return $this->redirect("/app/users/" . $user->getId() . "/edit");
            }
            unset($userSomeone);

            $userTarget->setEmail($email);
        }

        if($userTarget->getName() !== $name) {
            $userTarget->setName($name);
        }

        $password = $request->getBody('password');
        $rpassword = $request->getBody('rpassword');
        if($password && $rpassword) {
            if($password !== $rpassword) {
                $this->setFlash($request, "user.edit.error", "Password does not match.");
                return $this->redirect("/app/users/" . $user->getId() . "/edit");
            }

            $config = $this->configService->getConfig();

            if(strlen($password) < $config->getMinPasswordLength()) {
                $this->setFlash($request, "user.edit.error", "Password is too short.");
                return $this->redirect("/app/users/" . $user->getId() . "/edit");
            }

            if($config->isPasswordRequiresNumbers() && !preg_match('/[0-9]/', $password)) {
                $this->setFlash($request, "user.edit.error", "Password must contain at least one number.");
                return $this->redirect("/app/users/" . $user->getId() . "/edit");
            }

            if($config->isPasswordRequiresUppercase() && !preg_match('/[A-Z]/', $password)) {
                $this->setFlash($request, "user.edit.error", "Password must contain at least one letter uppercase.");
                return $this->redirect("/app/users/" . $user->getId() . "/edit");
            }

            if($config->isPasswordRequiresSpecial() && !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
                $this->setFlash($request, "user.edit.error", "Password must contain at least one special character.");
                return $this->redirect("/app/users/" . $user->getId() . "/edit");
            }

            $userTarget->setPassword(password_hash($password, PASSWORD_DEFAULT));
        }

        try {
            $this->entityManager->persist($userTarget);
            $this->entityManager->flush();

            $this->setFlash($request, "user.list.success", "User edited successfully.");
            $this->auditLogService->log("users.edit.success", [
                'user_agent' => $request->getHeader("User-Agent"),
                'ip' => $request->getUserIp()
            ], "User with id {$userTarget->getId()} has been edited.", $user);

            return $this->redirect("/app/users");
        } catch (Throwable $e) {
            $this->setFlash($request, "user.list.error", "Something went wrong: " . $e->getMessage());

            $this->auditLogService->log("users.edit.failure", [
                'user_agent' => $request->getHeader("User-Agent"),
                'ip' => $request->getUserIp()
            ], "User with id {$user->getId()} could not be edited.", $user);
            return $this->redirect("/app/users/" . $user->getId() . "/edit");
        }
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function createIndex(Request $request): Response
    {
        $user = $request->getAttributes()['user'];
        $error = $this->getFlash($request, "user.create.error");

        return $this->render('app/user/create.twig', [
            'user' => $user,
            'config' => $this->configService->getConfig(),
            'error' => $error
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->getAttributes()['user'];

        if(!$this->CSRFValidator->handle($request)) {
            $this->setFlash($request, "user.create.error", "Invalid CSRF token.");
            return $this->redirect("/app/users-new");
        }

        $config = $this->configService->getConfig();
        $name = $request->getBody('name');
        $email = $request->getBody('email');
        $password = $request->getBody('password');
        $rpassword = $request->getBody('rpassword');

        if(!$name || !$email || !$password || !$rpassword) {
            $this->setFlash($request, "user.create.error", "Please enter a name, email, and password.");
            return $this->redirect("/app/users-new");
        }

        if($password !== $rpassword) {
            $this->setFlash($request, "user.create.error", "Password does not match.");
            return $this->redirect("/app/users-new");
        }

        if(strlen($password) < $config->getMinPasswordLength()) {
            $this->setFlash($request, "user.create.error", "Password is too short.");
            return $this->redirect("/app/users-new");
        }

        if($config->isPasswordRequiresNumbers() && !preg_match('/[0-9]/', $password)) {
            $this->setFlash($request, "user.create.error", "Password must contain at least one number.");
            return $this->redirect("/app/users-new");
        }

        if($config->isPasswordRequiresUppercase() && !preg_match('/[A-Z]/', $password)) {
            $this->setFlash($request, "user.create.error", "Password must contain at least one letter uppercase.");
            return $this->redirect("/app/users-new");
        }

        if($config->isPasswordRequiresSpecial() && !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $this->setFlash($request, "user.create.error", "Password must contain at least one special character.");
            return $this->redirect("/app/users-new");
        }

        $userTarget = new User();
        $userTarget->setName($name)
            ->setEmail($email)
            ->setPassword(password_hash($password, PASSWORD_DEFAULT));

        try {
            $this->entityManager->persist($userTarget);
            $this->entityManager->flush();

            $this->setFlash($request, "user.list.success", "User created successfully.");
            $this->auditLogService->log("users.list.success", [
                'user_agent' => $request->getHeader("User-Agent"),
                'ip' => $request->getUserIp()
            ], "User with id {$userTarget->getId()} has been created.", $user);
            return $this->redirect("/app/users");
        } catch (Throwable $e) {
            $this->setFlash($request, "user.create.error", "Something went wrong: " . $e->getMessage());

            $this->auditLogService->log("users.create.failure", [
                'user_agent' => $request->getHeader("User-Agent"),
                'ip' => $request->getUserIp()
            ], "User with id {$user->getId()} could not be created.", $user);

            return $this->redirect("/app/users-new");
        }
    }
}