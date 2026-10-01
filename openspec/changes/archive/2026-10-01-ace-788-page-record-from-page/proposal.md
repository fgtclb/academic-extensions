## Why

On TYPO3 v13, a program, partner or project page fails with a type error when a
`PAGEVIEW` site package assigns a text as `data`, and reads the wrong array as
the page record when it assigns the records of a query. The three page data
processors read the page record from `data` first, the variable a
`FLUIDTEMPLATE` page object assigns, and only then from `page`. `PAGEVIEW`
reserves `page` but not `data`. The project page heading falls back to
`{data.title}` as well, which is empty on `PAGEVIEW`.

This backports the order of `main`, where it was changed with ACE-785
(`openspec/changes/archive/2026-09-30-ace-785-page-templates-sections-subtitle/`,
partners and projects) and ACE-786
(`openspec/changes/archive/2026-09-30-ace-786-program-page-record/`, programs).

## What Changes

- `academic_programs` (`packages/fgtclb/academic-programs/`),
  `academic_partners` (`packages/fgtclb/academic-partners/`) and
  `academic_projects` (`packages/fgtclb/academic-projects/`): the page data
  processor reads the page record from the page information object `page`
  first, from `data` only when there is none, and adds nothing when the value
  is not a non-empty array.
- `academic_projects`: the project page heading falls back to the title of the
  page record resolved in the same order, so a project without a project title
  shows the page title on `PAGEVIEW` too.
- Functional tests per page type, with a `PAGEVIEW` site package that assigns a
  text or the records of a query as `data`, and with a `FLUIDTEMPLATE` site
  package that assigns a `page` of its own.

TYPO3 v12 has no `PAGEVIEW`, so the defect is v13 only. On v12 the behaviour
stays as it is: a `FLUIDTEMPLATE` page object assigns no `page`, and the record
comes from `data`.

## Capabilities

### New Capabilities

- `academic-programs/program-page`: what a site visitor sees on a program page,
  starting with the program of the page on both page object types.

### Modified Capabilities

- `academic-partners/partner-page`: a partner page renders when a `PAGEVIEW`
  site package assigns a variable `data` of its own.
- `academic-projects/project-page`: the same for project pages, and the heading
  falls back to the page title on `PAGEVIEW`.

## Impact

- The three page data processors and the project page template.
- Their page template tests and fixtures, including a `PAGEVIEW` site package
  fixture for partners and projects.
- `docs/architecture/page-type-rendering.md`, new on this branch.
- An `Important-*.rst` entry in `Documentation/Changelog/2.4/` of each of the
  three extensions.

## Non-goals

- The rest of ACE-785: page layout, partials, subtitle and content variable of
  the partner and project pages are 3.0 features.
- The study plan processor reads `data` too, but there `data` is the record of
  the content element, which is what it needs.
