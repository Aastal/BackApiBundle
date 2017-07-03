<?php

namespace Geoks\ApiBundle\Entity;

use FOS\OAuthServerBundle\Entity\Client as BaseClient;
use Doctrine\ORM\Mapping as ORM;

class Client extends BaseClient
{
    /**
     * mixed
     */
    protected $id;

    /**
     * @var string
     */
    private $name;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get name
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * Set name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
}
