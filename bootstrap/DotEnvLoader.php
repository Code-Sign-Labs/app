<?php

namespace Framework;

use Exception;

class DotEnvLoader
{
    /**
     * @var string $filePath
     */
    protected string $filePath;

    /**
     * @param string $filePath
     * @throws Exception
     */
    public function __construct(string $filePath)
    {
        if(!file_exists($filePath) || !is_readable($filePath)) {
            throw new Exception(".env file not found!");
        }

        $this->filePath = $filePath;
    }

    /**
     * @return void
     */
    public function load(): void
    {
        $lines = file($this->filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);
            if(str_starts_with($line, "#")) {
                continue;
            }

            if(str_contains($line, "=")) {
                [$key, $value] = explode("=", $line, 2);

                $key = trim($key);
                $value = trim($value);

                if(str_starts_with($value, '"') && str_ends_with($value, '"')) {
                    $value = substr($value, 1, -1);
                }

                $this->setEnvVariable($key, $value);
            }
        }
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    protected function setEnvVariable(string $key, mixed $value): void
    {
        if(!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}