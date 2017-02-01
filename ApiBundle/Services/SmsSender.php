<?php

namespace Geoks\ApiBundle\Services;

class SmsSender
{
    private $apiUrl;
    private $apiKey;
    private $logger;
    private $activationCode;
    private $num;

    public function __construct($apiUrl, $apiKey, $logger)
    {
        $this->apiUrl = $apiUrl;
        $this->apiKey = $apiKey;
        $this->logger = $logger;
    }

    public function init($num, $activationCode)
    {
        $this->num = $num;
        $this->activationCode = $activationCode;

        return $this;
    }

    public function send()
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
            'keyid' => $this->apiKey,
            'sms' => '[Les Robins] Votre code	d\'activation : ' . $this->activationCode,
            'num' => $this->num,
            'nostop' => 1
        )));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        if ($response === false) {
            $this->logger->error('An error occurred for num : ' . $this->num . ' error : ' . curl_error($ch));
        } else {
            $this->logger->info('SMS send for num' . $this->num);
        }

        curl_close($ch);

        return $response;
    }
}