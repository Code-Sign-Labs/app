<?php
declare(strict_types=1);
namespace App\Firewall;

use App\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManager;
use Framework\Http\AbstractController;
use Framework\Http\Interfaces\FirewallInterface;
use Framework\Http\Objects\Request;
use Framework\Http\Objects\Response;
use Framework\Http\ViewEngine\ViewEngineInterface;
use Throwable;

class IsLoggedFirewall extends AbstractController implements FirewallInterface
{
    protected ?Request $request = null;

    public function __construct(
        protected ViewEngineInterface $viewEngine,
        public EntityManager          $entityManager,
    ) {
        parent::__construct($this->viewEngine);
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function handle(Request $request): bool
    {
        $this->request = $request;
        $userId = $request->session()->get("user_id");
        if(!$userId) return false;

        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->find($userId);

        if(!$user) {
            $request->session()->remove("user_id");
            return false;
        }

        $request->setAttribute("user", $user);
        return true;
    }

    /**
     * @return Response
     */
    public function onFailure(): Response
    {
        return $this->redirect("/login");
    }
}