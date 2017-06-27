<?php
namespace Rzeka\DataHandler\Tests;

class Constraint extends \Symfony\Component\Validator\Constraint
{
    /**
     * @param string $errorCode
     *
     * @return string
     */
    public static function getErrorName($errorCode)
    {
        return $errorCode;
    }
}
