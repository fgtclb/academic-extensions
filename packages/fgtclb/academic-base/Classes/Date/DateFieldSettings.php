<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Date;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * One date field's configuration: how much of a date its editor is asked for,
 * how the parts nobody was asked for are completed, and how much of the result
 * a visitor is shown.
 *
 * A field that is not a date carries the neutral default, so a consumer never
 * has to ask whether the object exists.
 *
 * @internal not part of public API.
 */
#[Exclude]
final class DateFieldSettings
{
    public function __construct(
        public readonly DateDisplay $display = new DateDisplay(),
        public readonly DateGranularity $granularity = DateGranularity::DATE,
        public readonly DateCompletion $completion = new DateCompletion(),
    ) {}

    public static function default(): self
    {
        return new self();
    }

    /**
     * @param array{display?: DateDisplay, granularity?: DateGranularity, completion?: DateCompletion} $array
     */
    public static function __set_state(array $array): self
    {
        return new self(
            display: $array['display'] ?? new DateDisplay(),
            granularity: $array['granularity'] ?? DateGranularity::DATE,
            completion: $array['completion'] ?? new DateCompletion(),
        );
    }

    /**
     * Whether a visitor is shown less than the editor is asked for, which is
     * when the editor deserves to be told so at the field.
     */
    public function publishesLessThanItAsks(): bool
    {
        $asksForMonth = $this->granularity !== DateGranularity::YEAR;
        $asksForDay = $this->granularity === DateGranularity::DATE;
        return (!$this->display->year)
            || ($asksForMonth && !$this->display->month)
            || ($asksForDay && !$this->display->day);
    }
}
