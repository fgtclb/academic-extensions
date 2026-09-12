## Why

The page templates of the partner and project page types read the categories
of the page through a property neither model has. Fluid resolves the path to
nothing, so the category block of both page types has never rendered, and
four projects copy the whole page template for this block alone or drop it.

## What Changes

- The partner page template renders the categories assigned to the page,
  grouped by category type, through the accessor the partner list already
  uses.
- The project page template does the same for project pages.
- No model, TCA, TypoScript or setting changes.

The behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-partners/partner-page`: what a visitor sees of the categories
  assigned to a page of the partner page type.
- `academic-projects/project-page`: what a visitor sees of the categories
  assigned to a page of the project page type.

### Modified Capabilities

None.

## Impact

- `academic_partners` (`packages/fgtclb/academic-partners`): the page
  template `Resources/Private/Pages/AcademicPartner.html`.
- `academic_projects` (`packages/fgtclb/academic-projects`): the page
  template `Resources/Private/Pages/AcademicProject.html`.
- Visible output: a partner or project page with at least one category now
  shows the category list. Pages without categories render as before.
- Sites that override the page template are not affected.
- Functional tests of both page templates; an `Important-` changelog entry in
  each extension.

## Non-goals

- A public `categories` accessor on the partner or project model.
- Splitting the page templates into partials; that is a change of its own.
- Changing the markup or the order of the category block.
- Rendering categories on other page types.
- Taking the page templates off `styles.content.getContent` and removing the
  partner and project content-load sets; both belong to
  `ace-tbd-page-templates-sections-subtitle` (see the design).

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`listings-01`). Four of the six analysed projects carry their own code for
this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-page-template-category-block` when the issue is filed after
implementation.
