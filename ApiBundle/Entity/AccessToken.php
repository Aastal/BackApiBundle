<?php

namespace Geoks\ApiBundle\Entity;

use FOS\OAuthServerBundle\Entity\AccessToken as BaseAccessToken;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class AccessToken
 * @package Geoks\ApiBundle\Entity
 *
 * @ORM\MappedSuperclass
 */
abstract class AccessToken extends BaseAccessToken
{
    /**
     * @var mixed
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Geoks\ApiBundle\Entity\Client")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected $client;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $user;
}