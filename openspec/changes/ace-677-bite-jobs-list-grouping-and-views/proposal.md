## Why

Version 2.1 of the bite jobs list removed the grouping setting and renamed the
view values, but the list template was not adapted and stored content elements
were not migrated. Since then every list is rendered as one unnamed group with
every job title one heading level too low, and a content element saved with 2.0
fails on a view that no longer exists. Projects override the template or keep a
fork for it.

This is the backport of ACE-677, merged on `main` for 3.0.0
(`openspec/changes/archive/2026-09-17-ace-677-bite-jobs-list-grouping-and-views`
there). It is re-derived against this branch: the list template, the controller
and the FlexForm view values are the same as on `main` before the fix, the code
has to run on PHP 8.1, and this extension has no frontend plugin test tree here,
so the test harness is written as part of the backport.

## What Changes

- The job list is only grouped when an integrator names a grouping field in
  TypoScript; without one, the ungrouped list renders, as the 2.1 breaking
  note intended. Job titles move back up to the heading level the content
  element's header layout asks for.
- The list plugin accepts the pre-2.1 view values `ListView`, `CardView` and
  `TableView` as `List`, `Card` and `Table`, and falls back to the list view
  for an empty or unknown value.
- An upgrade wizard rewrites the stored view value of every job list content
  element to `List`, `Card` or `Table`, so the backend form shows what the
  frontend renders, and removes the two settings version 2.1 removed from the
  data structure but left in the stored FlexForms.

The behaviour is identical on TYPO3 v12 and v13.

## Capabilities

### New Capabilities

- `academic-bite-jobs/job-list`: how the B-ITE job list plugin chooses its
  view and whether it groups jobs.

### Modified Capabilities

None.

## Impact

- `academic_bite_jobs` (`packages/fgtclb/academic-bite-jobs`): the list
  template `Resources/Private/Templates/BiteJobs/List.html`, the list action
  of the controller, a view value enum and a new upgrade wizard. The extension
  declares `typo3/cms-install`, which the wizard needs.
- Stored data: `tt_content.pi_flexform` of job list content elements, only
  its view value, only when the wizard is run.
- Visible output: job titles on every existing list move up one heading
  level.
- The first `Tests/Functional/Plugins/` tree and the first fixture extension
  of this extension on this branch, plus tests of the wizard and the enum; an
  `Important-` changelog entry in `Documentation/Changelog/2.4/`.

## Non-goals

- Re-introducing the grouping or custom field options removed in 2.1 as
  FlexForm fields.
- Request or result events for the job service.
- Rewriting FlexForm fields other than the view value.
- Content elements created with 1.x, which are plugins of the list content
  type and are not rendered by this branch.

## Source

Backport of ACE-677, derived from the project differences analysis of
2026-09-12 (candidate `listings-04`). Two of the six analysed projects run the
2.x line and carry their own code for this today.
