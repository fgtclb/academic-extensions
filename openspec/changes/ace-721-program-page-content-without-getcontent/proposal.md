## Why

The backport of the `main` change of the same name, ACE-721, archived there as
`openspec/changes/archive/2026-09-23-ace-721-program-page-content-without-getcontent`.

The page type "Academic program" of `academic_programs`
(`packages/fgtclb/academic-programs`) renders its page content through the
global object `styles.content.getContent`. Since the configuration was cut per
component (ACE-458), only the opt-in component
`fgtclb/academic-programs-content-load` and its static template define it, so a
site that takes the component sets or component static templates without it
gets an exception on every program page. This branch carries that split just
like `main`; only the released 2.3.x always included the override.

## What Changes

- A program page renders the content of its main column without the
  content-load component, through an object scoped to the program page type.
- The content-load set and its static template stay, and so does the
  aggregate's dependency on them: nothing a 2.x site configuration names
  disappears in a minor release. They are only no longer needed by the page
  type.
- A site that customised `styles.content.getContent` for program pages (for
  example sliding content) now customises the program page variable instead;
  its program pages render differently until it does. That is the one
  observable change for a site that changed nothing, so it gets an `Important`
  changelog entry.

The behaviour is identical on TYPO3 v12 and v13, for site packages with a
`FLUIDTEMPLATE` page object, and on v13 also with a `PAGEVIEW` one.

## Capabilities

### New Capabilities

- `academic-programs/program-page-content`: how a program page renders the
  content elements of its main column, independent of the content-load
  component.

### Modified Capabilities

None.

## Impact

- `academic_programs`: the page TypoScript of the program page type and the
  page template `Resources/Private/Pages/AcademicProgram.html`; the comments
  of the content-load set and TypoScript; `Documentation/Configuration/`, the
  2.4 Breaking entry of the configuration split, a new 2.4 `Important` entry.
- The comment on the content-load sets in the site configuration of the
  `core-13` instance.
- `docs/architecture/database-queries.md`: tied rows and the PostgreSQL
  version.
- No database schema or dependency change.

## Non-goals

- Removing the content-load set and static template, and the
  `academic:upgrade:check` finding for sets TYPO3 cannot provide: both are
  3.0 only. This branch removes no set, so a site cannot end up depending on
  one that is gone.
- The same call in the page templates of `academic_partners` and
  `academic_projects`.
