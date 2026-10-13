<?php
declare(strict_types=1);

namespace App\Service;

use App\Messenger\Handler\EmailHandler;
use App\Messenger\Message\EmailMessage;
use DateTime;
use DateTimeInterface;
use Framework\Messenger\MessageDispatcher;
use ReflectionClass;
use ReflectionMethod;
use Traversable;

class MailingService
{
    public function __construct(protected MessageDispatcher $dispatcher)
    {
    }

    public function sendMail(string $template, string $subject, string $to, array $data = []): void
    {
        $normalizedData = $this->normalizeTemplateData($data);

        $message = new EmailMessage();
        $message->setEmail($to)
            ->setAdditionalData([
                'template' => $template,
                'subject' => $subject,
                'variables' => $normalizedData
            ])
            ->setAvailable(new DateTime())
            ->setContent(sprintf("Sending email to %s with subject %s", $to, $subject))
            ->setMessage(sprintf("Sending email to %s with subject %s", $to, $subject))
            ->setHandler(new EmailHandler());

        $this->dispatcher->dispatch($message);
    }

    private function normalizeTemplateData(array $data): array
    {
        $seen = [];

        return $this->normalizeTemplateValue($data, $seen);
    }

    private function normalizeTemplateValue(mixed $value, array &$seen): mixed
    {
        if (is_array($value)) {
            $normalized = [];

            foreach ($value as $key => $item) {
                $normalized[$key] = $this->normalizeTemplateValue($item, $seen);
            }

            return $normalized;
        }

        if ($value instanceof DateTimeInterface || $value instanceof \UnitEnum || is_scalar($value) || $value === null) {
            return $value;
        }

        if ($value instanceof Traversable) {
            return $this->normalizeTemplateValue(iterator_to_array($value, true), $seen);
        }

        if (is_object($value)) {
            $objectId = spl_object_id($value);

            if (isset($seen[$objectId])) {
                return null;
            }

            $seen[$objectId] = true;

            $normalized = [];
            $reflection = new ReflectionClass($value);

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->getNumberOfRequiredParameters() !== 0) {
                    continue;
                }

                $methodName = $method->getName();
                $key = null;

                if (str_starts_with($methodName, 'get') && $methodName !== 'get') {
                    $key = lcfirst(substr($methodName, 3));
                } elseif (str_starts_with($methodName, 'is') && $methodName !== 'is') {
                    $key = lcfirst(substr($methodName, 2));
                } elseif (str_starts_with($methodName, 'has') && $methodName !== 'has') {
                    $key = lcfirst(substr($methodName, 3));
                }

                if ($key === null || $key === '') {
                    continue;
                }

                try {
                    $normalized[$key] = $this->normalizeTemplateValue($method->invoke($value), $seen);
                } catch (\Throwable) {
                    // Ignore getters that cannot be evaluated safely while preparing template data.
                }
            }

            if ($normalized !== []) {
                return $normalized;
            }

            if ($value instanceof \Stringable) {
                return (string) $value;
            }

            return $value;
        }

        return $value;
    }
}