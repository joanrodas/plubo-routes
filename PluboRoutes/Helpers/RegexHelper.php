<?php
namespace PluboRoutes\Helpers;

class RegexHelper
{
    const DIGIT = '([0-9])';
    const NUMBER = '([0-9]+)';
    const WORD = '([a-zA-Z]+)';
    const DATE = '(\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01]))';
    const YEAR = '(\d{4})';
    const MONTH = '(0[1-9]|1[0-2])';
    const DAY = '(0[1-9]|[12][0-9]|3[01])';
    const IP = '(([0-9]{1,3}\.){3}[0-9]{1,3})';
    const JWT = '((?:[\w-]*\.){2}[\w-]*)';
    const SLUG = '([a-z0-9-]+)';
    const EMAIL = '([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}';

    private static $available_regex = array(
        'number' => self::NUMBER,
        'word' => self::WORD,
        'date' => self::DATE,
        'slug' => self::SLUG,
        'digit' => self::DIGIT,
        'year' => self::YEAR,
        'month' => self::MONTH,
        'day' => self::DAY,
        'jwt' => self::JWT,
        'email' => self::EMAIL,
        'ip' => self::IP
    );

    public static function getRegex($type)
    {
        return in_array($type, RegexHelper::$available_regex) ? $available_regex[$type] : $type;
    }
}
