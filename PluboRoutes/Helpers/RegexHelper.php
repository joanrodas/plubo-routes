<?php
namespace PluboRoutes\Helpers;

abstract class RegexHelper
{
    const DIGIT = '([0-9])';
    const NUMBER = '([0-9]+)';
    const WORD = '([a-zA-Z]+)';
    const TEXT = '([A-za-z0-9-%]+)';
    const DATE = '(\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01]))';
    const YEAR = '(\d{4})';
    const MONTH = '(0[1-9]|1[0-2])';
    const DAY = '(0[1-9]|[12][0-9]|3[01])';
    const IP = '(([0-9]{1,3}\.){3}[0-9]{1,3})';
    const JWT = '((?:[\w-]*\.){2}[\w-]*)';
    const SLUG = '([a-z0-9-]+)';
    const EMAIL = '([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}';

    const AVAILABLE_REGEX = [
        'number' => self::NUMBER,
        'word' => self::WORD,
        'text' => self::TEXT,
        'date' => self::DATE,
        'slug' => self::SLUG,
        'digit' => self::DIGIT,
        'year' => self::YEAR,
        'month' => self::MONTH,
        'day' => self::DAY,
        'jwt' => self::JWT,
        'email' => self::EMAIL,
        'ip' => self::IP
    ];

    /**
     * Get translated Regex path for an endpoint route.
     *
     * @param string $path
     */
    public static function getRegexMatches(string $regex_path)
    {
        preg_match_all('#\{(.+?)\}#', $regex_path, $matches);
        return $matches;
    }

    /**
     * Return trimmed path.
     *
     * @param string $path
     * @return string
     */
    public static function cleanPath(string $path)
    {
        return ltrim(trim($path), '/');
    }

    /**
     * Return regex of path.
     *
     * @param mixed $type
     * @return string
     */
    abstract public static function getRegex($type): string;
}
