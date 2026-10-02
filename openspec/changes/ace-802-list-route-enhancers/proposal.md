## Why

With filter URLs, pagination and a category route aspect in place, a site
still has to write the route enhancer for each list itself. The naive
enhancer fails in a non-obvious way: the demo site's task ACE-623 documents
that "only filter", "only sorting" and "filter and sorting" on page one answer
404, because Symfony routing only omits trailing defaults. Every project
would otherwise hand-write the same combinatorics.

## What Changes

- Each list extension ships an importable `Configuration/Routes/List.yaml`:
  - `academic_partners` (`packages/fgtclb/academic-partners`): the partner
    list and map, with filter, sorting and page,
  - `academic_projects` (`packages/fgtclb/academic-projects`): both project
    lists, with filter, sorting and active state,
  - `academic_programs` (`packages/fgtclb/academic-programs`): the program
    list, with filter and sorting. It replaces the sorting-only enhancer in
    `Configuration/Yaml/Routes.yaml`, which now imports the new file.
- Every combination of the list's arguments gets its own route, so each
  combination generates and resolves.
- Static path keys and sorting and state values are localised (for example
  `seite` and `titel/aufsteigend` in German).
- Category filters use the aspect of `ace-782-category-filter-route-aspect`.
- The files are not loaded automatically. A site imports them in its site
  configuration and limits them to the list pages, exactly as the
  `academic_persons` route files are used today.
- The development instances import the three files.
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

- Three new YAML files, the former program file reduced to an import of the
  new one, and documentation of the import.
- No PHP, template or schema changes. Sites that import no file see no
  change. A site that imports the former program file gets the filter routes,
  and on a German site its English sorting paths answer 404.

## Non-goals

- Automatic loading of route files.
- Route files for other plugins (`academic_persons` and `academic_jobs`
  already ship theirs).
- Pagination for projects and programs.
- Backporting to branch `2`.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`listings-13`). Two of the six analysed projects carry their own code for this
today. ACE-623, named by the analysis, is the demo site's own task and was
solved in the demo project, so the change is filed as ACE-802, which relates
to ACE-623.

Implements ACE-802.
