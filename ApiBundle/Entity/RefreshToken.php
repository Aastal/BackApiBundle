<?php

namespace Geoks\ApiBundle\Entity;

use FOS\OAuthServerBundle\Entity\RefreshToken as BaseRefreshToken;
use Doctrine\ORM\Mapping as ORM;use Geoks\UserBundle\Entity\User;

class RefreshToken extends BaseRefreshToken
{
    /**
     * @var mixed
     */
    protected $id;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var User
     */
    protected $user;
}
