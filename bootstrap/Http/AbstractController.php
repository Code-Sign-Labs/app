<?php
declare(strict_types=1);
namespace Framework\Http;

use Framework\Http\Objects\Response;
use Framework\Http\ViewEngine\ViewEngineInterface;

class AbstractController
{
    /**
     * @param ViewEngineInterface $viewEngine
     */
    public function __construct(protected ViewEngineInterface $viewEngine)
    {
    }

    /**
     * @param string $url
     * @param int $status
     * @return Response
     */
    protected function redirect(string $url, int $status = 302): Response
    {
        $response = new Response();
        $response->setStatusCode($status);
        $response->setBody("");
        $response->addHeader('Location', $url);

        return $response;
    }

    /**
     * @param string $template
     * @param array $data
     * @param array $headers
     * @return Response
     */
    protected function render(string $template, array $data = [], array $headers = []): Response
    {
        $response = new Response();
        $response->setStatusCode(200);
        $response->setBody($this->viewEngine->render($template, $data));
        $response->setHeaders($headers);
        return $response;
    }

    /**
     * @param array $data
     * @param int $status
     * @param array $headers
     * @return Response
     */
    protected function json(array $data, int $status = 200, array $headers = []): Response
    {
        $response = new Response();
        $response->setStatusCode($status);
        $response->setBody(json_encode($data));
        $response->setHeaders(array_merge($headers, [
            'Content-Type' => 'application/json',
        ]));

        return $response;
    }

    /**
     * @param string $text
     * @param int $status
     * @param array $headers
     * @return Response
     */
    protected function text(string $text, int $status = 200, array $headers = []): Response
    {
        $response = new Response();
        $response->setStatusCode($status);
        $response->setBody($text);
        $response->setHeaders(array_merge($headers, [
            'Content-Type' => 'text/plain',
        ]));

        return $response;
    }
}