## Why

Program, partner and project pages each ship a category summary for the page
module, and none of them ever appears: the partials are registered as an
override of a core backend partial that no core template renders, on TYPO3
v13 and v14 alike. The type labels they translate do not exist either. Two
projects built their own workaround, one of them an unregistered copy of the
core page module template.

## What Changes

- The page module shows a summary of the assigned categories, grouped by
  category type, above the content grid of a program page (doktype 20), a
  project page (doktype 30) and a partner page (doktype 40).
- Every type of the page's category group is listed, with its registered
  title and icon; a type without categories says so, hidden categories are
  marked.
- The labels come from the registered category type titles, so types an
  integrator adds through `CategoryTypes.yaml` are labelled too.
- The dead backend template override registration is removed from the page
  TSconfig of the three extensions, together with their unused partials.
- The summary template can be overridden through page TSconfig.

Behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-programs/page-module-category-summary`: the page module shows
  the categories of a program page.
- `academic-partners/page-module-category-summary`: the page module shows
  the categories of a partner page.
- `academic-projects/page-module-category-summary`: the page module shows
  the categories of a project page.

### Modified Capabilities

None.

## Impact

- `category_types` (`packages/fgtclb/typo3-category-types`): one shared,
  stateless summary renderer and its backend template.
- `academic_programs` (`packages/fgtclb/academic-programs`),
  `academic_partners` (`packages/fgtclb/academic-partners`) and
  `academic_projects` (`packages/fgtclb/academic-projects`): one event
  listener each; `Configuration/page.tsconfig` loses the
  `templates.typo3/cms-backend.*` line; the `Doktype20/30/40.html` partials
  are removed.
- A project that overrode one of those partials through the removed key keeps
  seeing nothing, as before, and moves its override to the new key.
- No database schema change, no new dependency.

## Non-goals

- Changing which categories a page carries, or their order (the order
  follows `ace-tbd-category-type-priority-order`).
- A summary for any other page type or for records outside the page module.
- Making the summary editable.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`programs-studyplan-04`). Two of the six analysed projects carry their own
code for this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-page-module-category-summary` when the issue is filed after
implementation.
