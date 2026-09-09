<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Date;

/**
 * Which end of a period completes a date part the editor was not asked for:
 * the first or the last month of the year, the first or the last day of the
 * month.
 *
 * @internal not part of public API.
 */
enum DateCompletionEdge: string
{
    case FIRST = 'first';
    case LAST = 'last';

    public static function default(): self
    {
        return self::FIRST;
    }

    public static function tryFromDefault(string $value): self
    {
        return self::tryFrom(strtolower(trim($value))) ?? self::default();
    }
}
