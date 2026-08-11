<?php
declare(strict_types=1);
namespace Framework\Utils;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class DoctrineUuidType extends Type
{
    public const NAME = 'uuid_binary';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'BINARY(16)';
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?UuidInterface
    {
        if ($value === null || $value instanceof UuidInterface) {
            return $value;
        }

        // Handle 16-byte binary
        if (is_string($value) && strlen($value) === 16) {
            return Uuid::fromBytes($value);
        }

        // Handle textual UUID (36 chars)
        if (is_string($value) && strlen($value) === 36) {
            return Uuid::fromString($value);
        }

        throw new \InvalidArgumentException('Invalid binary UUID value: ' . var_export($value, true));
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof UuidInterface) {
            return $value->getBytes();
        }

        // Accept string UUIDs too
        if (is_string($value)) {
            return Uuid::fromString($value)->getBytes();
        }

        throw new \InvalidArgumentException('Invalid UUID value for database conversion: ' . gettype($value));
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}