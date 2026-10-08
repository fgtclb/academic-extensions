<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Persistence;

use TYPO3\CMS\Core\Context\AspectInterface;
use TYPO3\CMS\Core\Context\Exception\AspectPropertyNotFoundException;
use TYPO3\CMS\Core\Context\VisibilityAspect;

/**
 * Marks the context while a result that includes hidden records is fetched, see
 * {@see HiddenRecordsFetcher::withinOverlayWindow()}: the table of that result, and,
 * while one of its queries is being fetched, that query and the visibility the context
 * had before it was lifted for the language overlay.
 *
 * It lives in the context rather than in a service, so the fetcher and the listener
 * that lifts the visibility stay stateless and share nothing but the request.
 *
 * @internal only to be used within `EXT:academic_*` extensions and not part of public API.
 */
final readonly class HiddenRecordsOverlayAspect implements AspectInterface
{
    public const NAME = 'academic-base.hidden-records-overlay';

    public function __construct(
        public string $tableName,
        public ?VisibilityAspect $visibilityBeforeOverlay = null,
        public ?int $liftedQueryId = null,
    ) {}

    public function get(string $name): mixed
    {
        return match ($name) {
            'tableName' => $this->tableName,
            'visibilityBeforeOverlay' => $this->visibilityBeforeOverlay,
            'liftedQueryId' => $this->liftedQueryId,
            default => throw new AspectPropertyNotFoundException(
                sprintf('Property "%s" not found in aspect "%s".', $name, self::NAME),
                1791480033
            ),
        };
    }
}
