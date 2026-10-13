<?php
declare(strict_types=1);
namespace Framework\Http\Validators;

enum ValidatorEnum
{
    const CSRF_FORM_FIELD = '__token';
    const CSRF_HEADER_FIELD = 'X-CSRF-TOKEN';
    const CSRF_SESSION_FIELD = 'csrf_token_';
}
