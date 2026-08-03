<?php

namespace Framework\FileSystem;

use Exception;
use Framework\FileSystem\Models\File;

class FileIntegrityChecker
{
    /**
     * @param string $path
     * @param string $algorithm
     * @return string
     * @throws Exception
     */
    public function calcChecksum(string $path, string $algorithm = 'sha256'): string
    {
        if(!file_exists($path)) {
            throw new Exception("File does not exist");
        }

        if(!in_array($algorithm, hash_algos())) {
            throw new Exception("Unknown algorithm");
        }

        $hash = hash_file($algorithm, $path);

        if($hash === false) {
            throw new Exception("Failed to calculate checksum for file");
        }

        return $hash;
    }

    /**
     * @param string $path
     * @param string $checksum
     * @param string $algorithm
     * @return bool
     * @throws Exception
     */
    public function verifyChecksum(string $path, string $checksum, string $algorithm = 'sha256'): bool
    {
        $realChecksum = $this->calcChecksum($path, $algorithm);
        return hash_equals($realChecksum, $checksum);
    }

    /**
     * @param string $path
     * @param string $algorithm
     * @return File
     * @throws Exception
     */
    public function generateChecksumFile(string $path, string $algorithm = 'sha256'): File
    {
        $checksum = $this->calcChecksum($path, $algorithm);
        $checksumFilePath = $path . '.' . $algorithm . '.checksum';

        if(file_put_contents($checksumFilePath, $checksum) === false) {
            throw new Exception("Failed to write checksum file");
        }

        return new File($checksumFilePath);
    }

    /**
     * @param string $path
     * @param string $checksumFile
     * @param string $algorithm
     * @return bool
     * @throws Exception
     */
    public function validateAgainstChecksumFile(string $path, string $checksumFile, string $algorithm = 'sha256'): bool
    {
        if(!file_exists($path)) {
            throw new Exception("File does not exist");
        }

        $realChecksum = trim(file_get_contents($checksumFile));
        return $this->verifyChecksum($path, $realChecksum, $algorithm);
    }
}