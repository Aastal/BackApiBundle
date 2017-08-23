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

    public function fromCamelCase($input)
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];

        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }

        return implode('_', $ret);
    }

    public function validateDate($date)
    {
        $d = \DateTime::createFromFormat('d/m/Y H:i:s', $date);

        if ($d && $d->format('d/m/Y H:i:s') === $date) {
            return $d;
        }

        $d = \DateTime::createFromFormat('d-m-Y H:i:s', $date);

        if ($d && $d->format('d/m/Y H:i:s') === $date) {
            return $d;
        }

        $d = \DateTime::createFromFormat('d/m/Y', $date);

        if ($d && $d->format('d/m/Y') === $date) {
            return $d;
        }

        $d = \DateTime::createFromFormat('d-m-Y', $date);

        if ($d && $d->format('d/m/Y') === $date) {
            return $d;
        }

        $d = \DateTime::createFromFormat('Y-m-d H:i:s', $date);

        if ($d && $d->format('Y-m-d H:i:s') === $date) {
            return $d;
        }

        return false;
    }

    public function removeAccents($string)
    {
        $alphabet = [
            'Š'=>'S', 'š'=>'s', 'Ð'=>'Dj','Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A',
            'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I',
            'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U',
            'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss','à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a',
            'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i',
            'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u',
            'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y', 'ƒ'=>'f'
        ];

        $string = strtr($string, $alphabet);

        return $string;
    }

    public function pluralize($word)
    {
        $endLetter = substr($word, -1);

        switch ($endLetter)
        {
            case 'y':
                $word = mb_substr($word, 0, -1);
                $word = $word . 'ies';
                break;
            case 'o':
                $word = $word . 'es';
                break;
            default:
                $notEnd = true;
                break;
        }

        if (isset($notEnd)) {
            $endLetter = substr($word, -2);

            switch ($endLetter)
            {
                case 'an':
                    $word = mb_substr($word, 0, -2);
                    $word = $word . 'en';
                    break;
                case 'ss':
                case 'us':
                case 'sh':
                case 'nx':
                case 'ix':
                case 'is':
                case 'ch':
                case 'ex':
                    $word = $word . 'es';
                    break;
                default:
                    $word = $word . 's';
                    break;
            }
        }

        return $word;
    }
}
