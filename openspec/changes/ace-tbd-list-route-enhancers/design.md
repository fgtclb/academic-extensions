## Context

See `proposal.md` for the motivation. Only `academic_persons`
(`Configuration/Routes/List.yaml`, `Detail.yaml`, `ListAndDetail.yaml`) and
`academic_jobs` (`Configuration/Routes/Detail.yaml`) ship route files; they
are imported by a site configuration, never loaded automatically. The persons
list file uses a `LocaleModifier` for the page key and a `StaticRangeMapper`
for the page number.

The arguments to map come from the preceding changes:

- `ace-tbd-list-filter-get-urls`: `demand[filterCollection][categories]`
  (flat uid list), `demand[sortingField]`, `demand[sortingDirection]`, and
  `demand[activeState]` for projects;
- `ace-tbd-partner-list-pagination`: `demand[currentPage]` (partners only);
- `ace-tbd-category-filter-route-aspect`: the `CategoryFilterMapper` aspect.

Sorting values on `main`: partners and programs `title`, `lastUpdated`,
`sorting`; projects additionally `tx_academicprojects_budget` and
`tx_academicprojects_start_date`; directions `asc`/`desc`. Active states:
`all`, `active`, `completed`.

## Goals / Non-Goals

**Goals:**

- Every argument combination generates and resolves.
- The files are copy-free: a site imports them and sets `limitToPages`.

**Non-Goals:**

- Route-level validation beyond what the aspects do.

## Decisions

### One route per combination, most specific first

Symfony routing omits only trailing defaults, so a single route
`/filter/{filter}/{sortingField}-{sortingDirection}/{page}` with defaults
404s for "filter only" and "sorting only" (ACE-623). Each non-empty subset
of the list's arguments gets its own route, ordered from most to least
specific:

- partners: filter, sorting, page: seven routes, for the `List` and the
  `Map` plugin (the map without page, three routes);
- projects: filter, sorting, active state: seven routes, for `ProjectList`
  and `ProjectListSingle`;
- programs: filter, sorting: three routes.

Rejected: leaving routing to each project; three projects would hand-write
the same combinatorics and hit the same trap.

### Explicit requirements for every variable

An aspect makes an Extbase route variable match greedily, so two variables
in one path swallow each other without a `requirements` entry. Every
variable gets an explicit pattern (`[^/]+` for the filter, the value lists
for sortings and states, `\d+` for the page).

### Localised keys and values

- static path keys through `LocaleModifier` (`filter`, `sortierung`/`sort`,
  `seite`/`page`, `status`);
- `sortingField` and `sortingDirection` each through a `StaticValueMapper`
  with a `localeMap`, as ACE-623 designs it;
- `activeState` through a `StaticValueMapper`;
- the filter through `CategoryFilterMapper` with the extension's group;
- the page through a `StaticRangeMapper` `1`–`1000`, as persons does.

### Documented import

Each extension's `Documentation/` shows the `imports:` line and a
`limitToPages` example, as for the persons files.

## Risks / Trade-offs

- [Route order decides which combination wins] → a functional router test
  per combination; a wrong order resolves the wrong list silently.
- [A project's own enhancer for the same plugin] → two enhancers compete; the
  documentation says to remove the project's file when importing ours.
- [New sorting options added later] → the `StaticValueMapper` lists must grow
  with them; a test compares them with the sorting options of the extension.
