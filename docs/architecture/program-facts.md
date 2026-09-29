# Program facts

`academic_programs` shows facts about a program in three places: the program
page, the program details content element and every card of the program list.
A fact is the categories of one category type of the group `programs`, or one
of the program fields `creditPoints`, `jobProfile`, `performanceScope` and
`prerequisites`. The integrator side — the two settings, the item vocabulary,
the defaults — is in the extension's `Documentation/Configuration/`; this page
is about how the three places share one implementation.

The project analysis of 2026-09 found all six installations re-implementing
the facts: fifteen partials in one of them, a hard-coded list in another, and a
pseudo category type registered only to give credit points an icon. The
templates had fixed the facts per place, each place in markup of its own.

## One builder, three callers

`Service/ProgramFactsBuilder` turns a program and a comma-separated field list
into an ordered list of `Domain/Model/ProgramFact`. It is `final readonly` and
holds nothing; the three places differ only in how they reach it:

| Place           | Caller                                                | Field list                                |
|-----------------|-------------------------------------------------------|-------------------------------------------|
| Program page    | the `program-data` processor, option `factsFields`    | `plugin.tx_academicprograms.facts.fields` |
| Details element | `DetailsController::showAction()`                     | `settings.facts.fields` of the plugin     |
| Program card    | `<ace:program.facts>` in `Partials/Program/Item.html` | `settings.card.fields` of the plugin      |

The page goes through the processor because a `PAGEVIEW` page object ignores
`settings` of its own — see [Page type rendering](page-type-rendering.md). The
card goes through a ViewHelper because the card renders one program of many,
and the list action would otherwise have to build the facts of every program
up front.

`Program` (the Extbase model of the plugins) and `ProgramData` (the data object
of the page) implement `ProgramFactsSourceInterface`, the categories and the
four fields, so the builder does not care which one it gets.

## What an empty list means

An empty field list stands for what the place showed before the list existed,
so an installation that configures nothing sees the same facts: the page every
category type and then the four fields, the details element every category
type, the card the degree. `ProgramFactsPlace` names the place for exactly that
decision and for nothing else.

"Every category type" is taken in the key order of
`CategoryCollection::getAllCategoriesByType()`, which is the order of the
category type registry for the group. The builder keeps no list of types and
never sorts them, so a change to the order of the registry — a priority, an
extension that registers a type — reaches the facts without a line here.
A list that names types keeps the order it names them in.

## Only the most specific category

`plugin.tx_academicprograms.facts.mostSpecificOnly` makes the builder take the
categories from `CategoryCollection::getMostSpecificCategoriesByType()` of
`category_types` instead of `getAllCategoriesByType()`. The switch reaches the
three callers the way the field lists do: the processor option
`factsMostSpecificOnly`, `settings.facts.mostSpecificOnly` in the details
controller, and the argument `mostSpecificOnly` of `<ace:program.facts>`, which
the card hands `settings.facts.mostSpecificOnly`. The card uses the facts
switch rather than one of its own, so a program shows the same categories in
all three places.

The collection method leaves out every category that is an ancestor of another
category of the same type in the collection. It walks up through the parents of
the attached categories only, without a query: a list of twenty programs would
otherwise pay one rootline query per level, category and program on every
render. The price is that a level which is not assigned breaks the line, which
is fine for the two-level degree hierarchies the analysed projects use. A
parent of another type is kept, because it is another fact, and the walk passes
it, so an ancestor of the same type above it is still found. A parent chain
that leads back to itself hides nothing of its own, since each of its categories
would otherwise hide the others and leave the fact empty.

The walk reads `Category::getParentId()`, which is the parent of the category
in the rendered language. The core copies the parent into a translation as it
is, so it points at the default language parent and the walk finds it. An
editor who points a translation at another parent changes the hierarchy for
that language.

The setting is programs only. The workarounds the analysis found all hide a
parent degree, and none of the partner or project listings asks for it. With the
method in `category_types`, extending it later costs a setting per extension.

## One partial per row

`Partials/Program/Facts.html` renders the list and `Partials/Program/Facts/Item.html`
one fact, the rule of [Overridable partials](overridable-partials.md): a
project that changes how a fact looks overrides the row once, for all three
places. The card passes `listClass` and `itemClass` for its Bootstrap list
group; the facts markup is otherwise the same everywhere.

A program field value is rendered raw, as the page rendered it before: the
three text fields are rich text edited in the backend. Credit points are an
integer, and `0` counts as no value.

The partial `Program/Categories.html` that rendered the categories on the page
and in the details element is removed rather than deprecated: an override of
it is ignored silently either way, so a deprecation phase would only have
delayed the same migration.

## Tests

`academic-programs/Tests/Functional/Facts/ProgramFactsTest.php` renders all
three places with and without the settings, on a `FLUIDTEMPLATE` and a
`PAGEVIEW` page object and on a site set site, and asserts that an override of
the removed partial renders nowhere. It asserts the order by labels and values,
not by markup, so its cases without configuration held before the facts
partials existed too; the published classes, a category title escaped once and
the rich text rendered raw are asserted on their own.
`Tests/Unit/Service/ProgramFactsBuilderTest.php` covers the list rules, a type
order that is not the registry's included, and
`Tests/Functional/Imaging/FactIconsTest.php` the credit points icon.
`Tests/Functional/Facts/ProgramFactsMostSpecificCategoryTest.php` renders the
three places with a parent and a child degree assigned, with the setting off
and on, as a constant and as a site setting, renders a translated program page,
and filters the list by the parent either way. The walk itself is covered in
`typo3-category-types/Tests/Unit/Collection/CategoryCollectionTest.php`.

## See also

- [Page type rendering](page-type-rendering.md) — the page object the facts
  section of the program page belongs to.
- [Overridable partials](overridable-partials.md) — why the facts are one
  partial per row.
- [Icons](icons.md) — the provider of the credit points icon.
