<?php
namespace PluboRoutes\Helpers;

class RegexHelperRoutes extends RegexHelper
{
    public static function getRegex($type): string
    {
        return array_key_exists($type, self::AVAILABLE_REGEX) ? self::AVAILABLE_REGEX[$type] : $type;
    }
}
