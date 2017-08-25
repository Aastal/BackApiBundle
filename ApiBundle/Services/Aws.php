<?php

namespace Geoks\ApiBundle\Services;

use Aws\S3\S3Client;
use Gaufrette\Adapter\AwsS3;
use Gaufrette\Filesystem;
use Symfony\Component\DependencyInjection\ContainerInterface;

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

    public function __construct($key, $secret, $region, $version, $bucket)
    {
        $this->key = $key;
        $this->secret = $secret;
        $this->region = $region;
        $this->version = $version;
        $this->bucket = $bucket;
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
}
