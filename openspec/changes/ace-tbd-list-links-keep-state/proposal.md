## Why

The pagination links of the persons list carry only the page number and the
letter links only the letter. Every other value a visitor chose is dropped on
the next click. One project copied both navigation partials to forward its
filter, and another reports the list jumping back to the tile view when
paging.

On `main` the only visitor values today are the page and the letter, and
pagination is switched off while a letter is active. The loss therefore
becomes visible with the changes that add visitor values:
`ace-tbd-list-view-modes` (view mode) and `ace-tbd-visitor-filter-demand-query`
(filters). This change provides the mechanism both rely on.

## What Changes

- The list action exposes the set of active visitor values to the view,
  taken from the values the plugin accepted and validated, never from the
  raw request.
- The pagination and letter navigation build their links from that set and
  change only the value they are responsible for; a letter link starts again
  at the first page.
- Query parameters the plugin does not accept are never carried into these
  links.
- The later changes extend the one list of accepted visitor values instead of
  editing the partials.

Behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-persons/list-navigation-state`: which visitor choices survive a
  click on a pagination or letter link of the persons list.

### Modified Capabilities

None.

## Impact

- `academic_persons` (`packages/fgtclb/academic-persons`): the profile
  controller's list action, `Partials/Profile/List/Pagination.html` and
  `Partials/Profile/List/AlphabetPagination.html`, one small ViewHelper, the
  template documentation and the 3.0 changelog.
- Plugins `list` and `listanddetail`.
- Overrides of the two partials keep working. They only benefit once they
  adopt the new link arguments.
- No schema change, no new setting.

## Non-goals

- Pagination while a letter is active; it stays switched off here. A
  separate follow-up change allows it, and extends the route set of
  `ace-tbd-visitor-filter-ui-routes` with the letter and page combinations.
- The view mode and filter values themselves, which belong to the changes
  named above.
- Route enhancer entries for further values.
- A backport to branch `2`.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`persons-display-10`). Two of the six analysed projects carry their own code
for this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-list-links-keep-state` when the issue is filed after
implementation.
