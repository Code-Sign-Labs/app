<?php
declare(strict_types=1);
namespace Framework\FileSystem\Models;

use FilesystemIterator;
use Framework\FileSystem\FileSystem;
use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Directory
{
    /**
     * @var string $dir
     */
    protected string $dir;

    /**
     * @var FileSystem $fileSystem
     */
    protected FileSystem $fileSystem;

    public function __construct(string $dir)
    {
        $this->dir = $dir;
        $this->fileSystem = new FileSystem();
    }

    /**
     * @return string
     */
    public function getDir(): string
    {
        return $this->dir;
    }


    /**
     * @return void
     * @throws \Exception
     */
    public function delete(): void
    {
        if(!$this->exists()) {
            throw new \Exception("Directory not found!");
        }

        $this->deleteDirectoryRecursively();

        if($this->exists()) {
            throw new \Exception("Failed to delete directory");
        }
    }

    /**
     * @return void
     * @throws \Exception
     */
    protected function deleteDirectoryRecursively(): void
    {
        foreach (scandir($this->dir) as $item) {
            if($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = "$this->dir/$item";

            if($this->exists($itemPath)) {
                $this->deleteDirectoryRecursively($itemPath);
            } else {
                if(!unlink($itemPath)) {
                    throw new \Exception("Failed to delete file: $itemPath");
                }
            }
        }

        if(!rmdir($this->dir)) {
            throw new \Exception("Failed to delete directory");
        }
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function size(): int
    {
        if(!$this->exists()) {
            throw new Exception("Directory does not exist");
        }

        $size = 0;
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->dir, FilesystemIterator::SKIP_DOTS));

        foreach ($files as $file) {
            $size += $file->getSize();
        }

        return $size;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function list(): array
    {
        if(!$this->exists()) {
            throw new \Exception("Directory does not exist");
        }

        $contents = scandir($this->dir);
        if($contents === false) {
            throw new \Exception("Failed to read directory");
        }

        return array_diff($contents, ['.', '..']);
    }

    /**
     * @param string $destination
     * @return void
     * @throws \Exception
     */
    public function move(string $destination): void
    {
        $this->fileSystem->move($this->dir, $destination);
        $this->dir = $destination;
    }

    /**
     * @param string $destination
     * @return Directory
     */
    public function copy(string $destination): Directory
    {
        $this->fileSystem->copy($this->dir, $destination);
        return new Directory($destination);
    }

    /**
     * @param string|null $dir
     * @return bool
     */
    public function exists(string $dir = null): bool
    {
        if(!$dir) $dir = $this->dir;
        return is_dir($dir);
    }
}