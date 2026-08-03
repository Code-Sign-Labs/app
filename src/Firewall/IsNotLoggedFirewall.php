<?php

namespace App\Firewall;

use Framework\Http\AbstractController;
use Framework\Http\Interfaces\FirewallInterface;
use Framework\Http\Objects\Request;
use Framework\Http\Objects\Response;

class IsNotLoggedFirewall extends AbstractController implements FirewallInterface
{
    /**
     * @param Request $request
     * @return bool
     */
    public function handle(Request $request): bool
    {
        return $request->session()->get('user_id') === null;
    }

    /**
     * @return Response
     */
    public function onFailure(): Response
    {
        return $this->redirect('/app/');
    }
}