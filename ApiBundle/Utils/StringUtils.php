<?php

namespace Geoks\ApiBundle\Utils;

class StringUtils
{
    /**
     * @param string $delimiter
     * @param string $string
     * @return string
     */
    public function getEndOfString($delimiter, $string)
    {
        $string = explode($delimiter, $string);
        $string = end($string);

        return $string;
    }

    function fromCamelCase($input)
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];

        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }

        return implode('_', $ret);
    }
}