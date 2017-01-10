<?php

namespace Geoks\AdminBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminControllerTest extends WebTestCase
{
    /**
     * @var string
     */
    private $prefix = '/admin/fr';

    public function testLogin()
    {
        $client = static::createClient();

        $client->request('GET', $this->prefix . '/login');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testPostLogin()
    {
        $client = static::createClient();

        $client->request('POST', $this->prefix . '/login/check', [
            'email' => 'test@gmail.com',
            'password' => 'testtest'
        ]);

        $this->assertEquals(302, $client->getResponse()->getStatusCode());
    }
}
