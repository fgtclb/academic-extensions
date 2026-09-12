## Context

See `proposal.md` for the motivation. Verified on `main`:

- `Templates/BiteJobs/List.html:13` tests `{settings.jobs.groupBy} !== 'none'`.
  `settings.jobs.groupBy` exists nowhere else: the FlexForm has no such field,
  the TypoScript sets no `settings`, and the 2.1 breaking note
  (`Documentation/Changelog/2.1/Breaking-RemoveProjectSpecificCustomFields.rst`)
  lists it as removed. The condition is therefore always true.
- `f:groupedFor` groups by the hard-coded key `relationName` (:15), which
  `BiteJobsService::fetchBiteJobs()` no longer sets - it returns the raw
  `jobPostings` (:73-74). All jobs end up in one group with the key `''`.
- `Partials/BiteJobs/Header.html:7` renders nothing for the empty group key,
  but every job header receives `grouped: 'true'` from the view partials
  (`View/Card.html:18`, `View/List.html:18`), which selects the next lower
  heading level (`Header.html:16-19`, `:91-94`).
- The FlexForm offers `List`, `Card`, `Table` (`AcademicBiteJobsList.xml:22,26,30`)
  without a default; the template renders `BiteJobs/View/{settings.jobs.view}`,
  so `ListView` or an empty value names a missing partial.
- Extbase calls `initialize<Action>Action()` before `resolveView()`, which
  assigns `settings` to the view (`ActionController::processRequest()`,
  :374-388 in the installed 13.4.35).

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
and `f:groupedFor` uses `groupBy="{settings.jobs.groupBy}"`. The setting is
read from TypoScript (`plugin.tx_academicbitejobs.settings.jobs.groupBy`);
the FlexForm does not carry it, so the TypoScript value is not overridden.

Rejected: removing the grouped branch. It is the only point where a project
that adds a grouping key to the postings - the use case the 2.1 note promised
an API for - can switch grouping on without overriding the template.

### Normalise the view value before the view is resolved

`BiteJobsController::initializeListAction()` rewrites `settings.jobs.view` in
`$this->settings`: a trailing `View` is stripped, and anything that is not
`List`, `Card` or `Table` becomes `List`. Because this runs before
`resolveView()`, the view receives the normalised `settings` and templates
keep reading `{settings.jobs.view}`. The controller keeps its constructor;
`ace-tbd-final-partner-project-controllers` makes it `final` in 3.0.0 once
the B-ITE events exist.

The rule itself lives in one place, a string-backed enum
`FGTCLB\AcademicBiteJobs\Enumeration\ListView` (`List`, `Card`, `Table`) with
a static constructor from a stored value, so the controller and the upgrade
wizard below cannot diverge. An enum holds no state and needs no service
registration.

Rejected: an `f:switch` fallback in the template. It puts the mapping into a
file projects override, so an override would drop it again.

### Decided: normalisation and an upgrade wizard

The view values are normalised at runtime by the controller, and an upgrade
wizard also rewrites the stored values. The wizard cleans the stored data, so
the backend form shows the value the frontend renders. The normalisation stays
for what the wizard cannot cover: a value written after the wizard ran, by an
import or by hand, and an installation that has not run the wizard yet.

This is the one explicit exception to the rule that 3.0 adds no new upgrade
wizard while TYPO3 v13 is supported. The consequence is named: the wizard
imports `Install\Attribute\UpgradeWizard`, `UpgradeWizardInterface` and
`DatabaseUpdatedPrerequisite`, so it joins the v15 blocker set tracked by
ACE-294, which grows from 11 wizards in 6 extensions to 12 in 7. It has to be
migrated with the others when ACE-294 is resolved, and the repository
documentation that counts the call sites is updated in this change.

### The upgrade wizard

`FGTCLB\AcademicBiteJobs\Upgrades\ListViewFlexFormUpgradeWizard`, identifier
`academicBiteJobs_listViewFlexFormUpgradeWizard`, `final`, registered through
the `UpgradeWizard` attribute and the existing `Services.yaml` autowiring of
`Classes/`, with `DatabaseUpdatedPrerequisite`. It follows the shape of the
FlexForm wizard of `academic_projects`:

- selects `tt_content` rows with the CType `academicbitejobs_list` and a
  non-empty `pi_flexform`, all restrictions removed so hidden and workspace
  rows are migrated too, with the CType list quoted by
  `quoteArrayBasedValueListToStringList()` and ordered by `uid`;
- maps `settings.jobs.view` through the enum and writes the FlexForm back only
  when the value changes, each update on its own query builder;
- `updateNecessary()` is true while any such row stores a value that is not
  exactly `List`, `Card` or `Table`, including an empty or missing one.

Other FlexForm fields of the row are left as they are.

### Decided: backport to branch `2` as a change of its own

The fix is backported to branch `2` in a separate change with its own
analysis. Tag 2.3.4 ships a byte-identical template, verified on branch `2`,
and two projects run the 2.x line.

## Risks / Trade-offs

- [Headings on existing lists move up one level] → Intended; named in the
  `Important-` changelog entry.
- [The assignment order of `settings` differs on v14] → Task 2.2 verifies the
  normalised value arrives in the view on both versions.
- [One more v15 blocker call site] → Accepted as the named exception; the
  counts in `AGENTS.md` and `docs/` are updated so ACE-294 sees it.
- [The wizard rewrites a FlexForm it cannot parse] → Rows whose XML does not
  parse are skipped and left to the runtime normalisation.

## Migration Plan

Run the upgrade wizard after the update. Until it has run, stored values are
read through the normalisation, so nothing breaks in between.

## Open Questions

None.
