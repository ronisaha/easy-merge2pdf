<?php

namespace EasyMerge2pdf;

use InvalidArgumentException;

abstract class InputNormalizer
{
    public static function normalize(string $file, ?string $pages): string
    {
        if (empty($pages)) {
            return $file;
        }

        return sprintf('%s~%s', $file, static::normalizePage($pages));
    }

    private static function normalizePage($pages): string
    {
        if (!str_contains($pages, '-')) {
            return $pages;
        }

        if (str_contains($pages, ',')) {
            $allPages = explode(',', $pages);
            $allPages = array_map([InputNormalizer::class, 'normalizePage'], $allPages);
            return implode(',', $allPages);
        }

        $parts = explode('-', $pages);

        if (count($parts) !== 2) {
            throw new InvalidArgumentException();
        }

        return implode(',', range($parts[0], $parts[1]));
    }
}
