## Context

See `proposal.md` for the motivation. `academic_persons`
(`Configuration/Routes/List.yaml`, `Detail.yaml`, `ListAndDetail.yaml`) and
`academic_jobs` (`Configuration/Routes/Detail.yaml`) ship route files, and so
does `academic_programs`: `Configuration/Yaml/Routes.yaml`, one route for the
sorting, `/{sorting_field}/{sorting_direction}`, no defaults, enhancer key
`AcademicPrograms`. All of them are imported by a site configuration, never
loaded automatically, and the development instances import the program file.

The arguments to map, as merged:

- `ace-723-list-filter-get-urls`: `demand[filterCollection][categories]`
  (flat uid list), `demand[sortingField]`, `demand[sortingDirection]`, and
  `demand[activeState]` for projects. Sorting and state are always carried.
- `ace-727-partner-list-pagination`: `demand[currentPage]`, partners only.
- `ace-782-category-filter-route-aspect`: the `CategoryFilterMapper` aspect.

Sorting values on `main`: partners and programs `title`, `lastUpdated`,
`sorting`, projects additionally `tx_academicprojects_budget` and
`tx_academicprojects_start_date`, directions `asc`/`desc`. Active states:
`all`, `active`, `completed`.

## Goals / Non-Goals

**Goals:**

- Every argument combination generates and resolves.
- The files are copy-free: a site imports them and sets `limitToPages`.

**Non-Goals:**

- Route-level validation beyond what the aspects do.

## Decisions

### One route per combination

Symfony routing omits only trailing defaults, so a single route
`/filter/{filter}/{sortingField}/{sortingDirection}/{page}` with defaults
generates `/filter/americas-2/title/asc/page` for a filter alone and falls
back to query arguments for a sorting alone. Each non-empty subset of the
list's arguments gets its own route:

- partners: filter, sorting, page: seven routes for the `List` plugin, three
  for `Map` (filter and sorting),
- projects: filter, active state, sorting: seven routes, for `ProjectList`
  and `ProjectListSingle`,
- programs: filter, sorting: three routes.

The order of the routes decides nothing. Of the routes whose variables a link
carries, `RouteSorter::compareAllVariablesPresence()` sorts the one with the
most variables first, identical on v13.4.35 and v14.3.7, and reversing the
routes keeps every test green. The files list them most specific first for
reading only.

Rejected: leaving routing to each project. Every project would hand-write the
same combinatorics and hit the same trap.

### Segments

`/filter/<categories>`, then for projects `/status/<state>`, then
`/<field>/<direction>`, then for partners `/page-<n>`. The filter, state and
page keys are `LocaleModifier`s (`page`/`seite`, while `filter` and `status`
are the same word in both languages). The sorting has no key, which keeps the
English paths of the former program enhancer (`/title/asc`).

### Every variable stays within one segment

An aspect makes an Extbase route variable match `.+`, so every variable gets
a requirement: `[^/]+` for the mapped ones and `[1-9][0-9]*` for the page.
The value mappers check sorting and state per language, and a path they do
not know falls through to the next route, finally a 404. The filter resolves
by uid in any language.

Rejected: requirements listing the values of both languages. Stricter, but a
site that adds a language through `localeMap` and forgets the requirement gets
an uncaught `InvalidParameterException` from the URL generator on every link
in that language. With `[^/]+` a site adds `localeMap` items only.

### Localised values, decided by the maintainer

`sortingField`, `sortingDirection` and `activeState` each go through a
`StaticValueMapper` with a `localeMap` for `de.*`, in the program file too.
German program sorting paths published under the English values answer 404
after the update, English ones keep resolving. Rejected: English values in
every language, which kept every program URL but leaves German sites with
English paths.

### Static and dynamic arguments

Static route arguments are part of the page cache identifier, dynamic ones
only with a cHash, which the lists never carry because their demand is
excluded. The `action` and `controller` a route maps are static as well.

- Sorting and state are static (`StaticValueMapper`): eight entries per
  partner list, map or program list page and language (six sortings, a path
  without sorting, the bare page), forty-five per project list page.
- The filter is dynamic, `CategoryFilterMapper` is not static mappable.
- The page is dynamic, it has no aspect at all. The persons list maps its page
  with a `StaticRangeMapper` because its action is cacheable. Here it would
  make each page of each sorting a cache entry of its own.

### The former program file, decided by the maintainer

`Configuration/Yaml/Routes.yaml` keeps existing as an `imports:` of
`Configuration/Routes/List.yaml`, and the enhancer keeps the key
`AcademicPrograms`, so a site importing the old path gets the new routes and
its `limitToPages` still applies. `YamlFileLoader` processes imports inside
an imported file. Rejected: deleting it, since TYPO3 only logs a missing
import and the site would silently lose its routes. Also rejected: leaving it
sorting-only, which makes two enhancers for one plugin when a site imports
both.

### Documented import

Each extension's `Documentation/` shows the `imports:` line, a `limitToPages`
example and a `localeMap` example for a further language.

## Risks / Trade-offs

- [German program sorting paths change] → `Important-RouteEnhancerMoved.rst`
  in academic_programs names the 404 and suggests redirects.
- [A project's own enhancer for the same plugin] → two enhancers compete. The
  documentation says to remove the project's file when importing ours.
- [New sorting options added later] → the `StaticValueMapper` lists must grow
  with them. `Unit/Configuration/ListRoutesTest` compares them, per language,
  with the sorting options and states of the extension.
