## Why

The short description and the funders of a project are rich text fields, but
the project page and the project list print them raw. A link an editor sets
to a page or a file in the rich text editor reaches the visitor as a literal
`t3://` URL, and the HTML skips the site's own rich text processing.

This is the backport of ACE-676, merged on `main` for 3.0.0
(`openspec/changes/archive/2026-09-17-ace-676-project-rte-fields-parsefunc`
there). It is re-derived against this branch: the two templates carry the same
three lines, but the premise that made the change free of integrator
requirements on `main` holds here for TYPO3 v13 only. TYPO3 v12 does not define
the standard rich text processing configuration itself.

## What Changes

- The project page renders the short description and the funders through the
  site's rich text processing, as core renders every other rich text field.
- The project list renders the short description the same way.
- Links written in the editor to pages, files or records resolve to URLs.
- On TYPO3 v13 the fields follow the site's `lib.parseFunc_RTE`, which TYPO3
  defines for every site; nothing has to be added.
- On TYPO3 v12 the site has to provide `lib.parseFunc_RTE`, as
  fluid_styled_content, bootstrap_package and common site packages do. The
  extensions `academic_jobs` and `academic_persons` already require it on this
  branch; a v12 site without it now fails visibly on project pages and lists.

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
- Functional tests of the page template, and a first list plugin rendering
  test for this extension on this branch; an `Important-` changelog entry in
  `Documentation/Changelog/2.4/`.

## Non-goals

- Changing the TCA or the rich text configuration of the fields.
- Shipping a rich text processing configuration of our own, also not for v12.
- Touching other templates that print plain text fields.
- Backporting ACE-673, which changes the same page template on `main`.

## Source

Backport of ACE-676 from `main` (pull request #649). Backport analysis:
file-level diff, API check on 12.4.45 and 13.4, test harness parity.
