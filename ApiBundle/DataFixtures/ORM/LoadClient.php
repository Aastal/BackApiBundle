<?php

namespace AppBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Geoks\ApiBundle\Entity\Client;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;

class LoadClient extends AbstractFixture implements OrderedFixtureInterface , ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager)
    {
        $name = $this->container->getParameter('geoks_admin.app_name');

        $redirect = $this->container->getParameter('base_url');
        $grants = ["password", "refresh_token", $this->container->getParameter('facebook_grant_type')];
        $root = $this->container->getParameter('kernel.root_dir');

        $parameters = [$root . '/config/parameters.yml', $root . '/config/parameters.yml.dist'];

        $clientManager = $this->container->get('fos_oauth_server.client_manager.default');

        $client = $clientManager->createClient();

        $client->setName($name);
        $client->setRedirectUris([$redirect]);
        $client->setAllowedGrantTypes($grants);

        $manager->persist($client);
        $manager->flush();

        $dumper = new Dumper();
        $yaml = new Parser();

        foreach ($parameters as $parameter) {
            $array = $yaml->parse(file_get_contents($parameter));

            foreach ($array as &$a) {
                foreach ($a as $key => &$value) {
                    if ($key == "api_client_id") {
                        $value = $client->getPublicId();
                    }

                    if ($key == "api_client_secret") {
                        $value = $client->getSecret();
                    }
                }
            }

            $data = $dumper->dump($array, 4);
            file_put_contents($parameter, $data);
        }
    }

    public function getOrder()
    {
        return 1;
    }
}
