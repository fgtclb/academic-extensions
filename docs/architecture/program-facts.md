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

## See also

- [Page type rendering](page-type-rendering.md) — the page object the facts
  section of the program page belongs to.
- [Overridable partials](overridable-partials.md) — why the facts are one
  partial per row.
- [Icons](icons.md) — the provider of the credit points icon.
