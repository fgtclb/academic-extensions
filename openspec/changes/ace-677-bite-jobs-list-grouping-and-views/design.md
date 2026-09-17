## Context

See `proposal.md` for the motivation. Verified on this branch:

- `Templates/BiteJobs/List.html` is byte-identical to the file `main` carried
  before ACE-677: line 13 tests `{settings.jobs.groupBy} !== 'none'` and line 15
  groups by the hard-coded key `relationName`. `settings.jobs.groupBy` exists
  nowhere else — the FlexForms of both core versions have no such field and the
  TypoScript sets no `settings` — so the condition is always true, and
  `BiteJobsService::fetchBiteJobs()` returns the raw `jobPostings`, which carry
  no `relationName`. Every list is one group with the key `''`.
- `Partials/BiteJobs/Header.html` renders nothing for the empty group key but
  receives `grouped: 'true'` from the view partials, which selects the next
  lower heading level.
- `Configuration/FlexForms/Core12/AcademicBiteJobsList.xml` and its `Core13`
  counterpart both offer `List`, `Card` and `Table` without a default, so a
  value stored before 2.1 names a partial that does not exist.
- `initialize<Action>Action()` runs before `resolveView()`, which assigns
  `settings` to the view, on TYPO3 v12.4.45 and v13.4.

Differences to `main` that shape this change:

- PHP 8.1 is the floor. Enums exist since 8.0 and `str_ends_with()` since 8.0,
  so the enum is written exactly as on `main`; no `readonly class` is used.
- The controller has no `record` view variable here, and it is not needed: the
  `EXT:fluid_styled_content` header partial of v12 and v13 reads `data`.
- `TYPO3\CMS\Install\Attribute\UpgradeWizard` exists on v12 and v13 and is in
  use by nine wizards here, so the wizard needs no version switch. The v15
  blocker discussion of `main` does not apply to this branch.
- This extension has no `Tests/Functional/Plugins/` tree and no fixture
  extension here, so both are written as part of the backport, following
  `docs/workflow/backporting.md`: the test gets a home rather than being
  dropped.

## Goals / Non-Goals

**Goals:**

- The default output is the ungrouped list.
- Old view values render without touching stored data.
- A template override that reads `{settings.jobs.view}` keeps working.

**Non-Goals:**

- Offering grouping in the FlexForm again.

## Decisions

### Group only on a named field

The condition becomes `{settings.jobs.groupBy} && {settings.jobs.groupBy} != 'none'`
and `f:groupedFor` uses `groupBy="{settings.jobs.groupBy}"`, read from
`plugin.tx_academicbitejobs.settings.jobs.groupBy`. The FlexForm does not carry
the setting, and Extbase merges the FlexForm over the TypoScript settings with
`ArrayUtility::mergeRecursiveWithOverrule`, so the TypoScript value survives.

Rejected: removing the grouped branch. It is the only point where a project
that adds a grouping key to the postings can switch grouping on without
overriding the template.

### Normalise the view value before the view is resolved

`BiteJobsController::initializeListAction()` rewrites `settings.jobs.view` in
`$this->settings`: a trailing `View` is stripped, and anything that is not
`List`, `Card` or `Table` becomes `List`. The rule lives in the string-backed
enum `FGTCLB\AcademicBiteJobs\Enumeration\ListView`, so the controller and the
upgrade wizard cannot diverge.

Rejected: an `f:switch` fallback in the template, which an override would drop.

### The upgrade wizard

`FGTCLB\AcademicBiteJobs\Upgrades\ListViewFlexFormUpgradeWizard`, identifier
`academicBiteJobs_listViewFlexFormUpgradeWizard`, `final`, registered through
the `UpgradeWizard` attribute and the existing `Services.yaml` autowiring, with
`DatabaseUpdatedPrerequisite`. It follows the shape of the FlexForm wizard of
`academic_projects` on this branch:

- selects `tt_content` rows with the CType `academicbitejobs_list` and a
  non-empty `pi_flexform`, all restrictions removed so hidden and workspace
  rows are migrated too, with the CType list quoted by
  `quoteArrayBasedValueListToStringList()` and ordered by `uid`;
- maps `settings.jobs.view` through the enum and writes the FlexForm back only
  when the value changes, each update on its own query builder;
- removes `settings.jobs.groupBy` and `settings.jobs.custom.zuordnung`, the two
  settings version 2.1 removed from the data structure. The 2.0 data structure
  offered `groupBy` with the value `thema`, and a stored value is still merged
  into the plugin settings, so without this the grouped branch would come back
  for exactly the content elements this change repairs;
- `updateNecessary()` is true while any such row stores a view value that is not
  exactly `List`, `Card` or `Table`, including an empty or missing one, or one
  of the removed settings.

Rows whose XML does not parse are skipped and left to the runtime
normalisation. Other FlexForm fields of the row are left as they are.

### The test harness is part of the backport

The fixture extension `test_bitejobs_stub` answers the b-ite API from memory
through `$GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler']`, which v12 and v13
evaluate identically, and the plugin test renders the content element with
`FrontendPluginRenderingTrait`, which this branch has. It stays small: the
heading level, the grouping and the view values, which is what this change
touches.

## Risks / Trade-offs

- [Headings on existing lists move up one level] → Intended; named in the
  `Important-` changelog entry.
- [A new fixture extension changes the dependency install] → It is registered
  by `sbuerk/fixture-packages` like the six existing ones; the gates run after
  a `composerUpdate` for each core version.
- [The wizard rewrites a FlexForm it cannot parse] → Rows whose XML does not
  parse are skipped.

## Migration Plan

Run the upgrade wizard after the update. Until it has run, stored values are
read through the normalisation, so nothing breaks in between.

## Open Questions

None.
