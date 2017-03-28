<?php

namespace Geoks\ApiBundle\Utils;

class StringManager
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
}