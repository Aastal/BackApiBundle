<?php

namespace Geoks\ApiBundle\Services;

use \libphonenumber\PhoneNumber;
use \libphonenumber\PhoneNumberUtil;
use \libphonenumber\PhoneNumberFormat;
use \libphonenumber\NumberParseException;
use Monolog\Logger;

class SmsSender
{
    /**
     * @var string
     */
    private $apiUrl;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var string
     */
    private $text;

    /**
     * @var PhoneNumber
     */
    private $num;

    public function __construct($apiUrl, $apiKey, $logger)
    {
        $this->apiUrl = $apiUrl;
        $this->apiKey = $apiKey;
        $this->logger = $logger;
    }

    public function init($num, $text)
    {
        $this->num = $num;
        $this->text = $text;

        return $this;
    }

    public function send()
    {
        $phoneUtil = PhoneNumberUtil::getInstance();

        $number = $phoneUtil->format($this->num, PhoneNumberFormat::INTERNATIONAL);
        $number = str_replace(' ', '', $number);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
            'keyid' => $this->apiKey,
            'sms' => $this->text,
            'num' => $number
        )));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        if ($response === false) {
            $this->logger->error('An error occurred for num : ' . $number . ' error : ' . curl_error($ch));
        } else {
            $this->logger->info('SMS send for num ' . $number);
        }

        curl_close($ch);

        return $response;
    }
}