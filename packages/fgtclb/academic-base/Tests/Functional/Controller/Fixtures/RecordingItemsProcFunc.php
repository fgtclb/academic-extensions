<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Controller\Fixtures;

/**
 * An `itemsProcFunc` handler that keeps the parameters it was called with, so a test reads
 * what the caller handed over. It adds no item.
 */
final class RecordingItemsProcFunc
{
    /**
     * @var array<string, mixed>|null
     */
    public static ?array $parameters = null;

    /**
     * @param array<string, mixed> $parameters
     */
    public function itemsProcFunc(array &$parameters): void
    {
        self::$parameters = $parameters;
    }
}
