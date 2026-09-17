## Why

The page templates of the partner and project page types read the categories
of the page through a property neither model has. Fluid resolves the path to
nothing, so the category block of both page types has never rendered, and
projects on the 2.x line copy the whole page template for this block alone or
drop it.

This is the backport of ACE-673, merged on `main` for 3.0.0
(`openspec/changes/archive/2026-09-17-ace-673-page-template-category-block`
there). It is re-derived against this branch: the category lines, the models
and the registered category types are the same here, so the change carries
over unchanged apart from the core versions it names.

## What Changes

- The partner page template renders the categories assigned to the page,
  grouped by category type, through the accessor the partner list already
  uses.
- The project page template does the same for project pages.
- No model, TCA, TypoScript or setting changes.

The behaviour is identical on TYPO3 v12 and v13.

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
  each extension for 2.4.

## Non-goals

- A public `categories` accessor on the partner or project model.
- Changing the markup or the order of the category block.
- Rendering categories on other page types.
- Anything of the page template rework `main` plans for 3.0; the content
  rendering of both templates stays as it is on this branch.
