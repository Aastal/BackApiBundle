<?php

namespace Geoks\ApiBundle\Entity;

use FOS\OAuthServerBundle\Entity\AuthCode as BaseAuthCode;
use Doctrine\ORM\Mapping as ORM;
use Geoks\UserBundle\Entity\User;

/**
 * @ORM\Table("oauth2_auth_codes")
 * @ORM\Entity
 */
class AuthCode extends BaseAuthCode
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
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
