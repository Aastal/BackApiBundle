<?php
namespace Geoks\AdminBundle\EventListener\User;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Geoks\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UserSubscriber implements EventSubscriber
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            'preUpdate',
            'prePersist'
        );
    }

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function preUpdate(LifecycleEventArgs $args)
    {
        $this->index($args);
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $user = $args->getEntity();

        if ($user instanceof User) {
            if (!$user->getUsername()) {
                $user->setUsername($user->getEmail());
                $user->setUsernameCanonical($user->getEmail());
            }

            if ($user->getPassword()) {
                $encoder = $this->container->get('security.password_encoder');
                $encoded = $encoder->encodePassword($user, $user->getPassword());

                $user->setPassword($encoded);
            }
        }
    }

    public function index(LifecycleEventArgs $args)
    {
        $user = $args->getEntity();

        if ($user instanceof User) {
            if ($user->getPlainPassword()) {
                $encoder = $this->container->get('security.password_encoder');
                $encoded = $encoder->encodePassword($user, $user->getPlainPassword());

                $user->setPassword($encoded);
            }

            if ($user->getPassword()) {
                $encoder = $this->container->get('security.password_encoder');
                $encoded = $encoder->encodePassword($user, $user->getPassword());

                $user->setPassword($encoded);
            }
        }
    }
}
