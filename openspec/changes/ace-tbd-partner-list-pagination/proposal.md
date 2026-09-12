## Why

The partner list renders every matching partner on one page. Institutions
with a few hundred partners get a very long page, and the only way to page it
today is to subclass the upstream controller and re-register the plugin, as
ace-demo does. `academic_persons` already paginates its list; the partner
list should offer the same.

## What Changes

- The partner list content element of `academic_partners`
  (`packages/fgtclb/academic-partners`) gains an optional pagination:
  - a FlexForm switch "Enable pagination" (off by default) and a "Results per
    page" field;
  - a site setting for the number of page links, as `academic_persons` has.
- With pagination enabled, the list shows one page of partners and a
  pagination navigation below it. Numbered page links are used when
  `georgringer/numbered-pagination` is installed, previous/next links
  otherwise.
- Pagination links keep the active filter and sorting, using the GET URL
  shape of `ace-tbd-list-filter-get-urls`. A new filter submission starts at
  page 1.
- The partner map is not paginated.
- Behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-partners/list-pagination`: optional pagination of the partner
  list that keeps filter and sorting.

### Modified Capabilities

None.

## Impact

- `ListSettings.xml`, the list action, the demand and its factory, a new
  `Partner/Pagination.html` partial, TypoScript and site settings, labels.
- An installation that subclasses `PartnerController` to paginate (ace-demo)
  has to remove that subclass in the same upgrade, or it paginates twice.
- `georgringer/numbered-pagination` stays optional (a `suggest`, as in
  `academic_persons`).
- No schema changes.

## Non-goals

- Pagination for the project and program lists (can follow the same shape
  when a project asks).
- A shared pagination helper in `academic_base`.
- Infinite scrolling or "load more" buttons.
- Backporting to branch `2`.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`listings-11`). One of the six analysed projects carries its own code for this
today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-partner-list-pagination` when the issue is filed after
implementation.

Relates to ACE-125.
