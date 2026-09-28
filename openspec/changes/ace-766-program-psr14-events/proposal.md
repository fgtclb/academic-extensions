## Why

`academic_programs` (`packages/fgtclb/academic-programs`) dispatches no event
of its own. Its plugins dispatch only the generic plugin view event of
`academic_base`, which adds view variables but cannot change which programs
are found or what a program page receives. To change those, projects
subclass the program controller, replace the program model through XCLASS,
query the database from ViewHelpers (for a hero image, for a degree sort) or
replace the page data processor. Each of these breaks silently on an upstream
change, and ordering tweaks such as the one ace-demo needs have no supported
seam either.

## What Changes

- An event in the program list and the program finder after the selection is
  built from the element settings and the submitted filter, before the query
  runs, so a listener can change the selection.
- An event in both after the query and before the view variables are
  assigned, so a listener can replace or reorder the programs, replace the
  offered filter categories and assign template variables.
- An event after the data of a program page is built, so a listener can
  change it.
- The events and the types they hand over are listed as public API on the
  extension points page of `academic_base`.
- Without listeners the output is unchanged.

Behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-programs/program-extension-events`: what an integrator can
  change in the program list, the program finder and on program pages
  through event listeners.

### Modified Capabilities

- `academic-base/plugin-action-context`: the program list and finder events
  receive the one context of their rendering, as the partner and project
  events do.

## Impact

- Three new final event classes in `academic_programs`, dispatched from the
  program controller and the program page data processor. They are public
  API from the release on, and so are `ProgramDemand` and `ProgramData`,
  which they hand over.
- The data processor gets its collaborators through constructor injection
  instead of creating them itself.
- A developer chapter for `academic_programs`, the rows on the extension
  points page, and `docs/architecture/list-plugin-events.md` naming the
  program events next to the partner and project ones.

## Non-goals

- Making the factories or the models `final`. The program controllers become
  `final` in 3.0.0 through `ace-tbd-final-partner-project-controllers`, which
  depends on these events.
- Events for `academic_partners` and `academic_projects`. They were added by
  `ace-717-partners-projects-list-events`, and these events follow their
  names and shape.
- An event for the details plugin, which reads the program model rather than
  the page data. The generic plugin view event covers it.
- A textual credit points value such as "180/210". The credit points stay an
  integer.
- Backporting to branch `2`.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`programs-studyplan-12`). Four of the six analysed projects carry their own
code for this today. Implements ACE-766, filed for this change.

Relates to ACE-624 and ACE-717.
