<?php

namespace Framework\FileSystem;

use Exception;

class FileContentSearcher
{
    /**
     * @param string $path
     * @param string $search
     * @return array
     * @throws Exception
     */
    public function searchString(string $path, string $search): array
    {
        if(!file_exists($path)) {
            throw new Exception("File does not exist");
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if($lines === false) {
            throw new Exception("Failed to read file");
        }

        $foundLines = [];
        foreach($lines as $lineNumber => $lineContent) {
            if(str_contains($lineContent, $search)) {
                $foundLines[] = $lineNumber + 1;
            }
        }

        return $foundLines;
    }

    /**
     * @param string $path
     * @param string $pattern
     * @return array
     * @throws FileManagerException
     */
    public function searchPattern(string $path, string $pattern): array
    {
        if(!file_exists($path)) {
            throw new FileManagerException("File does not exist");
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if($lines === false) {
            throw new FileManagerException("Failed to read file");
        }

        $foundLines = [];
        foreach($lines as $lineNumber => $lineContent) {
            if(preg_match($pattern, $lineContent)) {
                $foundLines[] = $lineNumber + 1;
            }
        }

        return $foundLines;
    }

    /**
     * @param string $path
     * @param string $pattern
     * @param string $replacement
     * @return bool
     * @throws FileManagerException
     */
    public function replacePattern(string $path, string $pattern, string $replacement): bool
    {
        if(!file_exists($path)) {
            throw new FileManagerException("File does not exist");
        }

        $fileContents = file_get_contents($path);
        if($fileContents === false) {
            throw new FileManagerException("Failed to read file");
        }

        $newContents = preg_replace($pattern, $replacement, $fileContents);
        if($newContents === false) {
            throw new FileManagerException("Failed to read file");
        }

        return file_put_contents($path, new $newContents) !== false;
    }
}