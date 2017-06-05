<?php

namespace Geoks\ApiBundle\Utils;

use Symfony\Component\HttpFoundation\File\File;

class ImageUtil
{
    public function resizeImage(File $image, $width, $height)
    {
        list($width_orig, $height_orig) = getimagesize($image->getRealPath());

        $ratio_orig = $width_orig / $height_orig;

        if ($width/$height > $ratio_orig) {
            $width = $height * $ratio_orig;
        } else {
            $height = $width / $ratio_orig;
        }

        $image_p = imagecreatetruecolor($width, $height);

        if ($image->getMimeType() == "image/jpeg") {
            $imageFrom = imagecreatefromjpeg($image->getRealPath());
        } elseif ($image->getMimeType() == "image/png") {
            $imageFrom = imagecreatefrompng($image->getRealPath());
        } elseif ($image->getMimeType() == "image/gif") {
            $imageFrom = imagecreatefromgif($image->getRealPath());
        } else {
            throw new \Exception("Wrong image format");
        }

        imagecopyresampled($image_p, $imageFrom, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);

        if ($image->getMimeType() == "image/jpeg") {
            imagejpeg($image_p, $image->getRealPath());
        } elseif ($image->getMimeType() == "image/png") {
            imagepng($image_p, $image->getRealPath());
        } elseif ($image->getMimeType() == "image/gif") {
            imagegif($image_p, $image->getRealPath());
        }

        return $image_p;
    }
}