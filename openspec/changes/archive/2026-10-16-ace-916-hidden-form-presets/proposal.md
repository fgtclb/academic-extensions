## Why

The list elements of `academic_partners` (`packages/fgtclb/academic-partners`),
`academic_programs` (`packages/fgtclb/academic-programs`) and
`academic_projects` (`packages/fgtclb/academic-projects`) apply the presets of
the content element (default categories, default sorting, for projects the
active state) only to a request without any selection of the visitor. Once a
visitor submits the visible part of the form, every part the editor hid loses
its preset, so the list shows what the editor hid the part to prevent
(ACE-916). On this branch the program list loses its default sorting as well,
since the ACE-91 fallback of `main` is not here.

This is the backport of ACE-916, made on `main` in pull request #991, whose
OpenSpec change it re-derives for TYPO3 v12 and v13.

## What Changes

- A part of the form the content element hides keeps the value of the content
  element: the sorting while the sorting is hidden, and for projects the
  active state while the state select is hidden, whatever the request carries
  for it.
- The default categories of a hidden category filter always apply, in all
  three extensions. Categories a submission or a URL carries for such an
  element are added to them, so a visitor can narrow the list but never widen
  it, since a record has to carry every category.
- A visible part keeps today's behaviour: the visitor's selection replaces the
  preset.
- The behaviour is the same on TYPO3 v12 and v13.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-partners/list-filter`: the default categories of a hidden category
  filter hold for every request of the list and the map, and requested
  categories narrow them.
- `academic-partners/list-sorting`: the default sorting of a hidden sorting
  holds for every request of the list and the map.
- `academic-projects/list-filter`: the default categories, the default sorting
  and the active state of a hidden part hold for every request of both project
  lists, and requested categories narrow the default categories.
- `academic-programs/list-filter`: the default categories of a hidden category
  filter hold for every request of the program list, and requested categories
  narrow them.
- `academic-programs/program-list-sorting`: the default sorting of a hidden
  sorting holds for every request of the program list.

## Impact

- The demand factory of each of the three extensions.
- A new `Documentation/Changelog/2.4/Important-HiddenFormPartsKeepTheirPresets.rst`
  per extension and a bullet in each `Documentation/Upgrade/From23To24.rst`,
  byte-identical to `main`.
- No database, TCA, FlexForm, template, label or dependency change. Template
  overrides keep working, since the factory reads the settings the templates
  read.

## Non-goals

- Hidden form fields that carry the presets: a visitor can change them, and an
  override of the form template would have to keep them.
- The program finder of `main`, and the rule of 3.0 that a submission of the
  visible filter replaces only the default categories of the types it names.
  Neither is part of 2.4.
- The ACE-91 fallback of a program demand without a sorting, not on this
  branch.
- Two lists of the same plugin on one page share their arguments. `main`
  documents that limitation on its integrator pages, which this branch does
  not have.
- The redirect target, pagination links and active filter tags of `main`.
  This branch renders the list in the answer to the post and has none of them.
