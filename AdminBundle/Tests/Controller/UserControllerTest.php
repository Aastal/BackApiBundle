<?php

namespace Geoks\AdminBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase
{
    /**
     * @var string
     */
    private $prefix = '/admin/fr';

    /**
     * @var array
     */
    private $entities;

    public function setUp()
    {
        $client = static::createClient();

        $container = $client->getContainer();
        $this->entities = $container->getParameter('geoks_api.tests.entities');
    }

    public function testIndexes()
    {
        $client = static::createClient();

        $client->request('POST', $this->prefix . '/login/check', [
            'email' => 'test@gmail.com',
            'password' => 'testtest'
        ]);

        foreach ($this->entities as $entity) {
            $client->request('GET', $this->prefix . '/' . $entity . '/index');

            $this->assertEquals(200, $client->getResponse()->getStatusCode());
        }
    }
}
