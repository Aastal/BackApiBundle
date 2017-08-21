<?php
namespace Geoks\AdminBundle\EventListener\Doctrine\User;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Geoks\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;

class UserSubscriber implements EventSubscriber
{
    /**
     * @var UserPasswordEncoder
     */
    private $passwordEncoder;

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

    public function __construct(UserPasswordEncoder $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    public function preUpdate(LifecycleEventArgs $args)
    {
        $this->index($args);
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $user = $args->getEntity();

        if (is_subclass_of($user, 'Geoks\UserBundle\Entity\User') || $user instanceof User) {

            if (!$user->getUsername()) {
                $user->setUsername($user->getEmail());
                $user->setUsernameCanonical($user->getEmail());
            }

            if ($user->getPassword() && strlen($user->getSalt()) == 0) {
                $encoded = $this->passwordEncoder->encodePassword($user, $user->getPassword());

                $user->setPassword($encoded);
            }

        }
    }

    public function index(LifecycleEventArgs $args)
    {
        $user = $args->getEntity();

        if (is_subclass_of($user, 'Geoks\UserBundle\Entity\User') || $user instanceof User) {
            if ($user->getPlainPassword() && strlen($user->getSalt()) == 0) {
                $encoded = $this->passwordEncoder->encodePassword($user, $user->getPlainPassword());

                $user->setPassword($encoded);
            }
        }
    }
}
