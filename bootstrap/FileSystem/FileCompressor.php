<?php

namespace Framework\FileSystem;

use Exception;
use Framework\FileSystem\Models\File;
use ZipArchive;

class FileCompressor
{
    /**
     * @var FileSystem $fileSystem
     */
    protected FileSystem $fileSystem;

    public function __construct()
    {
        $this->fileSystem = new FileSystem();
    }

    /**
     * @param string $sourcePath
     * @param string $destinationPath
     * @param string $algorithm
     * @return File
     * @throws Exception
     */
    public function compress(string $sourcePath, string $destinationPath, string $algorithm = "gzip"): File
    {
        if(!$this->fileSystem->exists($sourcePath)) {
            throw new Exception("Source file does not exist!");
        }

        $data = file_get_contents($sourcePath);
        if($data === false) {
            throw new Exception("Source file is not readable!");
        }

        $compressedData = $data;

        switch ($algorithm) {
            case 'gzip':
                $compressedData = gzencode($data);
                break;
            case 'zip':
                $zip = new ZipArchive();
                if($zip->open($destinationPath, ZipArchive::CREATE) !== true) {
                    throw new Exception("Failed to create zip archive: $destinationPath");
                }

                if(!$zip->addFile($sourcePath, basename($sourcePath))) {
                    $zip->close();
                    throw new Exception("Failed to add file: $sourcePath");
                }

                $zip->close();
                return new File($destinationPath);
            case 'bzip2':
                $compressedData = bzcompress($data);
                break;
        }

        if($compressedData === false) {
            throw new Exception("Failed to compress data!");
        }

        file_put_contents($destinationPath, $compressedData);
        return new File($destinationPath);
    }

    /**
     * @param string $sourcePath
     * @param string $destination
     * @param string $algorithm
     * @return File
     * @throws Exception
     */
    public function decompress(string $sourcePath, string $destination, string $algorithm = 'gzip'): File
    {
        if(!$this->fileSystem->exists($sourcePath)) {
            throw new Exception("Source file does not exist!");
        }

        $data = file_get_contents($sourcePath);
        if($data === false) {
            throw new Exception("Source file is not readable!");
        }

        $decompressedData = $data;

        switch ($algorithm) {
            case 'gzip':
                $decompressedData = gzdecode($data);
                break;
            case 'zip':
                $zip = new ZipArchive();
                if($zip->open($sourcePath) !== true) {
                    throw new Exception("Failed to open zip archive");
                }

                if(!$zip->extractTo($destination)) {
                    $zip->close();
                    throw new Exception("Failed to extract zip archive");
                }

                $zip->close();
                return new File($destination);
            case 'bzip2':
                $decompressedData = bzdecompress($data);
                break;
        }

        if($decompressedData === false) {
            throw new Exception("Failed to decompress data!");
        }

        file_put_contents($destination, $decompressedData);
        return new File($destination);
    }
}