<?php

namespace Geoks\ApiBundle\Command;

use Aws\S3\S3Client;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Util\ClassUtils;
use Gaufrette\Adapter\AmazonS3;
use Gaufrette\Adapter\AwsS3;
use Liip\ImagineBundle\Imagine\Cache\Resolver\AmazonS3Resolver;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CronResizeImageCommand extends CronTaskCommand
{
    protected $name = "resize-image";
    protected $description = "Resize images in amazone s3 server.";

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getContainer()->get('geoks.uploader')->resizeUpload();
    }
}
