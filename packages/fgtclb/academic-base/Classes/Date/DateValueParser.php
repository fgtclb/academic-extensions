<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Date;

/**
 * Reads a submitted date value at the granularity a field asks for, and
 * completes the parts it did not ask for.
 *
 * Every format is read strictly: `createFromFormat()` accepts `2019-02-31` and
 * answers with the third of March, so the parsed date is formatted back and
 * compared with what came in. The full ISO format is accepted at every
 * granularity, and the German notation at the full one, so a client written
 * against the endpoint before the control became a native one keeps working.
 *
 * @internal not part of public API.
 */
final readonly class DateValueParser
{
    private const ISO_FORMAT = 'Y-m-d';
    private const GERMAN_FORMAT = 'd.m.Y';
    private const MONTH_FORMAT = 'Y-m';
    private const YEAR_FORMAT = 'Y';

    /**
     * @return ?\DateTimeImmutable `null` when the value is not a date of this
     *                             granularity - which an empty value is not
     *                             either, so a caller that means "clear the
     *                             field" checks for that before asking.
     */
    public function parse(string $value, DateGranularity $granularity, DateCompletion $completion): ?\DateTimeImmutable
    {
        foreach ($this->formatsFor($granularity) as $format) {
            $date = $this->readStrictly($value, $format);
            if (!$date instanceof \DateTimeImmutable) {
                continue;
            }
            return match ($format) {
                self::YEAR_FORMAT => $completion->complete((int)$date->format('Y'), null, null),
                self::MONTH_FORMAT => $completion->complete((int)$date->format('Y'), (int)$date->format('n'), null),
                default => $date->setTime(0, 0, 0),
            };
        }
        return null;
    }

    /**
     * @return list<string>
     */
    private function formatsFor(DateGranularity $granularity): array
    {
        return match ($granularity) {
            DateGranularity::DATE => [self::ISO_FORMAT, self::GERMAN_FORMAT],
            DateGranularity::MONTH => [self::MONTH_FORMAT, self::ISO_FORMAT],
            DateGranularity::YEAR => [self::YEAR_FORMAT, self::ISO_FORMAT],
        };
    }

    private function readStrictly(string $value, string $format): ?\DateTimeImmutable
    {
        $date = \DateTimeImmutable::createFromFormat('!' . $format, $value);
        if (!$date instanceof \DateTimeImmutable) {
            return null;
        }
        $errors = \DateTimeImmutable::getLastErrors();
        if ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
            return null;
        }
        return $date->format($format) === $value ? $date : null;
    }
}
