## Why

The list elements of `academic_partners` (`packages/fgtclb/academic-partners`),
`academic_programs` (`packages/fgtclb/academic-programs`) and
`academic_projects` (`packages/fgtclb/academic-projects`) apply the presets of
the content element (default categories, default sorting, for projects the
active state) only to a request without any selection of the visitor. Once a
visitor submits the visible part of the form, every part the editor hid loses
its preset, so the list shows what the editor hid the part to prevent
(ACE-916). The manuals document it as a pitfall.

## What Changes

- A part of the form the content element hides keeps the value of the content
  element: the sorting while the sorting is hidden and, for projects, the
  active state while the state select is hidden, whatever the request carries
  for it. While the category filter is hidden, every default category stays,
  and categories a submission or a link carries narrow the list further. A
  visible part keeps today's behaviour, and the reset link brings the presets
  back.
- A submission replaces the default categories of the category types its form
  offers and keeps those of every other type: the types the filter types of
  the site or the element leave out, and for programs the types the program
  finder does not offer, as the finder keeps the sorting since 3.0.
- The manuals of the list elements describe the new behaviour instead of the
  pitfall, and name the limit of two lists of one plugin on a page. The 2.4
  changelog of each extension gets an entry written for both branches, the
  per type rule a 3.0 entry per extension.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-partners/list-filter-urls`: a submission keeps the presets of the
  hidden parts of the form, and the defaults of the category types its form
  does not offer.
- `academic-projects/list-filter-urls`: the same, the active state included.
- `academic-programs/list-filter-urls`: the same for the program list.
- `academic-programs/program-finder`: the list the finder opens keeps the
  default categories of the types the finder does not offer, and all of them
  where it hides its filter.

## Impact

- The demand factory of each of the three extensions and the category filter
  normalizer of `category_types` (`packages/fgtclb/typo3-category-types`),
  which holds the shared rule, the manual pages of the list elements and of the
  program finder, a new `Documentation/Changelog/2.4/Important-*.rst` and a new
  `Documentation/Changelog/3.0/Important-*.rst` per extension, the 3.0 entries
  on filter URLs, the upgrade guides.
- TYPO3 v13 and v14 behave the same.
- No database, TCA, FlexForm, template or dependency changes.

## Non-goals

- Hidden form fields that carry the presets: a visitor can change them, and an
  override of the form template would have to keep them.
- Two lists of the same plugin on one page with different hidden parts: the
  first list builds the redirect, its hidden presets can override the other
  list's selection. Documented, not changed.
- Branch `2`, which gets its own change from the backport analysis.
