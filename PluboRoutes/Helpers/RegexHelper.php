<?php

namespace PluboRoutes\Helpers;

abstract class RegexHelper
{
    const DIGIT = '([0-9])';
    const NUMBER = '([0-9]+)';
    const WORD = '([a-zA-Z]+)';
    const TEXT = '([A-za-z0-9-%]+)';
    const ALPHANUMERIC = '([a-zA-Z0-9]+)';
    const HEXADECIMAL = '([a-fA-F0-9]+)';
    const UUID = '([a-fA-F0-9]{8}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{12})';
    const FILE_PATH = '([\/\w\.-]+)';
    const DATE = '(\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01]))';
    const YEAR = '(\d{4})';
    const MONTH = '(0[1-9]|1[0-2])';
    const DAY = '(0[1-9]|[12][0-9]|3[01])';
    const IP = '(([0-9]{1,3}\.){3}[0-9]{1,3})';
    const JWT = '((?:[\w-]*\.){2}[\w-]*)';
    const SLUG = '([a-z0-9-]+)';

    const AVAILABLE_REGEX = [
        'digit' => self::DIGIT,
        'number' => self::NUMBER,
        'word' => self::WORD,
        'text' => self::TEXT,
        'alphanumeric' => self::ALPHANUMERIC,
        'hex' => self::HEXADECIMAL,
        'uuid' => self::UUID,
        'file' => self::FILE_PATH,
        'date' => self::DATE,
        'year' => self::YEAR,
        'month' => self::MONTH,
        'day' => self::DAY,
        'ip' => self::IP,
        'jwt' => self::JWT,
        'slug' => self::SLUG,
    ];

    /**
     * Get translated Regex path for an endpoint route.
     *
     * @param string $path
     */
    public function getRegexMatches(string $regex_path)
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
    public function cleanPath(string $path)
    {
        return ltrim(trim($path), '/');
    }

    /**
     * Return regex of path.
     *
     * @param mixed $type
     * @return string
     */
    abstract public function getRegex($type): string;
}
