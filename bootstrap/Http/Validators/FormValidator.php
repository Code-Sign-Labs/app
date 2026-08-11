<?php
declare(strict_types=1);
namespace Framework\Http\Validators;

use Framework\Http\Objects\Request;

class FormValidator
{
    /**
     * @param array $configuration
     * @param Request $request
     * @return array|string[]
     */
    public function handle(array $configuration, Request $request): array
    {
        $data = $request->getAllBody();
        $errors = $this->validate($configuration, $data);

        if (isset($errors['status']) && $errors['status'] === 'valid') {
            return ['status' => true];
        }

        return [
            'status' => false,
            'errors' => $errors,
        ];
    }

    /**
     * @param array $configuration
     * @param array $data
     * @return array[]|string[]
     */
    protected function validate(array $configuration, array $data): array
    {
        $errors = [];
        $fields = $configuration['fields'];

        foreach($fields as $field => $rules) {
            $value = $data[$field] ?? null;

            if($rules['required'] && empty($value)) {
                $errors[$field][] = strtoupper($field) . '_REQUIRED';
                continue;
            }

            $fieldName = strtoupper($field);

            if($value !== null) {
                switch ($rules['type']) {
                    case 'string':
                        if (!is_string($value) || strlen($value) < $rules['min'] || strlen($value) > $rules['max']) {
                            $errors[$field] = "{$fieldName}_INVALID";
                        }
                        break;
                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field] = "{$fieldName}_INVALID";
                        }
                        break;
                    case 'integer':
                        if (!filter_var($value, FILTER_VALIDATE_INT)) {
                            $errors[$field] = "{$fieldName}_INVALID";
                        } elseif (isset($rules['min']) && $value < $rules['min']) {
                            $errors[$field] = "{$fieldName}_TOO_LOW";
                        }
                        break;
                    case 'ip':
                        if (!filter_var($value, FILTER_VALIDATE_IP)) {
                            $errors[$field] = "{$fieldName}_INVALID";
                        }
                        break;
                    case 'mac':
                        if (!filter_var($value, FILTER_VALIDATE_MAC)) {
                            $errors[$field] = "{$fieldName}_INVALID";
                        }
                        break;
                    case 'url':
                        if (!filter_var($value, FILTER_VALIDATE_URL)) {
                            $errors[$field] = "{$fieldName}_INVALID";
                        }
                        break;
                    case 'domain':
                        if (!filter_var($value, FILTER_VALIDATE_DOMAIN)) {
                            $errors[$field] = "{$fieldName}_INVALID";
                        }
                        break;
                }
            }
        }

        return empty($errors) ? ['status' => 'valid'] : ['errors' => $errors];
    }
}