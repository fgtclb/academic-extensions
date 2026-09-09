<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Date;

/**
 * How much of a date an editor is asked for.
 *
 * There is no native year-only input in any browser, so a year is asked for
 * with a number control. The two others are the browser's own date and month
 * controls; desktop Firefox has never implemented the month one and degrades
 * it to a text field, which submits the same value.
 *
 * @internal not part of public API.
 */
enum DateGranularity: string
{
    case DATE = 'date';
    case MONTH = 'month';
    case YEAR = 'year';

    public static function default(): self
    {
        return self::DATE;
    }

    public static function tryFromDefault(string $value): self
    {
        return self::tryFrom(strtolower(trim($value))) ?? self::default();
    }

    /**
     * The HTML input type a control of this granularity carries.
     */
    public function inputType(): string
    {
        return match ($this) {
            self::DATE => 'date',
            self::MONTH => 'month',
            self::YEAR => 'number',
        };
    }

    /**
     * The date format a control of this granularity is prefilled with and
     * submits.
     */
    public function format(): string
    {
        return match ($this) {
            self::DATE => 'Y-m-d',
            self::MONTH => 'Y-m',
            self::YEAR => 'Y',
        };
    }
}
