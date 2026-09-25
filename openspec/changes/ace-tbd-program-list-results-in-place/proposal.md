## Why

The filter and sorting selects of the program list submit their form with an
inline `onchange="this.form.submit()"`: every change reloads the whole page,
and without JavaScript the form cannot be submitted at all, because it has no
button. Pull request #516 (ACE-91) re-rendered the results through `fetch()`
instead; that part of it is not the finder ACE-91 asks for, so it is taken up
here as a change of its own, on top of the filter URLs of
`ace-723-list-filter-get-urls`.

## What Changes

- The program list of `academic_programs` (`packages/fgtclb/academic-programs`)
  updates its results and its filter form in place when a visitor changes a
  filter or the sorting, without reloading the page. Every program list does
  so; there is no setting to switch it on.
- The address bar follows: after an update it shows the filter URL the list
  redirects a submission to, so the result can still be bookmarked, shared and
  reloaded, and the browser's back and forward buttons step through the
  selections.
- Screen reader users hear that the results changed, through a polite status
  message with the number of programs found.
- Without JavaScript the form gets a submit button and works as a plain form;
  with JavaScript the button is hidden and a change updates the list.
- The inline `onchange` handlers are removed from the list's partials; a
  frontend module, built from TypeScript and published through the import map,
  takes over.
- Project overrides of the list partials keep working: the module attaches
  to the form by the class it carries today, and where an override lacks the
  new results region it submits the form as before, with a page reload.

Behaviour is identical on TYPO3 v13 and v14; the change is frontend only.

## Capabilities

### New Capabilities

- `academic-programs/program-list-results-in-place`: updating the program list
  without a reload, the address bar, the announcement and the no-JavaScript
  form.

### Modified Capabilities

None. The filter URLs (`academic-programs/list-filter-urls`) keep their shape;
the list only requests them without a page reload.

## Impact

- New TypeScript module, committed build output and import map in
  `academic_programs`, which ships no frontend module today (the finder
  narrowing change `ace-tbd-finder-client-side-narrowing` adds one too;
  whichever lands first adds the import map).
- The partials `Program/SortingAndFilters.html`, `Program/DemandCategories.html`,
  `Program/DemandSorting.html` and `Program/ItemList.html`.
- New `testJs` coverage and functional tests for the markup contract.
- No database, TCA or PHP API change.

## Non-goals

- The partner and project lists, which carry the same inline handlers; they
  can adopt the module once it exists.
- A JSON endpoint or a page type of its own; see `design.md`.
- The program finder; see `ace-91-program-finder-element` and
  `ace-tbd-finder-client-side-narrowing`.
- Backporting to branch `2`.

## Source

Adopted from pull request #516 (ACE-91), whose `filter-ajax.js` replaced the
list results through `fetch()`. Split off when the finder of #516 was replaced
by `ace-91-program-finder-element`, as the maintainer decided on 2026-09-25.
No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-program-list-results-in-place` when the issue is filed after
implementation.
