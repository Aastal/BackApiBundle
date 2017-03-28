<?php

namespace Geoks\ApiBundle\Utils;

class RegexUtils
{
    public function findEmail($text)
    {
        foreach (preg_split('/\s/', $text) as $token) {
            $email = filter_var(filter_var($token, FILTER_SANITIZE_EMAIL), FILTER_VALIDATE_EMAIL);

            if ($email) {
                $text = str_replace($email, '*****', $text);
            }
        }

        return $text;
    }

    public function findPhone($text)
    {
        // ex: 06 00 11 22 33
        preg_match_all('/([0-9]{1}[\s][0-9]{2}[\s][0-9]{2}[\s][0-9]{2}[\s][0-9]{2}|[0-9]{2}[\s][0-9]{2}[\s][0-9]{2}[\s][0-9]{2}[\s][0-9]{2})/', $text, $matches);

        foreach ($matches as $key => $value) {
            $text = str_replace($value, '**********', $text);
        }

        // ex: 010-1234010, 010 1234010, 010 123 4010, 0101234010, 010-010-0100, +33644223399, +33 6 44 22 33 99
        preg_match_all('/[0-9]{3}[\-][0-9]{6}|[0-9]{3}[\s][0-9]{6}|[0-9]{3}[\s][0-9]{3}[\s][0-9]{4}|[0-9]{9,13}|[0-9]{3}[\-][0-9]{3}[\-][0-9]{4}/', $text, $matches);

        foreach ($matches as $key => $value) {
            $text = str_replace($value, '**********', $text);
        }

        return $text;
    }
}