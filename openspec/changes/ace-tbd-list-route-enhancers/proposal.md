## Why

With filter URLs, pagination and a category route aspect in place, a site
still has to write the route enhancer for each list itself. The naive
enhancer fails in a non-obvious way: ACE-623 documents that "only filter",
"only sorting" and "filter and sorting" on page one answer 404, because
Symfony routing only omits trailing defaults. Three projects would otherwise
hand-write the same combinatorics.

## What Changes

- Each list extension ships an importable `Configuration/Routes/List.yaml`:
  - `academic_partners` (`packages/fgtclb/academic-partners`): the partner
    list and map, with filter, sorting and page;
  - `academic_projects` (`packages/fgtclb/academic-projects`): both project
    lists, with filter, sorting and active state;
  - `academic_programs` (`packages/fgtclb/academic-programs`): the program
    list, with filter and sorting.
- Every combination of the list's arguments gets its own route, most
  specific first, so each combination generates and resolves.
- Static path keys and sorting values are localised (for example
  `filter`/`sortierung`/`seite` in German).
- Category filters use the aspect of `ace-tbd-category-filter-route-aspect`.
- The files are not loaded automatically. A site imports them in its site
  configuration and limits them to the list pages, exactly as the
  `academic_persons` route files are used today.
- Behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-partners/list-routing`: readable URLs for the partner list and
  map.
- `academic-projects/list-routing`: readable URLs for the project lists.
- `academic-programs/list-routing`: readable URLs for the program list.

### Modified Capabilities

None.

## Impact

- Three new YAML files; documentation of the import.
- No PHP, template or schema changes. Sites that do not import a file see no
  change.

## Non-goals

- Automatic loading of route files.
- Route files for other plugins (`academic_persons` and `academic_jobs`
  already ship theirs).
- Pagination for projects and programs.
- Backporting to branch `2`.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`listings-13`). Two of the six analysed projects carry their own code for this
today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-list-route-enhancers` when the issue is filed after implementation.

Implements ACE-623.
