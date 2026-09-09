<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Date;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * Which parts of a stored date a visitor is shown. The three are independent:
 * a field may publish the year alone while its editor enters a full date, and
 * that is the case the dropped `year_only` column existed for.
 *
 * The parts become an ICU skeleton rather than a format string, so the order
 * and the separators are the locale's own and never ours.
 *
 * @internal not part of public API.
 */
#[Exclude]
final class DateDisplay
{
    public function __construct(
        public readonly bool $year = true,
        public readonly bool $month = true,
        public readonly bool $day = true,
    ) {}

    /**
     * @param array{year?: bool, month?: bool, day?: bool} $array
     */
    public static function __set_state(array $array): self
    {
        return new self(
            year: $array['year'] ?? true,
            month: $array['month'] ?? true,
            day: $array['day'] ?? true,
        );
    }

    public function isEmpty(): bool
    {
        return !$this->year && !$this->month && !$this->day;
    }

    public function isComplete(): bool
    {
        return $this->year && $this->month && $this->day;
    }

    /**
     * The ICU skeleton of the shown parts. A month standing on its own is
     * spelled out, a month next to another part abbreviated.
     */
    public function skeleton(): string
    {
        $monthOnly = $this->month && !$this->year && !$this->day;
        return ($this->year ? 'y' : '')
            . ($this->month ? ($monthOnly ? 'MMMM' : 'MMM') : '')
            . ($this->day ? 'd' : '');
    }
}
