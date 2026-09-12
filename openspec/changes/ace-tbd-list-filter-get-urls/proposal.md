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
  selection as GET arguments with a valid cache hash.
- The redirect carries only the normalised selection: the category filter as
  one uid list, the sorting field and direction, and for projects the active
  state. Unknown or foreign category uids and form bookkeeping arguments are
  dropped.
- Clearing a category the editor preselected stays cleared after the
  redirect; the editor's preselection applies only to the bare list URL.
- A GET request with those arguments renders the filtered list exactly as the
  POST did before.
- Affected extensions:
  - `academic_partners` (`packages/fgtclb/academic-partners`), list and map
    plugins;
  - `academic_projects` (`packages/fgtclb/academic-projects`), both list
    plugins;
  - `academic_programs` (`packages/fgtclb/academic-programs`), list plugin;
  - `category_types` (`packages/fgtclb/typo3-category-types`), which gains
    the reverse of its existing filter normalisation.
- No template changes; overridden filter templates keep working.
- Behaviour is identical on TYPO3 v13 and v14.

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

- The list actions of three extensions; the category filter normalisation in
  `category_types`.
- A filter POST now answers 303 instead of 200. Project JavaScript that posts
  the form with `fetch()` and reads the HTML body must follow the redirect
  (browsers and `fetch()` do so by default).
- No database schema, TCA or dependency changes.

## Non-goals

- Pagination, active filter tags and readable route segments; they build on
  this change as separate changes.
- Switching the forms to `method="get"`.
- Changing which filters or sortings exist.
- Backporting to branch `2`.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`listings-09`). Two of the six analysed projects carry their own code for this
today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-list-filter-get-urls` when the issue is filed after implementation.

Relates to ACE-125.
