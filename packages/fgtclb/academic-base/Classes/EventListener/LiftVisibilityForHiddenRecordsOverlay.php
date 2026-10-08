<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\EventListener;

use FGTCLB\AcademicBase\Persistence\HiddenRecordsFetcher;
use FGTCLB\AcademicBase\Persistence\HiddenRecordsOverlayAspect;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\VisibilityAspect;
use TYPO3\CMS\Extbase\Event\Persistence\ModifyQueryBeforeFetchingObjectDataEvent;
use TYPO3\CMS\Extbase\Event\Persistence\ModifyResultAfterFetchingObjectDataEvent;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\SelectorInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * Lifts the visibility of the context for the language overlay of a query that
 * {@see HiddenRecordsFetcher} fetches, and for nothing else, on TYPO3 v13.
 *
 * Extbase clones the context for the overlay after the first event and maps the rows,
 * eager relations included, after the second one. Lifting in between reaches the
 * overlay of the hidden translation, while the relations of the mapped objects keep the
 * visibility of the request. That is what TYPO3 v14.3.7 does itself, which mirrors the
 * ignored enable fields into the cloned context of the overlay only.
 *
 * Only a query of the table the fetcher marked that ignores the hidden flag is lifted,
 * and only the query that lifted restores. Every other query, those of the relations
 * mapped afterwards included, is left alone. A fetch that fails between the two events
 * is restored by the fetcher.
 *
 * @todo Remove together with the support of TYPO3 v13.
 * @internal only to be used within `EXT:academic_*` extensions and not part of public API.
 */
final readonly class LiftVisibilityForHiddenRecordsOverlay
{
    public function __construct(
        private Context $context,
    ) {}

    #[AsEventListener(identifier: 'academic-base/lift-visibility-for-hidden-records-overlay')]
    public function liftVisibility(ModifyQueryBeforeFetchingObjectDataEvent $event): void
    {
        $marker = $this->marker();
        $query = $event->getQuery();
        if ($marker === null
            || $marker->visibilityBeforeOverlay !== null
            || !$this->queriesHiddenRecordsOf($query, $marker->tableName)
        ) {
            return;
        }
        $visibilityAspect = $this->context->getAspect('visibility');
        if (!$visibilityAspect instanceof VisibilityAspect) {
            return;
        }
        $isPagesTable = $marker->tableName === 'pages';
        $this->context->setAspect(
            HiddenRecordsOverlayAspect::NAME,
            new HiddenRecordsOverlayAspect($marker->tableName, $visibilityAspect, spl_object_id($query)),
        );
        $this->context->setAspect('visibility', new VisibilityAspect(
            includeHiddenPages: $isPagesTable || $visibilityAspect->includeHiddenPages(),
            includeHiddenContent: !$isPagesTable || $visibilityAspect->includeHiddenContent(),
            includeDeletedRecords: $visibilityAspect->includeDeletedRecords(),
            includeScheduledRecords: $visibilityAspect->includeScheduledRecords(),
        ));
    }

    #[AsEventListener(identifier: 'academic-base/restore-visibility-after-hidden-records-overlay')]
    public function restoreVisibility(ModifyResultAfterFetchingObjectDataEvent $event): void
    {
        $marker = $this->marker();
        if ($marker === null
            || $marker->visibilityBeforeOverlay === null
            || $marker->liftedQueryId !== spl_object_id($event->getQuery())
        ) {
            return;
        }
        $this->context->setAspect('visibility', $marker->visibilityBeforeOverlay);
        $this->context->setAspect(HiddenRecordsOverlayAspect::NAME, new HiddenRecordsOverlayAspect($marker->tableName));
    }

    private function marker(): ?HiddenRecordsOverlayAspect
    {
        if (!$this->context->hasAspect(HiddenRecordsOverlayAspect::NAME)) {
            return null;
        }
        $marker = $this->context->getAspect(HiddenRecordsOverlayAspect::NAME);
        return $marker instanceof HiddenRecordsOverlayAspect ? $marker : null;
    }

    /**
     * @param QueryInterface<object> $query
     */
    private function queriesHiddenRecordsOf(QueryInterface $query, string $tableName): bool
    {
        $source = $query->getSource();
        $querySettings = $query->getQuerySettings();
        return $source instanceof SelectorInterface
            && $source->getSelectorName() === $tableName
            && $querySettings->getIgnoreEnableFields()
            && in_array('disabled', $querySettings->getEnableFieldsToBeIgnored(), true);
    }
}
