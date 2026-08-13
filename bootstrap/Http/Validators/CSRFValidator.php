<?php
declare(strict_types=1);
namespace Framework\Http\Validators;

use Framework\Http\Objects\Request;
use Random\RandomException;

class CSRFValidator
{
    /**
     * @param Request $request
     * @param string|null $formName
     * @return bool
     */
    public function handle(Request $request, ?string $formName = 'default'): bool
    {
        $token = $request->getBody(ValidatorEnum::CSRF_FORM_FIELD) ?? $request->getHeader(ValidatorEnum::CSRF_HEADER_FIELD);

        return
            $request->session()->get(ValidatorEnum::CSRF_SESSION_FIELD . $formName) && $token
            &&
            hash_equals(
                $request->session()->get(ValidatorEnum::CSRF_SESSION_FIELD . $formName),
                $token
            );
    }

    /**
     * @param Request $request
     * @param string|null $formName
     * @return string
     * @throws RandomException
     */
    public static function setup(Request $request, ?string $formName = 'default'): string
    {
        $token = bin2hex(random_bytes(64));
        $request->session()->set(ValidatorEnum::CSRF_SESSION_FIELD . $formName, $token);
        return $token;
    }
}