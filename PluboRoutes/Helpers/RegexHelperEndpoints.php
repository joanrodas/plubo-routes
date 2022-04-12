<?php
namespace PluboRoutes\Helpers;

class RegexHelperEndpoints extends RegexHelper
{
    public static function getRegex($type): string
    {
        $regex_code = array_key_exists($type[1], self::AVAILABLE_REGEX) ? self::AVAILABLE_REGEX[$type[1]] : $type[1];
        return "(?P<$type[0]>$regex_code)";
    }
}
