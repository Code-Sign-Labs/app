<?php
declare(strict_types=1);
namespace App\Firewall;

use Framework\Http\AbstractController;
use Framework\Http\Interfaces\FirewallInterface;
use Framework\Http\Objects\Request;
use Framework\Http\Objects\Response;

class IsNotLoggedFirewall extends AbstractController implements FirewallInterface
{
    protected bool $byAllowlist = false;

    /**
     * @param Request $request
     * @return bool
     */
    public function handle(Request $request): bool
    {
        $ipAllowlistFirewall = AccessIpAllowlistFirewall::handleStatic($request);
        if(!$ipAllowlistFirewall) {
            $this->byAllowlist = true;
            return false;
        };

        return $request->session()->get('user_id') === null;
    }

    /**
     * @return Response
     */
    public function onFailure(): Response
    {
        if($this->byAllowlist) {
            return $this->text("Unauthorized " . $_SERVER["REMOTE_ADDR"], 401);
        }

        return $this->redirect('/app/');
    }
}