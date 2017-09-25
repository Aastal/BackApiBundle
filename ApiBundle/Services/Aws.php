<?php

namespace Geoks\ApiBundle\Services;

use Aws\S3\S3Client;
use Gaufrette\Adapter\AwsS3;
use Gaufrette\Filesystem;
use Smalot\PdfParser\Parser;
use Symfony\Component\Filesystem\Filesystem as System;
use Symfony\Component\HttpFoundation\File\File;

class Aws
{
    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $secret;

    /**
     * @var string
     */
    private $region;

    /**
     * @var string
     */
    private $version;

    /**
     * @var string
     */
    private $bucket;

    /**
     * @var string
     */
    private $rootDir;

    public function __construct($key, $secret, $region, $version, $bucket, $rootDir)
    {
        $this->key = $key;
        $this->secret = $secret;
        $this->region = $region;
        $this->version = $version;
        $this->bucket = $bucket;
        $this->rootDir = $rootDir;
    }

    public function getS3Instance()
    {
        $config = array(
            'credentials' => [
                'key' => $this->key,
                'secret' => $this->secret
            ],
            'region' => $this->region,
            'version' => $this->version
        );

        $service = new S3Client($config);
        $service->registerStreamWrapper();

        $client = new AwsS3($service, $this->bucket);
        $fsaws = new Filesystem($client);

        return $fsaws;
    }

    public function getS3File($file)
    {
        return $this->getS3Instance()->get($file);
    }

    /**
     * @param string $file
     * @return string
     */
    public function PdfToText($file)
    {
        $parser = new Parser();
        $system = new System();

        $file = $this->getS3File($file);
        $system->dumpFile($this->rootDir . "/../web/assets/" . $file->getName(), $file->getContent(), 0777);

        $file = new File($this->rootDir . "/../web/assets/" . $file->getName());

        $pdf = $parser->parseFile($file->getRealPath());

        try
        {
            $text = '';
            $pages = $pdf->getPages();

            foreach ($pages as $page) {
                $text .= $page->getText();
            }
        } catch (\Exception $e) {
            $text = "Impossible de lire le fichier.";
        }

        $system->remove($file->getRealPath());

        return $text;
    }

    /**
     * @param string $file
     * @return string
     */
    public function DocxToText($file)
    {
        $system = new System();

        $file = $this->getS3File($file);
        $system->dumpFile($this->rootDir . "/../web/assets/" . $file->getName(), $file->getContent(), 0777);

        $file = new File($this->rootDir . "/../web/assets/" . $file->getName());

        $content = '';

        $zip = zip_open($file->getRealPath());

        if (!$zip || is_numeric($zip)) return false;

        while ($zipEntry = zip_read($zip)) {

            if (zip_entry_open($zip, $zipEntry) == false) continue;

            if (zip_entry_name($zipEntry) != "word/document.xml") continue;

            $content .= zip_entry_read($zipEntry, zip_entry_filesize($zipEntry));

            zip_entry_close($zipEntry);
        }

        zip_close($zip);

        $content = str_replace('</w:r></w:p></w:tc><w:tc>', " ", $content);
        $content = str_replace('</w:r></w:p>', "\r\n", $content);
        $stripedContent = strip_tags($content);

        $system->remove($file->getRealPath());

        return $stripedContent;
    }

    /**
     * @param $file
     * @return mixed|string
     */
    public function DocToText($file)
    {
        $system = new System();

        $file = $this->getS3File($file);
        $system->dumpFile($this->rootDir . "/../web/assets/" . $file->getName(), $file->getContent(), 0777);

        $file = new File($this->rootDir . "/../web/assets/" . $file->getName());

        $fileHandle = fopen($file->getRealPath(), "r");
        $line = @fread($fileHandle, filesize($file->getRealPath()));
        $lines = explode(chr(0x0D),$line);
        $text = "";

        foreach($lines as $line) {
            $pos = strpos($line, chr(0x00));

            if (($pos !== false) || (strlen($line) === 0)) {
            } else {
                $text .= $line . " ";
            }
        }

        $text = preg_replace("/[^a-zA-Z0-9\s\,\.\-\n\r\t@\/\_\(\)]/", "", $text);

        $system->remove($file->getRealPath());

        return $text;
    }
}
