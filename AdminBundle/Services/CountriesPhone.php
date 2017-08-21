<?php

namespace Geoks\AdminBundle\Services;

class CountriesPhone
{
    public function getPhones()
    {
        $list = [
          ['nom' => 'France', 'countryCode' => 'fr', 'dialCode' => '33'],
          ['nom' => 'United Kingdom', 'countryCode' => 'gb', 'dialCode' => '44'],
          ['nom' => 'United States', 'countryCode' => 'us', 'dialCode' => '1']
        ];

        return $list;
    }
}
