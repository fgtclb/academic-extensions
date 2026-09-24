## Why

The filter and sorting forms of the partner, project and program lists are
submitted as POST requests. A filtered list therefore has no URL of its own:
a visitor cannot bookmark or share it, a reload asks to re-send the form, and
any link rendered inside the list (pagination, active filter tags, readable
routes) cannot carry the filter. Projects work around it with a controller
subclass today.

## What Changes

- A list plugin that receives a filter or sorting submission as POST answers
  with a `303 See Other` redirect to the same list, carrying the submitted
  selection as GET arguments. The selection is read from the submitted form
  alone, so a form posted to a filtered URL replaces its selection.
- The selection is not part of the cache hash, so every filter URL of a list
  shares one page cache entry instead of the redirect signing one per
  submitted combination.
- The redirect carries only the normalised selection: the category filter as
  one uid list, the sorting field and direction, and for projects the active
  state. Unknown or foreign category uids and form bookkeeping arguments are
  dropped.
- Clearing a category the editor preselected stays cleared after the
  redirect; the editor's preselection applies only to the bare list URL.
- A GET of that URL renders the list the POST rendered before.
- The shipped program route enhancer declares no default sorting any more, so
  the redirect never ends on the bare page, where the preselection applies.
- Affected: `academic_partners` (`packages/fgtclb/academic-partners`), list
  and map; `academic_projects` (`packages/fgtclb/academic-projects`), both
  lists; `academic_programs` (`packages/fgtclb/academic-programs`), list and
  route enhancer; `category_types` (`packages/fgtclb/typo3-category-types`),
  the reverse of its filter normalisation.
- No template changes. Behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-partners/list-filter-urls`: filter and sorting selections of the
  partner list and map become bookmarkable GET URLs.
- `academic-projects/list-filter-urls`: the same for the project lists,
  including the active state.
- `academic-programs/list-filter-urls`: the same for the program list.

### Modified Capabilities

None.

## Impact

- Each list extension adds its plugins' demand to the installation-wide
  `FE.cacheHash.excludedParameters`.
- A filter POST answers 303 instead of 200; script that reads the HTML body
  of the POST must follow the redirect.
- The program route enhancer generates `/title/asc` for the default sorting,
  and `/last-updated` alone no longer resolves; 2.4 will be the first release
  with a working copy.
- No database schema, TCA or dependency changes.

## Non-goals

- Pagination, active filter tags and readable route segments; they build on
  this change as separate changes.
- Switching the forms to `method="get"`.
- Changing which filters or sortings exist.
- Backporting the redirect to branch `2`; only the route enhancer's
  `defaults` are dropped there too, in a pull request of its own without an
  OpenSpec change.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`listings-09`). Two of the six analysed projects carry their own code for this
today. Filed after implementation as ACE-723, which relates to ACE-125.
