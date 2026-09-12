## Why

The short description and the funders of a project are rich text fields, but
the project page and the project list print them raw. A link an editor sets
to a page or a file in the rich text editor reaches the visitor as a literal
`t3://` URL, and the HTML skips the site's own rich text processing.

## What Changes

- The project page renders the short description and the funders through the
  site's rich text processing, as core renders every other rich text field.
- The project list renders the short description the same way.
- Links written in the editor to pages, files or records resolve to URLs.
- A site without the standard rich text processing configuration can no
  longer render the project page or list; the Important changelog entry
  states the requirement.

The behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-projects/project-rich-text`: how the rich text fields of a project
  are rendered on the project page and in the project list.

### Modified Capabilities

None.

## Impact

- `academic_projects` (`packages/fgtclb/academic-projects`): the page template
  `Resources/Private/Pages/AcademicProject.html` and the list partial
  `Resources/Private/Partials/Project/Item.html`.
- Markup of the two fields may change slightly, depending on the site's rich
  text configuration.
- Functional tests of the page template and the list plugin; an `Important-`
  changelog entry.

## Non-goals

- Changing the TCA or the rich text configuration of the fields.
- Shipping a rich text processing configuration of our own.
- Touching other templates that print plain text fields.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`listings-03`). Two of the six analysed projects carry their own code for
this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-project-rte-fields-parsefunc` when the issue is filed after
implementation.
