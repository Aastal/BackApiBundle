<?php

namespace Geoks\ApiBundle\Services;

class Pluralization
{
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