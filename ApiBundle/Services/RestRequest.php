<?php

namespace Geoks\ApiBundle\Services;

use OAuth2\Model\IOAuth2Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RestRequest
{
    private $tokenUrl;
    private $accessToken;
    private $refreshToken;
    private $clientId;
    private $clientSecret;
    private $facebookGrantType;
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->clientId = $this->container->getParameter('api_client_id');
        $this->clientSecret = $this->container->getParameter('api_client_secret');
        $this->facebookGrantType = $this->container->getParameter('facebook_grant_type');
        $this->tokenUrl = $this->container->getParameter('base_url') . '/oauth/v2/token';
    }

    /**
     * Get accessToken
     * @return
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * Set accessToken
     * @return $this
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
        return $this;
    }

    /**
     * Get refreshToken
     * @return
     */
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    /**
     * Set refreshToken
     * @return $this
     */
    public function setRefreshToken($refreshToken)
    {
        $this->refreshToken = $refreshToken;
        return $this;
    }

    /**
     * @param $username
     * @param $password
     */
    public function login($username, $password)
    {
        $params = array(
            'client_id'	=>	$this->clientId,
            'client_secret'	=>	$this->clientSecret,
            'username'	=>	$username,
            'password'	=>	$password,
            'grant_type'=>	'password'
        );

        $result = $this->call($this->tokenUrl, 'GET', $params);

        $this->accessToken = $result->access_token;
        $this->refreshToken = $result->refresh_token;
    }

    /**
     * @param IOAuth2Client $client
     * @param $facebookToken
     * @return string
     */
    public function loginFacebook($client, $facebookToken)
    {
        $user = $this->container->get('geoks.api.oauth.facebook_extension')->checkGrantExtension(
            $client,
            ['facebook_token' => $facebookToken],
            ['GET']
        );

        if ($user === false) {
            return false;
        }

        $params = array(
            'client_id'	=> $this->clientId,
            'client_secret'	=> $this->clientSecret,
            'facebook_token' =>	$facebookToken,
            'grant_type' =>	$this->facebookGrantType
        );

        $result = $this->call($this->tokenUrl, 'GET', $params);

        $this->accessToken = $result->access_token;
        $this->refreshToken = $result->refresh_token;

        return $user;
    }

    /**
     * @param $refreshToken
     */
    public function refreshToken($refreshToken)
    {
        $params = array(
            'client_id'		=>  $this->clientId,
            'client_secret'	=>  $this->clientSecret,
            'refresh_token'	=>  $refreshToken,
            'grant_type'	=>  'refresh_token'
        );

        $result = $this->call($this->tokenUrl, 'GET', $params);

        $this->refreshToken = $result->refresh_token;
    }

    /**
     * @param $url
     * @param $method
     * @param array $getParams
     * @param array $postParams
     * @return mixed
     */
    private function call($url, $method, $getParams = array(), $postParams = array()){
        ob_start();
        $curl_request = curl_init();

        curl_setopt($curl_request, CURLOPT_HEADER, 0);
        curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, 1);

        $url = $url . '?' . http_build_query($getParams);

        switch(strtoupper($method))
        {
            // Create Method
            case 'POST':
                curl_setopt($curl_request, CURLOPT_URL, $url);
                curl_setopt($curl_request, CURLOPT_POST, 1);
                curl_setopt($curl_request, CURLOPT_POSTFIELDS, http_build_query($postParams));
                break;

            // Read Method
            case 'GET':
                curl_setopt($curl_request, CURLOPT_URL, $url);
                break;

            // Update Method
            case 'PUT':
                curl_setopt($curl_request, CURLOPT_URL, $url);
                curl_setopt($curl_request, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($curl_request, CURLOPT_POSTFIELDS, http_build_query($postParams));
                break;

            // Delete Method
            case 'DELETE':
                curl_setopt($curl_request, CURLOPT_URL, $url);
                curl_setopt($curl_request, CURLOPT_CUSTOMREQUEST, 'DELETE');
                curl_setopt($curl_request, CURLOPT_POSTFIELDS, http_build_query($postParams));
                break;

            default:
                curl_setopt($curl_request, CURLOPT_URL, $url);
                break;
        }

        $result = curl_exec($curl_request);

        if($result === false) {
            $result = curl_error($curl_request);
        }

        curl_close($curl_request);
        ob_end_flush();

        return json_decode($result);
    }
}