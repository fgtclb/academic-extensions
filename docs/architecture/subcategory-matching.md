# Subcategory matching

The program list and the program finder have a field **Include subcategories**,
`settings.filter.includeSubcategories`, off by default. With it on, a selected
category matches every program that carries the category or a category below it,
and the filter offers a category as soon as a program carries one of its
subcategories. What an integrator or editor sees is documented in the
`Documentation/Configuration/` of `academic_programs`. This page is about how the
tree is read and the rules that are easy to get wrong.

## Where the flag goes

| Piece                                                  | What it does with the flag                                                      |
|--------------------------------------------------------|---------------------------------------------------------------------------------|
| `ProgramListSettings.xml`, `ProgramFinderSettings.xml` | A `check` field with `checkboxToggle` and default `0`, per element              |
| `DemandFactory::createDemandObject()`                  | Maps it onto `ProgramDemand::setIncludeSubcategories()`, from the settings only |
| `ProgramRepository::findByDemand()`                    | Widens each selected category by its subtree                                    |
| `ProgramController::findApplicableCategories()`        | Offers a parent whose subcategory is carried, for the list and the finder       |

The flag belongs to the element, never to the request: a submitted
`includeSubcategories` is not read, so a link cannot change what an element
matches. There is no site setting and no constant. Whether a category tree is
meant hierarchically differs per list, and an element without the field keeps the
exact match it had.

## Widening a selection

`findByDemand()` asks `CategoryRepository::findDescendantUids()` once for the
subtrees of all selected categories, then replaces each
`contains('categories', $uid)` by a `logicalOr()` of that uid and its descendants,
inside the outer `logicalAnd()`.

A single `in('categories.uid', …)` per selection was rejected. Extbase joins a
relation once per query, so two such constraints would have to match the same
category row, and two selections of different types would find nothing. With
`contains()` each term is a subselect of its own.

`findDescendantUids()` walks the tree downwards, one statement per level, each
built and executed on its own query builder with
`quoteArrayBasedValueListToIntegerList()`, the types of the group, the default
language and `ORDER BY uid`. A uid reached before is not read again, which ends a
loop. The result is computed per requested uid in memory afterwards, because one
requested category can sit below another. A recursive CTE was rejected: it needs
raw SQL, and the repository rules ask for query builder statements that behave the
same on SQLite, MariaDB, MySQL and PostgreSQL.

The default restrictions of the query builder stay in place, so a hidden or
deleted category is not part of the subtree, and neither is anything below it or
below a category of a type outside the group. Those restrictions hold no
workspace restriction, as for every other query of the repository, so a
category that exists in a workspace only takes part in the live frontend as
well. A uid of 0 or less is not a category and gets no subtree: walked, 0 would
find every root category of the group.

The walk reads `parent` of the default-language rows. An editor can give a
translation another parent, but the uids a program carries are those of the
default language, and so is the tree both walks read.

## Offering a parent

`CategoryRepository::findAllApplicable()` returns every visible category of the
group and disables the ones no listed program carries itself. With the flag off
that stays as it is. With the flag on, the controllers call
`findAllApplicableWithSubcategories()`, which runs the same query and then enables
every ancestor of an enabled category, reading the parents of the rows before
they are overlaid with a translation. The parent id of a category object is the
one of its translation in a translated frontend, which is why the walk does not
read it.

That walk needs no further query and sees the same tree as the walk downwards:
the query returns exactly the visible categories of the group, so a hidden
category, or one of a type outside the group, is missing from it and ends the
walk. Without it, a list where editors assign only the specific category would
render the parent as a disabled option, and a visitor could never select it.

Hiding options without results, `settings.filter.hideDisabledOptions`, works on
the disabled marker, so a parent enabled this way is kept, and nothing in the
filter select had to change.

## The finder and its target list

The finder has the same field, but it only decides which options the finder
enables. Its preselection follows, because the finder preselects only an
option it enables. The programs found are those of the list on the finder's target page,
which applies its own field. The finder does not read the list's setting: the
target page can hold several lists, and the finder already reads its programs
from its own storage for the same reason, see
[List filter types](list-filter-types.md#the-program-finder).

## Tests

| What                                                 | Test                                                                                          |
|------------------------------------------------------|-----------------------------------------------------------------------------------------------|
| The walk down and up, hidden, foreign type, loop     | `typo3-category-types/Tests/Functional/Domain/Repository/CategoryRepositorySubtreeTest.php`   |
| The flag from the settings, never from the request   | `academic-programs/Tests/Functional/Factory/DemandFactoryIncludeSubcategoriesTest.php`        |
| Subtree, sibling, several selections                 | `academic-programs/Tests/Functional/Domain/Repository/ProgramRepositorySubcategoriesTest.php` |
| Filter options, hidden options, preselection, finder | `academic-programs/Tests/Functional/Plugins/AcademicProgramsSubcategoryFilterTest.php`        |
| The field in both elements                           | `academic-programs/Tests/Functional/Backend/FormEngine/IncludeSubcategoriesFieldTest.php`     |

## See also

- [List filter types](list-filter-types.md): which filters a list offers, the
  disabled options and the program finder.
- [Database queries](database-queries.md): quoting value lists, one builder per
  statement, and ordering.
