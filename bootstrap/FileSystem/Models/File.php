<?php
declare(strict_types=1);
namespace Framework\FileSystem\Models;

use Exception;
use Framework\FileSystem\FileSystem;

class File
{
    /**
     * @var string $filePath
     */
    protected string $filePath;

    /**
     * @var FileSystem $fileSystem
     */
    protected FileSystem $fileSystem;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
        $this->fileSystem = new FileSystem();
    }

    /**
     * @return string
     * @throws Exception
     */
    public function read(): string
    {
        return $this->fileSystem->read($this->filePath);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function remove(): void
    {
        $this->fileSystem->delete($this->filePath);
    }

    /**
     * @param string $contents
     * @param bool $append
     * @return void
     * @throws Exception
     */
    public function write(string $contents, bool $append = false): void
    {
        $this->fileSystem->write($this->filePath, $contents, $append);
    }

    /**
     * @param string $destination
     * @return void
     * @throws Exception
     */
    public function move(string $destination): void
    {
        $this->fileSystem->move($this->filePath, $destination);
        $this->filePath = $destination;
    }

    /**
     * @param string $destination
     * @return File
     */
    public function copy(string $destination): File
    {
        $this->fileSystem->copy($this->filePath, $destination);
        return new File($destination);
    }

    /**
     * @return bool
     */
    public function exists(): bool
    {
        return $this->fileSystem->exists($this->filePath);
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->filePath;
    }

    /**
     * @return int
     */
    public function getSize(): int
    {
        return filesize($this->filePath);
    }


    /**
     * @param int $decimals
     * @return string
     */
    public function getHumanSize(int $decimals = 2): string
    {
        $bytes = $this->getSize();
        $sz = "BKMGTP";
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024,$factor)) . @$sz[$factor];
    }
}
