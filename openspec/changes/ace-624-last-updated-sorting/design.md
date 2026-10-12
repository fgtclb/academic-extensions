## Context

Measured on `main` `e75f86b95`, TYPO3 13.4.35 (`core-13/vendor`) and 14.3.7
(`.Build/vendor`).

- The three list queries order by the bare column, with the `uid` tiebreaker of
  ACE-491 behind it: `ProgramRepository::findByDemand()`
  (`packages/fgtclb/academic-programs/Classes/Domain/Repository/ProgramRepository.php:89-97`),
  `PartnerRepository::findByDemand()` (`academic-partners/…/PartnerRepository.php:100-108`),
  `ProjectRepository::findByDemand()` (`academic-projects/…/ProjectRepository.php:91-99`).
  All three return through `HiddenRecordsFetcher::execute()` of `academic_base`.
- `SortingOptions::SORT_BY_LASTUPDATED_{ASC,DESC}` (`lastUpdated asc|desc`)
  exists in all three extensions. It is offered by the FlexForms (program list,
  partner list and map, project lists), by the sorting selects and by the list
  routes (`Configuration/Routes/List.yaml`, `last-updated` and
  `zuletzt-aktualisiert`). `Configuration/Extbase/Persistence/Classes.php` of
  each maps the property `lastUpdated` to the column `lastUpdated`.
- `pages.lastUpdated` is a core field, `type: datetime`, `default: 0`, without
  `nullable` (v14 `cms-core/Configuration/TCA/pages.php:284-293`, v13
  `:279-288`). Nothing fills it automatically.
- Extbase cannot order by an expression on v13. TYPO3 v14.2 added
  `QueryInterface::orderBy()` with `concat()`, `trim()` and `coalesce()`
  (`Changelog/14.2/Feature-32051-ExtbaseQueryExpressionBuilderForOrderings.rst`),
  but no `NULLIF`, and `core-13/vendor` has none of it.
- A Doctrine `QueryBuilder` passed to `$query->statement()` is what Extbase
  executes instead of the query object model, with limit and offset applied
  and the language and workspace overlay as usual
  (`Typo3DbBackend::getObjectDataByQuery()`). The count of such a query is
  taken from the same builder: `Typo3DbQueryParser::convertQueryToDoctrineQueryBuilder()`
  returns a clone of a `QueryBuilder` statement (v13 `:152-155`, v14
  `:126-129`), and `getObjectCountByQuery()` resets its ordering and counts.
  The parser is `@internal` on both cores ("only to be used within Extbase,
  not part of TYPO3 Core API"), and it already changed between them: v13
  takes one constructor argument, v14 three, v14 added the return type of
  `convertQueryToDoctrineQueryBuilder()` and resets its distinct suggestion
  per call, v13 does not. `GeneralUtility::makeInstance()` hides the
  constructor, a `new` would not.
- Two limits of the statement path, identical on both cores. The data path
  does not clone the statement: `getObjectDataByQuery()` sets offset and limit
  on the statement's own builder. `Query` has no `__clone()`, so the clone a
  `QueryResultPaginator` executes shares the `Statement` and its builder with
  the query of the whole result. A count or an unlimited fetch of that query
  taken after a page was fetched therefore carries the page's `LIMIT` and
  `OFFSET` (a count with an offset finds no row and reports 0). And the count
  path replaces the select with `COUNT(*)`: a fresh parser suggests no
  `DISTINCT` for a statement, so a `DISTINCT` the service puts into the select
  reaches the data, not the count. None of the three queries joins today
  (`contains()` on the categories is a subquery), so data and count agree.
  `PartnerController::listAction()` loads the whole result (`toArray()`)
  before the paginator counts and fetches the page, which is the order that
  keeps the first limit harmless.
- `ModifyQueryBeforeFetchingObjectDataEvent` lets a listener replace the query,
  and the after event receives the replaced one (`Generic\Backend::getObjectDataByQuery()`,
  both cores). `academic_base` already listens to it on v13:
  `LiftVisibilityForHiddenRecordsOverlay` lifts the visibility for the
  overlay of a hidden records query and restores it in the after event only
  for the query object it lifted (`spl_object_id`).
- `lastUpdated` and `SYS_LASTCHANGED` are mixed or upper case, and TYPO3 creates
  columns with quoted names, so an unquoted identifier resolves to a lower case
  name on PostgreSQL and fails. `COALESCE` and `NULLIF` are portable.
- `tstamp` is written by DataHandler on every save of the page record, hiding
  included, and not when a content element of the page changes.
  `SYS_LASTCHANGED` is written only by a frontend rendering of the page
  (v13 `TypoScriptFrontendController.php:402-408`, v14
  `RequestHandler.php:970-990`), into the translated record when a translation
  is rendered, and stays `0` for a page never rendered
  (`PageParts.php:50-67` on v14). The core XML sitemap uses
  `SYS_LASTCHANGED ?: tstamp` (`PagesXmlSitemapDataProvider.php:54` on v13).
- The development seed (`packages-dev/dev-site/…/Scenario.yaml`) dates every
  program and partner page, and its only lists sorted by "last updated" are two
  program lists, so the seed renders the same. Its project pages carry no date.

## Goals / Non-Goals

**Goals:**

- One ordering rule for the three extensions, in one place.
- Pagination, counts, hidden records and translations keep working as with the
  other sorting options.

**Non-Goals:**

- Changing the stored `lastUpdated` value or what templates print.

## Decisions

### Decision 1: how the ordering reaches the database (decision for the maintainer)

The order to express is
`COALESCE(NULLIF("pages"."lastUpdated", 0), "pages"."tstamp") <direction>`,
then `"pages"."uid" ASC` as today.

**a) A `QueryBuilder` statement built from the query, in the repositories
(recommended).** A stateless, `@internal` service in `academic_base` takes the
finished Extbase query and the direction. It converts the query with a fresh
`Typo3DbQueryParser` (a prototype that keeps state, so it is created per call
with `GeneralUtility::makeInstance()` and never injected), carries over a
suggested `DISTINCT`, replaces the ordering and hands the builder to
`$query->statement()`. The three repositories call it only for the
`lastUpdated` field and keep `setOrderings()` for every other option. The
expression is added as a select literal with an alias of its own and ordered
by that alias, which keeps it valid under `DISTINCT` on PostgreSQL and needs
no `getConcreteQueryBuilder()`. Count and data come from the same builder, so
pagination agrees as long as the query does not join and the whole result is
counted before a page of it is fetched (Context, "Two limits of the statement
path"). Cost: the `@internal` parser, pinned by the functional
tests on both cores, with a docblock that says so and no phpstan baseline
entry. A listener of the list events that changes constraints on the query of
the returned result has no effect for this option, because the statement
wins. The `Important` entries say so.

**b) A listener on `ModifyQueryBeforeFetchingObjectDataEvent`**, the shape one
project uses today. It converts late, so constraints a list event listener
adds still apply. Rejected because it needs the same `@internal` parser, runs
for every Extbase query of an installation, and has to recognise its own
queries: either by heuristics that also reorder third party queries of the
`pages` table, or by a marker kept in a service (state) or in the context with
a query result of its own, as `HiddenRecordsFetcher` does. It also has to run
before `LiftVisibilityForHiddenRecordsOverlay`: if it replaces the query after
the lift, the restore in the after event does not match the replaced object,
and the relations of the mapped objects are loaded with the lifted visibility
on v13.

**c) A persisted sort column**, for example an effective date on `pages`
maintained by a DataHandler hook and filled by an upgrade wizard. Extbase
orders by it without internal API. Rejected: a schema change on `pages`, a
hook on every page save, a wizard, and a value that goes stale for every
import, copy or SQL update that bypasses DataHandler.

**d) The v14.2 ordering API.** Not available on v13, which this branch
supports, and without `NULLIF` the stored `0` is not skipped. Not a successor
once v13 is dropped either, unless the core adds `NULLIF`.

### Decision 2: which time stands in for a missing date (decision for the maintainer)

**`tstamp` (recommended).** It is what the issue proposes, what an editor
reads as "last saved", deterministic and written by every DataHandler save.
Hiding or unhiding a page moves it, a content change does not.

**`SYS_LASTCHANGED`.** Closer to "content last changed", but written only when
the page is rendered in the frontend, `0` until then, and on a translation
written to the translated record while the query reads the default one. A list
would reorder when a page is first visited.

**`SYS_LASTCHANGED`, else `tstamp`**, as the core sitemap does. Inherits the
visit dependency and needs a second `NULLIF`.

Choosing another column than `tstamp` changes the wording of the scenarios
("last saved"), so the specs have to be updated before the code.

### Decided: no switch, no new option

The fallback replaces the behaviour of the existing option. A second option
"last updated, with fallback" would leave the useless one in place, and a
switch adds configuration for a behaviour nobody asked to keep.

### Decided: identifiers are quoted

Every column of the expression goes through `quoteIdentifier()` of the
builder that executes it, with the table alias the parser uses (`pages`).

### The ordering reads the default language record

Extbase queries the default language rows and overlays them afterwards, as for
every other option today. A translated page is therefore ordered by the
`lastUpdated` and `tstamp` of its default language record. The values of the
translated record are not considered, for `lastUpdated` exactly as today.

## Risks / Trade-offs

- [The `@internal` parser changes in a core update] → Functional tests per
  extension on v13 and v14 and on all four database systems, plus the
  pagination and hidden records cases, fail on such a change.
- [The shared statement builder keeps the limit of a page] → A count or a
  full fetch of the list query after a page fetch is wrong. The partner list
  loads the whole result first, and task 2.4 pins that order with a count
  taken after the page fetch. A later change of that order, or a template that
  counts the list after the page, has to be checked against it.
- [A constraint that joins is added to a list query] → The count of the
  statement then counts joined rows. Today no constraint joins, and the
  service's docblock says that a join needs a count of its own.
- [Other planned changes edit the same queries] →
  `ace-433-projects-without-end-date` changes the "Active" constraint of the
  project query and its test, `ace-566-partner-coordinates-numeric` the
  drawable constraint of the partner query and its fixtures. The statement is
  converted from the finished query, so their constraints are carried into
  it. Whichever lands second rebases and re-runs the combined cases.
- [A list event listener narrows the returned query] → Documented in the
  `Important` entries and in `docs/architecture/list-plugin-events.md`. Such a
  listener builds a query of its own for "last updated".
- [Paginated lists change their first page] → The `Important` entries name the
  effect.
- [Hidden records on v13] → The fetcher executes the same query object, and
  the overlay listener reads its source and settings, which a statement query
  keeps. Covered by a test with the option "Show hidden records".

## Migration Plan

None. A project that carries its own listener for this ordering removes it
after the update.

## Open Questions

None beyond the two decisions above, which are confirmed before any code is
written.
