<?php
declare(strict_types=1);
namespace App\Controller;

use Framework\Http\Interfaces\NotFoundControllerInterface;
use Framework\Http\Objects\Request;
use Framework\Http\Objects\Response;

class NotFoundController extends CoreAbstractController implements NotFoundControllerInterface
{
    public function handle(Request $request): Response
    {
        if(str_starts_with($request->getUri(), "/api")) {
            $response = new Response();
            $response->setStatusCode(404);
            $response->setHeaders([
                'Content-Type' => 'application/json',
            ]);
            $response->setBody(json_encode([
                'success' => false,
                'response' => [],
                'error' => [
                    'code' => "NOT_FOUND",
                    'description' => "The requested resource was not found",
                    'data' => [
                        'uri' => $request->getUri(),
                    ]
                ],
                'metadata' => [
                    'timestamp' => microtime(true),
                    'timezone' => date_default_timezone_get()
                ]
            ]));
            return $response;
        }
        return $this->redirect("/login");
    }
}