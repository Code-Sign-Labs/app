<?php
declare(strict_types=1);
namespace App\Extension;

use Framework\Http\Objects\Request;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CSRFExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('csrf', function (string $formName = 'default'): string {
                $request = new Request();
                $token = $request->session()->get('csrf_token_' . $formName) ?? bin2hex(random_bytes(32));
                $request->session()->set('csrf_token_' . $formName, $token);

                return $token;
            }),
        ];
    }
}
