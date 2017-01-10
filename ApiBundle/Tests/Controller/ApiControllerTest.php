<?php

namespace Geoks\ApiBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiControllerTest extends WebTestCase
{
    /**
     * @var string
     */
    private $apiV1 = "/api/v1";

    /**
     * @var array
     */
    private $entities;

    /**
     * @var string
     */
    private $token;

    public function setUp()
    {
        $client = static::createClient();

        $container = $client->getContainer();
        $this->entities = $container->getParameter('geoks_api.tests.entities');

        $client->request('POST', $this->apiV1 . '/sessions/login', [
            'email' => 'test@gmail.com',
            'password' => 'testtest'
        ]);

        $response = $client->getResponse();

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $json = \GuzzleHttp\json_decode($response->getContent(), true);

        $this->assertArrayHasKey('accessToken', $json);
        $this->token = $json['accessToken'];
    }

    public function testAllGetters()
    {
        $client = static::createClient();

        foreach ($this->entities as $entity) {
            $client->request('GET', $this->apiV1 . '/' . $entity . '?access_token=' . $this->token);

            $this->assertEquals(200, $client->getResponse()->getStatusCode());
        }
    }
}
