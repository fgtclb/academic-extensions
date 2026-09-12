## Why

In `academic_projects` (`packages/fgtclb/academic-projects`) the rule for
whether a project is active or completed exists only as a constraint of the
list filter query. Templates cannot show the state: one project's card reads
a project state that does not exist and renders empty, and ace-demo carries its
own badge logic and labels. The labels "Active" and "Completed" already ship
with the extension.

## What Changes

- Every project exposes its state, active or completed, to templates, using
  the same rule as the list filter: no end date or an end date in the future
  is active, a past end date is completed.
- The project card of the list can show the state as a badge. A new content
  element option "Show active state badge" (`settings.showActiveStateBadge`)
  switches it on; it is off by default, so existing lists do not change.
- The badge uses the existing translated labels.

The candidate named the option `settings.list.showActiveState`. The projects
plugin has no `list` settings namespace, and `settings.hideActiveState`
already exists for the filter select, so the option gets a name that cannot
be mistaken for it.

The behaviour is the same on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-projects/project-active-state`: how a project's active or
  completed state is determined and shown.

### Modified Capabilities

None.

## Impact

- `academic_projects`: the project model, the active state enum,
  `Resources/Private/Partials/Project/Item.html`,
  `Configuration/FlexForms/ProjectSettings.xml`, `locallang_be.xlf` and its
  German file, the TypoScript default of the option.
- No database change.

## Non-goals

- A stored status column maintained by editors.
- A badge on the project page template; candidate `listings-22` gives that
  template partials a project can extend.
- Changing the list filter rule. The "Active" filter misses projects whose
  end date is stored as `NULL`; that is fixed in a separate bugfix change,
  and this change pins the gap with a test (see `design.md`).
- Backporting to branch `2`.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`listings-21`). Two of the six analysed projects carry their own code for this
today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-<slug>` when the issue is filed after implementation.
