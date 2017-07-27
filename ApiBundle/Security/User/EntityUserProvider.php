<?php

namespace Geoks\ApiBundle\Security\User;

use Geoks\ApiBundle\Entity\AccessToken;
use Geoks\ApiBundle\Entity\Client;
use Geoks\UserBundle\Entity\User;
use HWI\Bundle\OAuthBundle\Security\Core\User\EntityUserProvider as BaseClass;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class EntityUserProvider extends BaseClass
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var mixed
     */
    private $accessToken;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * EntityUserProvider constructor.
     *
     * @param ManagerRegistry $userManager
     * @param string $class
     * @param array $properties
     * @param ContainerInterface $container
     * @param Session $session
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(ManagerRegistry $userManager, $class, array $properties, ContainerInterface $container, Session $session, TokenStorageInterface $tokenStorage)
    {
        parent::__construct($userManager, $class, $properties);

        $this->container = $container;

        try
        {
            $this->client = $this->em->getRepository('GeoksApiBundle:Client')->findOneBy(array(
                'secret' => $this->container->getParameter('api_client_secret')
            ));
        } catch (\Exception $exception)
        {
            $this->container->get('monolog.logger.doctrine')->error('No Client Table');
        }

        $this->session = $session;
        $this->tokenStorage = $tokenStorage;
    }

    public function getAccessToken()
    {
        return $this->accessToken->getToken();
    }

    /**
     * @param User $user
     * @param null $session
     * @throws \Exception
     */
    public function createToken($user, $session = null)
    {
        if (!$this->client) {
            throw new \Exception('Client must be defined');
        }

        if (!$this->accessToken || $this->accessToken->hasExpired()) {
            $expire = new \DateTime(
                'now + ' .
                $this->container->getParameter('fos_oauth_server.server.options')['access_token_lifetime'] .
                ' seconds'
            );

            $accessToken = new AccessToken();
            $accessToken->setUser($user);
            $accessToken->setClient($this->client);
            $accessToken->setToken(uniqid(md5($user->getUsername())));
            $accessToken->setExpiresAt($expire->format('U'));
            $accessToken->setScope('api');

            $this->em->persist($accessToken);

            if ($this->accessToken) {
                $this->em->remove($this->accessToken);
            }

            $this->accessToken = $accessToken;

            $user->setLastLogin(new \DateTime());
        }

        if ($session === true) {
            $token = new UsernamePasswordToken(
                $user,
                null,
                'secured_area',
                $user->getRoles()
            );

            $this->tokenStorage->setToken($token);
            $this->accessToken->setScope('admin');
        } else {
            $this->accessToken->setScope('api');
        }

        $this->em->flush();
    }

    public function getUsernameForApiKey($apiKey)
    {
        $this->accessToken = $this->em->getRepository('GeoksApiBundle:AccessToken')->findOneBy(array('token' => $apiKey));

        if (!$this->accessToken) {
            return null;
        }

        return $this->accessToken->getUser()->getUsername();
    }

    public function loadUserByUsername($username, $session = null)
    {
        /** @var User $user */
        $user = $this->findUser(array('username' => $username));

        $this->accessToken = $this->em->getRepository('GeoksApiBundle:AccessToken')->findOneBy(array('user' => $user));

        $this->createToken($user, $session);

        return $user;
    }

    public function loadByFacebookToken($facebookToken)
    {
        $user = $this->container->get('geoks.api.oauth.facebook_extension')->checkGrantExtension(
            $this->client,
            ['facebook_token' => $facebookToken],
            ['GET']
        );

        if ($user === false) {
            return false;
        }

        $this->createToken($user);

        return $user;
    }
}
