<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBiteJobs\Enumeration;

/**
 * The views the job list renders, backed by the value the plugin FlexForm stores.
 */
enum ListView: string
{
    case LIST = 'List';
    case CARD = 'Card';
    case TABLE = 'Table';

    /**
     * Resolves a stored view value, including the values stored before 2.1
     * (`ListView`, `CardView`, `TableView`). An empty or unknown value is the list view.
     */
    public static function fromStoredValue(string $value): self
    {
        if (str_ends_with($value, 'View')) {
            $value = substr($value, 0, -4);
        }

        return self::tryFrom($value) ?? self::LIST;
    }
}
