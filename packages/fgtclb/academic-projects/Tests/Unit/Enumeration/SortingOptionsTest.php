<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Unit\Enumeration;

use FGTCLB\AcademicProjects\Enumeration\SortingOptions;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The values are used verbatim as Extbase ordering strings, and `getConstants()` is
 * what `ProjectDemand::setSorting()` validates against - so a value that drops out of
 * this list stops being selectable without anything failing.
 */
final class SortingOptionsTest extends UnitTestCase
{
    /**
     * The full set, asserted as a whole rather than by counting: a renamed constant or
     * a changed ordering string has to show up here, since both are part of what a
     * stored FlexForm value refers to.
     */
    #[Test]
    public function everySortingOptionIsOffered(): void
    {
        $this->assertSame(
            [
                'SORT_BY_TITLE_ASC' => 'title asc',
                'SORT_BY_TITLE_DESC' => 'title desc',
                'SORT_BY_LASTUPDATED_ASC' => 'lastUpdated asc',
                'SORT_BY_LASTUPDATED_DESC' => 'lastUpdated desc',
                'SORT_BY_SORTING_ASC' => 'sorting asc',
                'SORT_BY_SORTING_DESC' => 'sorting desc',
                'SORT_BY_BUDGET_ASC' => 'tx_academicprojects_budget asc',
                'SORT_BY_BUDGET_DESC' => 'tx_academicprojects_budget desc',
                'SORT_BY_STARTDATE_ASC' => 'tx_academicprojects_start_date asc',
                'SORT_BY_STARTDATE_desc' => 'tx_academicprojects_start_date desc',
            ],
            SortingOptions::getConstants(),
        );
    }

    /**
     * `__default` is an alias of an option that is already in the list, so offering it
     * would present the same ordering twice.
     */
    #[Test]
    public function theDefaultAliasIsNotAnOptionOfItsOwn(): void
    {
        $this->assertArrayNotHasKey('__default', SortingOptions::getConstants());
        $this->assertSame(SortingOptions::SORT_BY_TITLE_ASC, SortingOptions::__default);
    }

    /**
     * `ProjectDemand` splits an option on the first space into field and direction, so
     * an option that carries neither, or carries more than one space, would reach the
     * query builder as an ordering it cannot use.
     */
    #[Test]
    public function everyOptionIsAFieldAndADirection(): void
    {
        foreach (SortingOptions::getConstants() as $name => $value) {
            $this->assertMatchesRegularExpression(
                '/^[a-zA-Z0-9_]+ (asc|desc)$/',
                $value,
                sprintf('%s is not a "<field> <asc|desc>" pair', $name),
            );
        }
    }

    /**
     * Two constants sharing a value would make the option list ambiguous - the FlexForm
     * stores the value, not the constant name, so the second one could never be selected.
     */
    #[Test]
    public function noOrderingIsOfferedTwice(): void
    {
        $values = array_values(SortingOptions::getConstants());
        $this->assertSame($values, array_values(array_unique($values)));
    }
}
