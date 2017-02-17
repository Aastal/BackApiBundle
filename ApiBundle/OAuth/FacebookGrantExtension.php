<?php

namespace Geoks\ApiBundle\OAuth;

use Geoks\ApiBundle\Entity\AccessToken;
use Geoks\UserBundle\Entity\User;
use FOS\OAuthServerBundle\Storage\GrantExtensionInterface;
use OAuth2\Model\IOAuth2Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\EntityManager;
use \Facebook;

class FacebookGrantExtension implements GrantExtensionInterface
{
    private $container;
    private $entityManager;

    public function __construct(ContainerInterface $container, EntityManager $entityManager)
    {
        $this->container = $container;
        $this->entityManager = $entityManager;
    }

    /*
     * {@inheritdoc}
     */
    public function checkGrantExtension(IOAuth2Client $client, array $inputData, array $authHeaders)
    {
        $userRepository = $this->container->getParameter('geoks_api.user_class');
        $facebookToken = $inputData['facebook_token'];

        $fb = new Facebook\Facebook([
            'app_id' => $this->container->getParameter('facebook_app_id'),
            'app_secret' => $this->container->getParameter('facebook_app_secret'),
            'default_graph_version' => $this->container->getParameter('facebook_app_version'),
        ]);

        try
        {
            $response = $fb->get('/me', $facebookToken);
        }
        catch (Facebook\Exceptions\FacebookResponseException $e)
        {
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        }
        catch (Facebook\Exceptions\FacebookSDKException $e)
        {
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }

        $me = $response->getGraphUser();

        if ($me !== null) {

            /** @var User $user */
            $user = $this->entityManager->getRepository($userRepository)->findOneByFacebookUid($me->getId());

            if ($user) {
                if (!$user->getFacebookName()) {
                    $user->setFacebookName($me->getName());

                    $this->entityManager->flush();
                }

                return $user;
            }
        }

        return false;
    }
}