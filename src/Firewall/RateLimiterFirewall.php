<?php
declare(strict_types=1);
namespace App\Firewall;

use App\Controller\API\CoreApiController;
use App\Service\RateLimiter;
use Doctrine\ORM\EntityManager;
use Framework\Http\Interfaces\FirewallInterface;
use Framework\Http\Objects\Request;
use Framework\Http\Objects\Response;
use Framework\Http\ViewEngine\ViewEngineInterface;
use Redis;

class RateLimiterFirewall extends CoreApiController implements FirewallInterface
{
    protected RateLimiter $rateLimiter;
    protected Response $response;
    public function __construct(ViewEngineInterface $viewEngine, EntityManager $entityManager, protected Redis $redis)
    {
        if($_ENV["RATE_LIMITER_ENABLED"] === "true") {
            $this->rateLimiter = new RateLimiter($this->redis);
        };
        parent::__construct($viewEngine, $entityManager);
    }

    public function handle(Request $request): bool
    {
        if($_ENV["RATE_LIMITER_ENABLED"] !== "true") return true;

        $ip = $request->getUserIp();
        $hashedIp = hash('sha256', $ip);
        $rateLimitObject = $this->rateLimiter->check($hashedIp);

        $response = new Response();
        $response->setHeaders([
            'X-RateLimit-Limit' => $rateLimitObject->getLimit(),
            'X-RateLimit-Remaining' => $rateLimitObject->getRemaining(),
            'X-RateLimit-Reset' => $rateLimitObject->getResetIn(),
            'Content-Type' => 'application/json',
        ]);

        if(!$rateLimitObject->isAllowed()) {
            $response->setStatusCode(429);
            $response->setBody(json_encode([
                'success' => false,
                'response' => [],
                'error' => [
                    'code' => "RATE_LIMIT_EXCEEDED",
                    'description' => "Rate limit exceeded",
                    'data' => [
                        'limit' => $rateLimitObject->getLimit(),
                        'remaining' => $rateLimitObject->getRemaining(),
                        'reset_in' => $rateLimitObject->getResetIn(),
                    ]
                ],
                'metadata' => [
                    'timestamp' => microtime(true),
                    'timezone' => date_default_timezone_get()
                ]
            ]));
            $this->response = $response;

            return false;
        }

        return true;
    }


    public function onFailure(): Response
    {
        return $this->response;
    }
}