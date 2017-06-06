<?php

namespace Geoks\ApiBundle\Services;

use Aws\S3\S3Client;
use Gaufrette\Adapter\AwsS3;
use Gaufrette\Filesystem;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Aws
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getS3Instance()
    {
        $config = array(
            'credentials' => [
                'key' => $this->container->getParameter('amazon.s3.key'),
                'secret' => $this->container->getParameter('amazon.s3.secret')
            ],
            'region' => $this->container->getParameter('amazon.s3.region'),
            'version' => $this->container->getParameter('amazon.s3.version')
        );

        $service = new S3Client($config);
        $service->registerStreamWrapper();

        $client = new AwsS3($service, $this->container->getParameter('amazon.s3.bucket'));
        $fsaws = new Filesystem($client);

        return $fsaws;
    }
}