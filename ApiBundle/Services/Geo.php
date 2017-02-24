<?php

namespace Geoks\ApiBundle\Services;

class Geo
{
    /**
     * @var string
     */
    private $address;

    /**
     * @var string
     */
    private $country;

    public function init($address, $city = null, $country = null)
    {
        $this->address = $address . '+' . $city;
        $this->address = str_replace(' ', '+', $this->address);

        $this->country = $country;
    }

    public function geoLocByAddress()
    {
        if ($this->country) {
            $url = "http://maps.google.com/maps/api/geocode/json?address=$this->address&sensor=false&region=$this->country";
        } else {
            $url = "http://maps.google.com/maps/api/geocode/json?address=$this->address&sensor=false&region=France";
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $response = curl_exec($ch);
        curl_close($ch);

        $json = json_decode($response);

        return [
            'latitude' => $json->results[0]->geometry->location->lat,
            'longitude' => $json->results[0]->geometry->location->lng,
        ];
    }
}