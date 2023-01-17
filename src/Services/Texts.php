<?php

namespace App\Services;

use Michelf\Markdown;

class Texts
{
    /**
     * Returns a substring of $string that is $max_length length max and doesn't split
     * a word or a html tag
     */
    public function truncate($string, $max_length)
    {
        $response = '';
        $token = '';

        $string = preg_replace('/\s+/', ' ', $string);

        while (strlen($token . $string) > 0 && strlen($response . $token) < $max_length) {
            $response = $response . $token;
            $matches = array();

            if (preg_match('/^(<.+?>)(.*)/', $string, $matches)) {
                $token = $matches[1];
                $string = $matches[2];
            } elseif (preg_match('/^([^\s]+\s*)(.*)/', $string, $matches)) {
                $token = $matches[1];
                $string = $matches[2];
            } else {
                $token = $string;
                $string = '';
            }
        }
        if (strlen($token) > 0) {
            $response = $response . '[&hellip;]';
        }

        return $response;
    }

    /**
     * Returns the processed version of a Markdown text
     */
    public function markdown($string)
    {
        return Markdown::defaultTransform($string);
    }
}
