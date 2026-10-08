<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Persistence;

use FGTCLB\AcademicBase\EventListener\LiftVisibilityForHiddenRecordsOverlay;
use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\VisibilityAspect;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\SelectorInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * Executes and fetches Extbase queries that include hidden records, for the plugin
 * option "Show hidden records", so that a translated page shows the translation of a
 * hidden record (ACE-857).
 *
 * Ignoring the hidden flag in the query settings reaches the query itself only. Extbase
 * overlays the translation through `PageRepository`, which follows the visibility aspect
 * of the context. On TYPO3 v13 the hidden translation of a hidden record is therefore not
 * found, and the record is rendered in the default language. TYPO3 v14.3.7 mirrors the
 * ignored enable fields of the query settings into the context of the overlay itself, so
 * there the fetcher leaves everything to the core.
 *
 * - {@see self::execute()} replaces `$query->execute()` in a repository. On v13, for a
 *   query that includes hidden records, it returns a {@see HiddenRecordsQueryResult},
 *   which fetches lazily, like any query result, within the overlay window.
 * - {@see self::fetch()} fetches a result someone else executed within that window, the
 *   page a `QueryResultPaginator` executes on its own.
 *
 * The window marks the context with the queried table, and
 * {@see LiftVisibilityForHiddenRecordsOverlay} lifts the visibility for the overlay of
 * that query only, so the relations of the mapped objects keep the visibility of the
 * request, as on v14.
 *
 * @todo Remove together with the support of TYPO3 v13.
 * @internal only to be used within `EXT:academic_*` extensions and not part of public API.
 */
final readonly class HiddenRecordsFetcher
{
    public function __construct(
        private Context $context,
        private ContainerInterface $container,
    ) {}

    /**
     * @template T of object
     * @param QueryInterface<T> $query
     * @return QueryResultInterface<int, T>
     */
    public function execute(QueryInterface $query): QueryResultInterface
    {
        if (!$this->needsOverlayWindow($query)) {
            return $query->execute();
        }
        /** @var HiddenRecordsQueryResult<T> $result */
        $result = $this->container->get(HiddenRecordsQueryResult::class);
        $result->setQuery($query);
        return $result;
    }

    /**
     * Fetch a result within the overlay window when its query includes hidden records.
     * Iterating the result later reuses the fetched objects.
     *
     * @template T of object
     * @param QueryResultInterface<int, T> $result
     */
    public function fetch(QueryResultInterface $result): void
    {
        $query = $result->getQuery();
        if (!$this->needsOverlayWindow($query)) {
            return;
        }
        $this->withinOverlayWindow($query, static fn(): array => $result->toArray());
    }

    /**
     * Run a fetch of the query with the context marked for the overlay listener, and
     * remove the marker afterwards. A fetch that fails between the two events of the
     * listener leaves the visibility lifted, it is restored here.
     *
     * @template T of object
     * @template R
     * @param QueryInterface<T> $query
     * @param callable(): R $fetch
     * @return R
     */
    public function withinOverlayWindow(QueryInterface $query, callable $fetch): mixed
    {
        $source = $query->getSource();
        if (!$source instanceof SelectorInterface) {
            throw new \LogicException(
                sprintf('Hidden records can only be fetched for a query on a single table, "%s" given.', get_class($source)),
                1791480032
            );
        }
        $previousMarker = $this->context->hasAspect(HiddenRecordsOverlayAspect::NAME)
            ? $this->context->getAspect(HiddenRecordsOverlayAspect::NAME)
            : null;
        $this->context->setAspect(
            HiddenRecordsOverlayAspect::NAME,
            new HiddenRecordsOverlayAspect($source->getSelectorName()),
        );
        try {
            return $fetch();
        } finally {
            $visibilityBeforeOverlay = $this->context->getPropertyFromAspect(
                HiddenRecordsOverlayAspect::NAME,
                'visibilityBeforeOverlay',
            );
            if ($visibilityBeforeOverlay instanceof VisibilityAspect) {
                $this->context->setAspect('visibility', $visibilityBeforeOverlay);
            }
            if ($previousMarker !== null) {
                $this->context->setAspect(HiddenRecordsOverlayAspect::NAME, $previousMarker);
            } else {
                $this->context->unsetAspect(HiddenRecordsOverlayAspect::NAME);
            }
        }
    }

    /**
     * @template T of object
     * @param QueryInterface<T> $query
     */
    private function needsOverlayWindow(QueryInterface $query): bool
    {
        if ((new Typo3Version())->getMajorVersion() >= 14) {
            return false;
        }
        $querySettings = $query->getQuerySettings();
        return $querySettings->getIgnoreEnableFields()
            && in_array('disabled', $querySettings->getEnableFieldsToBeIgnored(), true);
    }
}
