<?php

namespace KunversionApi\Helpers;

use Exception;
use Illuminate\Support\Facades\Log;

class FileManager 
{
    const MODE_READ = 'r';
    const MODE_WRITE = 'w';
    const MODE_READ_AND_WRITE = 'r+';

    /**
     * Opened file stream
     */
    private $file;

    /**
     * Open file stream
     *
     * @param string $filePath
     * @param string $mode
     * 
     * @return bool
     * 
     */
    public function openFile(string $filePath, string $mode = self::MODE_WRITE): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        if (isset($this->file)) {
            $this->closeFile();
        }

        dump('[openFile] file: ', $this->file);
        
        // with 'w' keyword the file will be truncated if already exists
        // See more: https://www.php.net/manual/en/function.fopen.php
        $this->file = fopen($filePath, $mode);

        return true;
    }

    /**
     * Write new line content to opened file
     *
     * @param string $newLineContent
     * 
     * @return bool
     * 
     */
    public function writeFile(string $newLineContent): bool
    {
        if (!isset($this->file)) {
            return false;
        }

        fwrite($this->file, $newLineContent);

        return true;
    }

    /**
     * Remove file
     *
     * @param string $filePath
     * 
     * @return bool
     * 
     */
    public function removeFile(string $filePath): bool
    {
        if (isset($this->file)) {
            $this->closeFile();
        }

        unlink($filePath);

        if (file_exists($filePath)) {
            return false;
        }

        return true;
    }

    /**
     * Close opened file
     *
     * @return bool
     * 
     */
    public function closeFile(): bool
    {
        if (!isset($this->file)) {
            return true;
        }

        fclose($this->file);

        return true;
    }

    /**
     * Read opened file
     *
     * @return array|null
     * 
     */
    public function getContent(): ?array
    {
        try {
            if (!isset($this->file)) {
                return null;
            }

            $fileContent = [];

            while (($line = fgets($this->file)) !== false) {
                $lineContent = json_decode($line, true);
                $fileContent[] = $lineContent;
            }

            return $fileContent;
        } catch (Exception $e) {
            Log::debug($e);
            
            Newrelic::logEvent(Newrelic::KVCORE_READ_SPREADSHEET_FILE_ERROR, [
                'method' => 'read()',
                'message' => 'Read File Failed',
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }
}
