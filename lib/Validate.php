<?php
namespace App;

/**
 * Validação e saneamento de parâmetros de entrada.
 * Lança InvalidArgumentException em caso de falha.
 */
final class Validate
{
    private const ISO_639_1 = ['pt','en','es','fr','de','it','ru','ja','ko','zh','ar','nl','pl','tr','sv','fi','no','da'];
    private const ISO_3166_1 = ['BR','US','GB','CA','AU','DE','FR','ES','IT','PT','MX','AR','JP','KR','CN'];

    public static function topic(string $s): string {
        $s = trim($s);
        if (mb_strlen($s) < 2 || mb_strlen($s) > 80) {
            throw new \InvalidArgumentException('topic length must be between 2 and 80 chars.');
        }
        return $s;
    }

    public static function lang(?string $s): string {
        $s = strtolower(trim((string)$s));
        if ($s === '') return 'en';
        if (!in_array($s, self::ISO_639_1, true)) throw new \InvalidArgumentException('invalid ISO 639-1 language.');
        return $s;
    }

    public static function country(?string $s): string {
        $s = strtoupper(trim((string)$s));
        if ($s === '') return 'US';
        if (!in_array($s, self::ISO_3166_1, true)) throw new \InvalidArgumentException('invalid ISO 3166-1 alpha-2 country.');
        return $s;
    }

    public static function pageToken(?string $s): ?string {
        if ($s === null || $s === '') return null;
        if (!preg_match('/^[A-Za-z0-9_-]{1,200}$/', $s)) throw new \InvalidArgumentException('invalid pageToken format.');
        return $s;
    }
}
