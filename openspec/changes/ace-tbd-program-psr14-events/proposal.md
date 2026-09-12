## Why

`academic_programs` (`packages/fgtclb/academic-programs`) dispatches no event.
To change which programs a list shows, what the list template receives, or
the data of a program page, projects subclass the program controller,
replace the program model through XCLASS, query the database from
ViewHelpers (for a hero image, for a degree sort) or replace the page data
processor. Each of these breaks silently on an upstream change, and ordering
tweaks such as the one ace-demo needs have no supported seam either.

## What Changes

- An event after the program list has built its selection from the element
  settings and the submitted filter, before the query runs, so a listener can
  change the selection.
- An event before the list is handed to its template, so a listener can
  replace or reorder the programs, adjust the offered filter categories and
  add template variables.
- An event after the data of a program page is built, so a listener can
  change it.
- Without listeners the output is unchanged.

Behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-programs/program-extension-events`: what an integrator can
  change in the program list and on program pages through event listeners.

### Modified Capabilities

None.

## Impact

- Three new final event classes in `academic_programs`, dispatched from the
  program controller and the program page data processor; they are public API
  from the release on.
- The data processor gets its collaborators through constructor injection
  instead of creating them itself.
- Integrator documentation for the events.

## Non-goals

- Making the factories or the models `final`. The program controllers become
  `final` in 3.0.0 through `ace-tbd-final-partner-project-controllers`, which
  depends on these events.
- Events for `academic_partners` and `academic_projects`; they are proposed as
  `ace-tbd-partners-projects-list-events` and use the same naming.
- A textual credit points value such as "180/210"; the credit points stay an
  integer.
- Backporting to branch `2`.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`programs-studyplan-12`). Four of the six analysed projects carry their own
code for this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-program-psr14-events` when the issue is filed after
implementation.
