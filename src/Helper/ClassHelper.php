<?php

namespace biliboobrian\lumenAngularCodeGenerator\Helper;

/**
 * Class ClassHelper
 * @package biliboobrian\lumenAngularCodeGenerator\Helper
 */
class ClassHelper
{
    /**
     * @param string $fullClassName
     * @return string
     */
    public static function getShortClassName($fullClassName)
    {
        $pieces = explode('\\', $fullClassName);

        return end($pieces);
    }
}
