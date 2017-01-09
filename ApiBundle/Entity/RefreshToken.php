<?php

namespace Geoks\ApiBundle\Entity;

use FOS\OAuthServerBundle\Entity\RefreshToken as BaseRefreshToken;
use Doctrine\ORM\Mapping as ORM;use Geoks\UserBundle\Entity\User;

/**
 * @ORM\MappedSuperclass
 */
class RefreshToken extends BaseRefreshToken
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