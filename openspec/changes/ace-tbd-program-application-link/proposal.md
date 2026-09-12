## Why

Program pages of `academic_programs` (`packages/fgtclb/academic-programs`)
have no field for the link to the application portal, which is the most
important call to action of such a page. One project added a column
`application_link` of its own, ace-demo two columns of its own, and another
project an inline table of coloured buttons; each also renders the button in
its own template.

## What Changes

- Two fields on the program tab of program pages (page type 20): the
  application link (a page or an external URL) and an optional label of up to
  60 characters.
- The program page shows the link as a call to action; an empty label falls
  back to a translated "Apply now", an empty link renders nothing.
- Link and label are available to templates on the program page and on
  program list items.
- The column name is the one a project already uses. That project drops its
  own definition; ace-demo copies its data with one statement, documented in
  the integrator migration guide.

Behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-programs/program-application-link`: the application link an
  editor maintains on a program page and how visitors and templates get it.

### Modified Capabilities

None.

## Impact

- Two `pages` columns, their TCA, labels and the derived schema: a database
  compare adds them.
- The program model and the program page data gain the two values.
- The program page template renders a new partial.

## Non-goals

- Several links or styled buttons per program (one project's inline table).
- Showing the link in the default list item; templates can, the default does
  not.
- Backporting to branch `2`.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`programs-studyplan-13`). Three of the six analysed projects carry their own
code for this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-program-application-link` when the issue is filed after
implementation.
