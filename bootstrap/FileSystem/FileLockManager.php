<?php

namespace Framework\FileSystem;


use Exception;

class FileLockManager
{
    /**
     * @var mixed $fileHandle
     */
    protected mixed $fileHandle;

    /**
     * @param string $path
     * @param bool $exclusive
     * @return true
     * @throws Exception
     */
    public function acquireLock(string $path, bool $exclusive = true): true
    {
        if(!file_exists($path)) {
            throw new Exception("File does not exist!");
        }

        $this->fileHandle = fopen($path, "c");
        if($this->fileHandle === false) {
            throw new Exception("Failed to open lock file!");
        }

        $lockType = $exclusive ? LOCK_EX : LOCK_SH;
        if(!flock($this->fileHandle, $lockType)) {
            fclose($this->fileHandle);
            throw new Exception("Failed to acquire lock!");
        }

        return true;
    }

    /**
     * @return true
     * @throws Exception
     */
    public function releaseLock(): true
    {
        if($this->fileHandle === null) {
            throw new Exception("No file is currently locked!");
        }

        if(!flock($this->fileHandle, LOCK_UN)) {
            throw new Exception("Failed to release lock on file.");
        }

        fclose($this->fileHandle);
        $this->fileHandle = null;

        return true;
    }

    /**
     * @return bool
     */
    public function isLocked(): bool
    {
        return $this->fileHandle !== null;
    }
}