<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Functional\Factory;

use FGTCLB\AcademicProjects\Domain\Model\Project;
use FGTCLB\AcademicProjects\Factory\ProjectFactory;
use FGTCLB\AcademicProjects\Tests\Functional\AbstractAcademicProjectsTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * `ProjectFactory::get()` is three lines around `DataMapper::map()`, which is exactly why
 * it needs a functional test rather than a unit one: what it actually does is bind
 * `Configuration/Extbase/Persistence/Classes.php`, the `pages` TCA of this extension and
 * the `Project` model together. Every one of those three can be changed without touching
 * a line of the factory, and nothing else asserts the result.
 *
 * Its only caller is `DataProcessing\ProjectProcessor`, which hands over the raw page
 * record of the page being rendered - `$processedData['data']`, or the `PAGEVIEW` page
 * record. The tests therefore read the row from the database the way the frontend does
 * instead of hand-building it, except where a deliberately incomplete row is the point.
 *
 * Two properties of the factory follow from that caller and are asserted here because
 * they are easy to assume the other way round:
 *
 * - it validates nothing. Doktype and the enable fields are the caller's business, and
 *   `ProjectProcessor` is bound to the page type through TypoScript, not through a check
 *   in PHP;
 * - the categories are not part of the mapping at all. `Project::getAttributes()` resolves
 *   them lazily through `GeneralUtility::makeInstance(CategoryRepository::class)` against
 *   the hardcoded group `projects`, so they are reached only once a template asks for
 *   them - and with the `pages` restrictions of an ordinary frontend query.
 */
final class ProjectFactoryTest extends AbstractAcademicProjectsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProjectFactory/projects.csv');
    }

    #[Test]
    public function aPageRecordIsMappedToAProjectModel(): void
    {
        $project = $this->subject()->get($this->pageRecord(10));

        $this->assertInstanceOf(Project::class, $project);
        $this->assertSame(10, $project->getUid());
        $this->assertSame(1, $project->getPid());
        $this->assertSame(30, $project->getDoktype());
        $this->assertSame('Quantum Research', $project->getTitle());
    }

    /**
     * The four scalar project columns carry the `tx_academicprojects_` prefix in the
     * database and none of it in the model, so this pins the property map rather than the
     * mapper: a renamed column or a dropped entry in `Classes.php` silently leaves the
     * property at its default instead of failing.
     */
    #[Test]
    public function theProjectColumnsAreMappedToTheirModelProperties(): void
    {
        $project = $this->subject()->get($this->pageRecord(10));

        $this->assertSame('Quantum research beyond the lab', $project->getProjectTitle());
        $this->assertSame('Investigating quantum effects.', $project->getShortDescription());
        $this->assertSame('Deutsche Forschungsgemeinschaft', $project->getFunders());
        $this->assertSame(123456.78, $project->getBudget());
    }

    /**
     * Both date columns are integer timestamps, and the assertion is on the timestamp
     * rather than on a formatted date on purpose - the `DateTime` the mapper builds
     * carries the PHP local timezone, so a formatted comparison would assert the test
     * container's timezone rather than the mapping.
     */
    #[Test]
    public function theDateColumnsAreMappedToDateTimeObjects(): void
    {
        $project = $this->subject()->get($this->pageRecord(10));

        $this->assertInstanceOf(\DateTime::class, $project->getStartDate());
        $this->assertInstanceOf(\DateTime::class, $project->getEndDate());
        $this->assertSame(1704067200, $project->getStartDate()->getTimestamp());
        $this->assertSame(1735689600, $project->getEndDate()->getTimestamp());
    }

    /**
     * A record that never had a date set stores `0`, not `NULL`, because neither column is
     * declared `nullable` in TCA. The mapper maps that empty value back to `null`, which is
     * what the model's `?\DateTime` promises and what a Fluid `f:if` on the property
     * expects. Without it every project would render as having started on 1970-01-01.
     */
    #[Test]
    public function aZeroDateColumnIsMappedToNull(): void
    {
        $project = $this->subject()->get($this->pageRecord(11));

        $this->assertNull($project->getStartDate());
        $this->assertNull($project->getEndDate());
    }

    /**
     * The same result through a different route: the columns are `DEFAULT NULL` in
     * `ext_tables.sql`, so a page created before the extension was installed carries
     * `NULL`, and the mapper skips a column that is not `isset()` instead of mapping it.
     */
    #[Test]
    public function aNullDateColumnIsMappedToNull(): void
    {
        $project = $this->subject()->get($this->pageRecord(14));

        $this->assertNull($project->getStartDate());
        $this->assertNull($project->getEndDate());
    }

    /**
     * `tx_academicprojects_budget` is nullable in the database while `Project::$budget` is
     * a non-nullable `float`, so "no budget recorded" and "a budget of zero" are the same
     * value once the record is mapped. A template cannot tell them apart.
     */
    #[Test]
    public function aBudgetThatWasNeverEnteredIsMappedToZero(): void
    {
        $project = $this->subject()->get($this->pageRecord(11));

        $this->assertSame(0.0, $project->getBudget());
    }

    /**
     * The factory takes whatever array it is handed - `ProjectProcessor` passes
     * `$processedData['data']`, and a `FLUIDTEMPLATE` can be configured to fill that with
     * something other than a full page row. A missing column must leave the model default
     * in place rather than raise anything, the more so because the suite fails on notices.
     */
    #[Test]
    public function aRecordWithoutTheProjectColumnsKeepsTheModelDefaults(): void
    {
        $project = $this->subject()->get(['uid' => 4711, 'pid' => 1]);

        $this->assertSame(4711, $project->getUid());
        $this->assertSame(0, $project->getDoktype());
        $this->assertSame('', $project->getTitle());
        $this->assertSame('', $project->getProjectTitle());
        $this->assertSame('', $project->getShortDescription());
        $this->assertSame('', $project->getFunders());
        $this->assertSame(0.0, $project->getBudget());
        $this->assertNull($project->getStartDate());
        $this->assertNull($project->getEndDate());
        $this->assertCount(0, $project->getMedia());
    }

    /**
     * The factory has no notion of the project page type. Handing it an ordinary page
     * yields a `Project` carrying that doktype - which is correct, because the guard is the
     * TypoScript that binds `ProjectProcessor` to page type 30, but it means the factory
     * cannot be used as a filter.
     */
    #[Test]
    public function aPageOfAnotherDoktypeIsMappedNonetheless(): void
    {
        $project = $this->subject()->get($this->pageRecord(14));

        $this->assertSame(14, $project->getUid());
        $this->assertSame(1, $project->getDoktype());
        $this->assertSame('Regular Page', $project->getTitle());
    }

    /**
     * The same for the enable fields: a hidden page reaching the processor is already a
     * decision the frontend made, so the factory maps it like any other row.
     */
    #[Test]
    public function aHiddenPageIsMappedNonetheless(): void
    {
        $project = $this->subject()->get($this->pageRecord(13));

        $this->assertSame(13, $project->getUid());
        $this->assertSame('Hidden laboratory', $project->getProjectTitle());
    }

    /**
     * The categories are not in the mapped row - `pages.categories` holds a relation count,
     * nothing else - so this is the one part of the result that costs a second query, fired
     * by the model rather than by the factory, and keyed by the uid the factory mapped.
     */
    #[Test]
    public function theCategoriesOfTheMappedPageAreResolvedAsAttributes(): void
    {
        $project = $this->subject()->get($this->pageRecord(10));

        $this->assertSame(
            [1 => 'Quantum Physics', 2 => 'Industry Partner'],
            $this->attributeTitles($project),
        );
    }

    /**
     * `getCategoryCollection()` is what `GetCategoryCollectionInterface` requires and what
     * `CategoryRepository::findAllApplicable()` calls; it must not resolve a second,
     * independent collection.
     */
    #[Test]
    public function theCategoryCollectionIsTheAttributeCollection(): void
    {
        $project = $this->subject()->get($this->pageRecord(10));

        $this->assertSame($project->getAttributes(), $project->getCategoryCollection());
    }

    #[Test]
    public function aPageWithoutCategoriesHasAnEmptyAttributeCollection(): void
    {
        $project = $this->subject()->get($this->pageRecord(11));

        $this->assertCount(0, $project->getAttributes());
    }

    /**
     * Page 12 is linked to a hidden category and to one whose type belongs to no group of
     * this extension. Both are dropped, for different reasons - the first by the default
     * restrictions of the query, the second because the group `projects` resolves to the
     * four types of `Configuration/CategoryTypes.yaml` - and the collection ends up empty
     * although `pages.categories` counts two relations.
     */
    #[Test]
    public function hiddenCategoriesAndCategoriesOutsideTheProjectsGroupAreNotAttributes(): void
    {
        $project = $this->subject()->get($this->pageRecord(12));

        $this->assertCount(0, $project->getAttributes());
    }

    /**
     * Not an optimisation but a property worth knowing: `DataMapper` registers every mapped
     * row in the Extbase persistence session, so a second `get()` for the same uid returns
     * the object built the first time and the values of the second row are discarded. Two
     * `ProjectProcessor` runs in one request - a page rendering a detail view of itself, or
     * a `PAGEVIEW` after a `FLUIDTEMPLATE` - therefore share one model.
     */
    #[Test]
    public function theSameRecordIsMappedToTheSameObjectInstance(): void
    {
        $record = $this->pageRecord(10);
        $first = $this->subject()->get($record);

        $record['title'] = 'Renamed in the meantime';
        $second = $this->subject()->get($record);

        $this->assertSame($first, $second);
        $this->assertSame('Quantum Research', $second->getTitle());
    }

    /**
     * `ProjectFactory` is a private service, so it is not retrievable from the container -
     * and `ProjectProcessor` does not retrieve it either, it uses `makeInstance()`.
     */
    private function subject(): ProjectFactory
    {
        return GeneralUtility::makeInstance(ProjectFactory::class);
    }

    /**
     * The full page row, as the frontend hands it to `ProjectProcessor`. Only the deleted
     * restriction is kept so that the hidden page of the fixture can be read too.
     *
     * @return array<string, mixed>
     */
    private function pageRecord(int $uid): array
    {
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());
        $record = $queryBuilder
            ->select('*')
            ->from('pages')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchAssociative();

        $this->assertIsArray($record, sprintf('Page %d is missing from the fixture.', $uid));

        return $record;
    }

    /**
     * @return array<int, string>
     */
    private function attributeTitles(Project $project): array
    {
        $titles = [];
        foreach ($project->getAttributes() as $category) {
            $titles[$category->getUid()] = $category->getTitle();
        }
        ksort($titles);

        return $titles;
    }
}
