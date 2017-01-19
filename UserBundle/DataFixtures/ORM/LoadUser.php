<?php

namespace Geoks\UserBundle\DataFixtures\ORM;

use AppBundle\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\File;

class LoadUser extends AbstractFixture implements OrderedFixtureInterface , ContainerAwareInterface {

    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager)
    {
        $user = new User();

        $user->setEmail('admin@geoks-dev.fr');
        $user->setFirstname('Admin');
        $user->setLastname('Admin');
        $user->setPassword('admin');
        $user->addRole('ROLE_SUPER_ADMIN');

        $manager->persist($user);
        $manager->flush();
    }

    public function getOrder()
    {
        return 1;
    }
}
