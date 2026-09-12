## Why

The page template of the program page type in `academic_programs`
(`packages/fgtclb/academic-programs`) declares no layout. On a site package
that renders its header, navigation and footer through a page layout, as
bootstrap_package does, a program page renders without any of them. The
template has no seam either: facts and a fixed attribute list are inline, so
all six known projects replace the whole page template. Its template paths
use index 100, which on `PAGEVIEW` sites replaces the site package's own
paths on program pages.

## What Changes

- **BREAKING** Program pages render inside the site's page layout, `Default`
  by default, through a `Main` section. The layout name is a site setting.
- The template is split into named partials (header with an optional link
  back to the list, media, facts, content), each overridable on its own.
- **BREAKING** The template paths of the page type move from index 100 to 50,
  so a site package's paths above 50 win; the unused layout path is dropped.
- A site setting names the list page for the back link.
- Overrides of `AcademicProgram.html` keep working, as long as they no longer
  render `styles.content.getContent`, which
  `ace-tbd-program-page-content-without-getcontent` removes.

The behaviour is identical on TYPO3 v13 and v14, for `FLUIDTEMPLATE` and
`PAGEVIEW` page objects.

## Capabilities

### New Capabilities

- `academic-programs/program-page-layout`: how a program page is embedded in
  the site layout and which parts of it an integrator can replace.

### Modified Capabilities

None.

## Impact

- `academic_programs`: the page template, new partials under
  `Resources/Private/Partials/Program/Page/`, the page TypoScript, new site
  settings and matching constants for static template installations.
- Visible output of every program page on sites with a page layout.
- Sites that reused or cleared index 100 of the page object's paths.
- Depends on `ace-tbd-program-page-content-without-getcontent`; the facts
  field list and the application link changes plug into the partials.

## Non-goals

- A multi-column backend layout for program pages.
- The facts field list and the call to action; separate changes.
- Partners and projects page templates.
- Removing or deprecating the content-load sets and
  `Partials/Program/Categories.html`; they are removed in 3.0 by
  `ace-tbd-program-page-content-without-getcontent`, the partner and project
  page template changes and `ace-tbd-program-facts-field-list`.
- A backport to branch `2`.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`programs-studyplan-03`). All six analysed projects carry their own code for
this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-program-page-layout-and-sections` when the issue is filed after
implementation.

Relates to ACE-450 and ACE-601.
