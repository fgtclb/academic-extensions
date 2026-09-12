## Why

Version 2.1 of the bite jobs list removed the grouping setting and renamed the
view values, but the list template was not adapted and stored content
elements were not migrated. Since then every list is rendered as one unnamed
group with every job title one heading level too low, and a content element
saved before 2.1 fails on a view that no longer exists. Projects override the
template or keep a fork for it.

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
  frontend renders. It is the one new upgrade wizard of 3.0 while TYPO3 v13
  is supported, and it adds a call site to the v15 blocker set (ACE-294).

The behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-bite-jobs/job-list`: how the B-ITE job list plugin chooses its
  view and whether it groups jobs.

### Modified Capabilities

None.

## Impact

- `academic_bite_jobs` (`packages/fgtclb/academic-bite-jobs`): the list
  template `Resources/Private/Templates/BiteJobs/List.html`, the list action
  of the controller, a view value enum and a new upgrade wizard.
- Stored data: `tt_content.pi_flexform` of job list content elements, only
  its view value, only when the wizard is run.
- Visible output: job titles on every existing list move up one heading
  level.
- Functional tests of the list plugin and the wizard; an `Important-`
  changelog entry.
- The v15 blocker counts in `AGENTS.md` and `docs/architecture/`.

## Non-goals

- Re-introducing the grouping or custom field options removed in 2.1 as
  FlexForm fields.
- Request or result events for the job service; that is a change of its own.
- Rewriting FlexForm fields other than the view value.
- Migrating the wizard off the deprecated install API; that is ACE-294.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`listings-04`). Two of the six analysed projects carry their own code for this
today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-bite-jobs-list-grouping-and-views` when the issue is filed after
implementation.
