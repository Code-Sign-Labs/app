<?php
declare(strict_types=1);

namespace App\Firewall;

use Framework\Http\AbstractController;
use Framework\Http\Interfaces\FirewallInterface;
use Framework\Http\Objects\Request;
use Framework\Http\Objects\Response;

class AccessIpAllowlistFirewall extends AbstractController implements FirewallInterface
{
    public function handle(Request $request): bool
    {
        return self::handleStatic($request);
    }

    public static function handleStatic(Request $request): bool
    {
        if ($_ENV["ACCESS_IP_ALLOWLIST_ENABLED"] !== "true") return true;

        $allowlist = explode(",", $_ENV["ACCESS_IP_ALLOWLIST"]);
        $clientIp = $request->getUserIp();

        return in_array($clientIp, $allowlist);
    }

    public function onFailure(): Response
    {
        return $this->text("Unauthorized " . $_SERVER["REMOTE_ADDR"], 401);
    }
}