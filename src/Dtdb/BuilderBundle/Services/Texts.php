<?php


namespace Dtdb\BuilderBundle\Services;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use \Michelf\Markdown;

class Texts
{
    /**
     * Returns a substring of $string that is $max_length length max and doesn't split
     * a word
     */
    public function truncate($string, $max_length)
    {
        $response = '';
        $token = '';
        
        while(strlen($token.$string) > 0 && strlen($response.$token) < $max_length)
        {
            $response = $response.$token;
            $matches = array();
            
            if(preg_match('/^(<.+?>)(.*)/', $string, $matches))
            {
                $token = $matches[1];
                $string = $matches[2];
            }
            else if(preg_match('/^(.+?\s)(.*)/', $string, $matches))
            {
                $token = $matches[1];
                $string = $matches[2];
            }
            else
            {
                $token = $string;
                $string = '';
            }
        }
        if(strlen($token) > 0) {
            $response = $response . '[&hellip;]';
        }
        
        return $response;
    }
    
    /**
     * Returns the processed version of a markdown text
     */
    public function markdown($string)
    {
        return Markdown::defaultTransform($string);
    }
}
