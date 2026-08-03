<?php

namespace Framework\FileSystem;

use Exception;

class FileSystem
{
    /**
     * @param string $path
     * @return bool
     */
    public function exists(string $path): bool
    {
        return file_exists($path);
    }

    /**
     * @param string $oldPath
     * @param string $newPath
     * @return bool
     */
    public function copy(string $oldPath, string $newPath): bool
    {
        return copy($oldPath, $newPath);
    }

    /**
     * @param string $source
     * @param string $destination
     * @return true
     * @throws Exception
     */
    public function move(string $source, string $destination): true
    {
        if(!file_exists($source)) {
            throw new Exception("Source file does not exist: $source");
        }

        if(!rename($source, $destination)) {
            throw new Exception("Could not move file from $source to $destination");
        }

        return true;
    }

    /**
     * @param string $path
     * @return string
     * @throws Exception
     */
    public function read(string $path): string
    {
        if(!is_file($path)) {
            throw new Exception("File not found: $path");
        }

        $content = file_get_contents($path);
        if(!$content) {
            throw new Exception("Failed to read file: $path");
        }

        return $content;
    }

    /**
     * @param string $path
     * @param string $content
     * @param bool $append
     * @return void
     * @throws Exception
     */
    public function write(string $path, string $content, bool $append = false): void
    {
        $flags = $append ? FILE_APPEND : 0;
        if(!file_put_contents($path, $content, $flags)) {
            throw new Exception("Failed to write to file: $path");
        }
    }

    /**
     * @param string $path
     * @return void
     * @throws Exception
     */
    public function delete(string $path): void
    {
        if(!is_file($path) || !unlink($path)) {
            throw new Exception("Failed to delete file: $path");
        }
    }
}