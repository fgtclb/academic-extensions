<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Persistence;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

/**
 * The lazy result of a query that includes hidden records on TYPO3 v13, see
 * {@see HiddenRecordsFetcher::execute()}. It fetches when it is first used, as any
 * query result, and does that within the overlay window, so a result that is never
 * iterated, the full list behind a paginated one, is never fetched. Counting it stays a
 * count query.
 *
 * @template TValue of object
 * @extends QueryResult<TValue>
 * @todo Remove together with the support of TYPO3 v13.
 * @internal only to be used within `EXT:academic_*` extensions and not part of public API.
 */
#[Autoconfigure(public: true, shared: false)]
final class HiddenRecordsQueryResult extends QueryResult
{
    public function __construct(
        DataMapper $dataMapper,
        PersistenceManagerInterface $persistenceManager,
        private readonly HiddenRecordsFetcher $hiddenRecordsFetcher,
    ) {
        parent::__construct($dataMapper, $persistenceManager);
    }

    protected function initialize(): void
    {
        if (is_array($this->queryResult) || $this->query === null) {
            parent::initialize();
            return;
        }
        $this->hiddenRecordsFetcher->withinOverlayWindow($this->query, function (): void {
            parent::initialize();
        });
    }

    /**
     * Without a fetched result the parent runs a query of its own, limited to one record.
     */
    public function getFirst()
    {
        if (is_array($this->queryResult) || $this->query === null) {
            return parent::getFirst();
        }
        return $this->hiddenRecordsFetcher->withinOverlayWindow($this->query, fn() => parent::getFirst());
    }
}
