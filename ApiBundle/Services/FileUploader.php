<?php

namespace Geoks\ApiBundle\Services;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploader
{
    public function upload(UploadedFile $file, $targetDir)
    {
        $fileName = md5(uniqid()).'.'.$file->guessExtension();

        $file->move($targetDir, $fileName);

        return $fileName;
    }
}