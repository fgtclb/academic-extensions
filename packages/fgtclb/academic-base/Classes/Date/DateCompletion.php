<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Date;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * The rule that turns the parts an editor was asked for into a complete
 * calendar date.
 *
 * The two axes express the four rules by name: `month: FIRST` is the first
 * month of the year and `month: LAST` the last one, `day: FIRST` the first day
 * of the month and `day: LAST` the last one. A part the editor did give is
 * never touched.
 *
 * @internal not part of public API.
 */
#[Exclude]
final class DateCompletion
{
    public function __construct(
        public readonly DateCompletionEdge $month = DateCompletionEdge::FIRST,
        public readonly DateCompletionEdge $day = DateCompletionEdge::FIRST,
    ) {}

    /**
     * @param array{month?: DateCompletionEdge, day?: DateCompletionEdge} $array
     */
    public static function __set_state(array $array): self
    {
        return new self(
            month: $array['month'] ?? DateCompletionEdge::FIRST,
            day: $array['day'] ?? DateCompletionEdge::FIRST,
        );
    }

    /**
     * Complete the parts that were not given. A `null` month becomes January
     * or December, a `null` day the first or the last day of the resulting
     * month - so a `LAST` day of a `LAST` month of 2019 is the 31st of
     * December and not the 31st of January.
     */
    public function complete(int $year, ?int $month, ?int $day): \DateTimeImmutable
    {
        $month ??= $this->month === DateCompletionEdge::LAST ? 12 : 1;
        $date = (new \DateTimeImmutable())
            ->setDate($year, $month, 1)
            ->setTime(0, 0, 0);
        if ($day === null) {
            return $this->day === DateCompletionEdge::LAST
                ? $date->setDate($year, $month, (int)$date->format('t'))
                : $date;
        }
        return $date->setDate($year, $month, $day);
    }
}
