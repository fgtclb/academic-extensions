## Why

The persons list switches pagination off while a letter is active, so a
letter with many profiles, such as S in a large institute, renders every
one of them on a single page. That setting belongs to the editor, and the
letter overrides it without telling anyone.

The switch-off made sense while page links dropped the letter. Once
`ace-tbd-list-links-keep-state` keeps the letter in every page link, it has
no technical reason left. The maintainer decided, with that change, that
pagination under an active letter is allowed, but in a change of its own:
this one.

## What Changes

- With pagination enabled, a list with an active letter is paginated like any
  other list. The page count reflects the profiles of that letter.
- Page links under an active letter keep the letter. Choosing another letter,
  or going back to all letters, starts again at the first page.
- The `ProfileListPlugin` and `ProfileListAndDetailPlugin` route enhancers get
  a route for a letter together with a page, so such a page has a readable
  URL, for example `/persons/m/page-2`.
- The route enhancer documentation drops the caveat that the letter and page
  routes are alternatives and cannot be combined.

Behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-persons/letter-filtered-pagination`: how the persons list pages
  through the profiles of an active letter, and which URL such a page has.

### Modified Capabilities

None.

## Impact

- `academic_persons` (`packages/fgtclb/academic-persons`): the profile
  controller's list action, `Configuration/Routes/List.yaml` and
  `Configuration/Routes/ListAndDetail.yaml`, the route enhancer documentation
  and the 3.0 changelog.
- Plugins `list` and `listanddetail`, and only when the editor enabled
  pagination. Lists without pagination are unchanged.
- Existing content elements with both pagination and the letter navigation
  enabled show fewer profiles per letter page than before.
- No schema change, no new setting, no template change.
- Depends on `ace-tbd-list-links-keep-state` for page links that keep the
  letter, on `ace-tbd-visitor-filter-ui-routes` for the route set it extends,
  and on `ace-tbd-letter-navigation-availability` for the letter navigation it
  pages under.

## Non-goals

- An option to keep the old "one page per letter" behaviour.
- Routes for a letter combined with a visitor filter, with or without a page.
  They follow the open question of `ace-tbd-visitor-filter-ui-routes`, and such
  URLs keep query parameters.
- A single URL for the first page of a letter. The first page link keeps its
  page number, as it does for a list without a letter.
- A manual profile selection, which ignores the letter and shows no letter
  navigation.
- A backport to branch `2`.

## Source

Follows from the maintainer's decision on `ace-tbd-list-links-keep-state`
(project differences analysis of 2026-09-12). No YouTrack issue is filed yet;
the change is renamed to `ace-<NNN>-letter-navigation-pagination` when the
issue is filed after implementation.
