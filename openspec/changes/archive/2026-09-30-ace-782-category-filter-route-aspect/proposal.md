## Why

Once list filters are GET URLs, a filtered list reads
`?tx_academicpartners_list[demand][filterCollection][categories]=12` next to
the plugin's action and controller (`ace-723-list-filter-get-urls`).
Projects want `/partner/filter/europa-12` instead, in the visitor's language.
`sys_category` has no slug column, so the core mappers cannot produce that,
and each project writes its own routing aspect today.

## What Changes

- `category_types` (`packages/fgtclb/typo3-category-types`) ships a routing
  aspect for category filter values, usable in any route enhancer:
  - it turns one category uid or a comma list of uids into a URL segment of
    `<title-slug>-<uid>` parts, joined by commas, with the title in the
    language of the generated URL;
  - on resolve it reads only the trailing uid of each part and accepts it
    only when the category exists and belongs to the configured category
    group;
  - an empty value maps to a configurable, localisable token such as
    `all` / `alle`.
- Options: the category group (for example `partners`) and the locale map
  of the empty token.
- The filter stays a dynamic route argument, never a static one, and a
  dynamic argument reaches the page cache identifier only through a cHash.
  The lists exclude their demand from the cache hash
  (`ace-723-list-filter-get-urls`), so their filter URLs carry no cHash and
  share one page cache entry.
- A renamed category changes its generated URL. The old URL keeps resolving,
  because only the uid is read.
- Behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `typo3-category-types/filter-route-aspect`: readable, translated URL
  segments for category filter values.

### Modified Capabilities

None.

## Impact

- A new aspect type registered in the system routing configuration.
- No schema change: the slug is computed at runtime from the title.
- Used by `ace-tbd-list-route-enhancers`, and unused until a site's route
  enhancer references it.

## Non-goals

- A persisted slug column on `sys_category`.
- Route enhancer files for the lists (`ace-tbd-list-route-enhancers`).
- Redirects from old slugs to new ones.
- Backporting to branch `2`.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`listings-12`). Two of the six analysed projects carry their own code for this
today. ACE-623 is the demo site's own task for readable list URLs and was
solved in the demo project, so this change has an issue of its own, ACE-782,
which relates to it.
