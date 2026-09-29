## Why

Program pages of `academic_programs` (`packages/fgtclb/academic-programs`)
have no field for the link to the application portal, which is the most
important call to action of such a page. One project added a column
`application_link` of its own, another one two columns of its own, and a third
one an inline table of coloured buttons. Each also renders the button in its
own template.

## What Changes

- Two fields on the program tab of program pages (page type 20): the
  application link (a page or an external URL) and an optional label of up to
  60 characters.
- The program page shows the link as a call to action. Without a label it
  reads a translated "Apply now", without a link nothing is rendered.
- Link and label are available to templates on the program page and on
  program list items.
- The column name is the one a project already uses. That project drops its
  own definition. A project with columns of its own copies its data with one
  statement, documented in the changelog entry.

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
- Showing the link in the default list item. Templates can, the default does
  not.
- Backporting to branch `2`.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`programs-studyplan-13`). Three of the six analysed projects carry their own
code for this today. The YouTrack issue is ACE-777.
