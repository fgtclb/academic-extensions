<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Domain\Model\Dto;

enum ActiveState: string
{
    case ALL = 'all';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';

    public static function default(): self
    {
        return self::ALL;
    }

    public static function tryFromDefault(string $value): self
    {
        return self::tryFrom($value) ?? self::ALL;
    }

    /**
     * The state of one project: active without an end date or with one still ahead,
     * completed once it has passed. It is the rule `ProjectRepository::findByDemand()`
     * filters by, so a project a filter lists is in the state of that filter. A project
     * is never in the state `ALL`.
     *
     * The query compares the stored timestamp, while Extbase may hand over the end date
     * set to midnight (`extbase.consistentDateTimeHandling`, the default on TYPO3 v14).
     * Both agree for an end date as the backend stores it, at local midnight. An imported
     * timestamp with a time of day can read as completed here while the "Active" filter
     * still lists it, until that day is over.
     */
    public static function fromEndDate(?\DateTimeInterface $endDate, \DateTimeInterface $now): self
    {
        if ($endDate === null || $endDate > $now) {
            return self::ACTIVE;
        }
        return self::COMPLETED;
    }

    /**
     * @return string[]
     */
    public static function values(): array
    {
        $values = [];
        foreach (self::cases() as $case) {
            $values[] = $case->value;
        }
        return $values;
    }
}
