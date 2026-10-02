# List route enhancers

The partner, project and program lists ship one route enhancer file each,
`Configuration/Routes/List.yaml`. A site imports it and limits each enhancer to
the pages of its plugin, and the list URLs of
[List filter URLs](list-filter-urls.md) become paths:

```text
/partners/filter/europe-1,university-3/title/desc/page-2
/de/partner/filter/europa-1,universitaet-3/titel/absteigend/seite-2
```

This page is about why the files look the way they do. How an integrator
imports them is in the route enhancer chapter of each extension's manual.

## What each file covers

| Extension           | Enhancers                                            | Arguments                        |
|---------------------|------------------------------------------------------|----------------------------------|
| `academic_partners` | `AcademicPartnersList`, `AcademicPartnersMap`        | filter, sorting, page (list)     |
| `academic_projects` | `AcademicProjectsList`, `AcademicProjectsListSingle` | filter, active state, sorting    |
| `academic_programs` | `AcademicPrograms`                                   | filter, sorting                  |

The segments come in that order. The filter, the state and the page carry a
static key in the language of the site (`filter`, `status/…`,
`page-2`/`seite-2`). The sorting is two segments without a key, field and
direction, as the program enhancer had them before.

## One route per combination

Symfony leaves a variable out of a generated path only when it equals its
default and nothing after it is left. A single route
`/filter/{categories}/{sorting_field}/{sorting_direction}/page-{page}` with
defaults for the sorting and the page therefore generates
`/filter/americas-2/title/asc/page` for a filter alone, and a sorting alone
falls back to query arguments. Every non-empty combination of the arguments is
a route of its own instead: seven for the partner list and for each project
list, three for the partner map and for the program list.

The order of the routes in the file does not matter. Of the routes whose
variables a link carries, `RouteSorter::compareAllVariablesPresence()` puts the
one with the most variables first, on TYPO3 v13.4 and v14.3 alike, so a link
with filter, sorting and page takes the route with all three wherever it
stands. The files list the routes from the most specific to the least for
reading. Resolving is not ambiguous either: the keys are static text, and a
route whose values the mappers reject falls through to the next one.

No enhancer declares `defaults`, for the reason
[List filter URLs](list-filter-urls.md#route-enhancers-must-not-declare-defaults-for-the-sorting)
gives: the bare page is where the content element's presets apply, and a link
with the default sorting must not lead there.

## Every variable is pinned

An aspect makes `AbstractEnhancer::applyRequirements()` give its variable the
pattern `.+`, which crosses slashes, so two mapped variables in one path swallow
each other. Every variable therefore has a requirement that stays within one
segment: `[^/]+` for the mapped ones, `[1-9][0-9]*` for the page.

The requirements do not list the values. The value mappers check them, in the
language of the site, and a path whose values a mapper does not know falls
through to the next route that matches, and to a 404 when none does. So an
English sorting or state on a German page is not a second address of the same
list. The filter is the exception: `CategoryFilterMapper` reads the uid of
each part and ignores its title, so a filter segment resolves under the title
of any language.

A value list in a requirement would have been stricter and is a trap: a site
that adds a language through `localeMap` and forgets the requirement gets an
`InvalidParameterException` from the URL generator on every link with a value
of that language, which breaks the page. With `[^/]+` a site adds `localeMap`
items to the aspects in its own site configuration and nothing else. The import
appends list items, so the site's own come after the shipped ones. A language
without items of its own uses the English keys and values, and a value missing
from the map of a language keeps its query argument in that language.

The page requirement has the same edge: a link with page `0` throws. The
paginators count from one, so the lists never build such a link.

## Static values and the page cache

Which arguments are static decides how many page cache entries a list page
gets, because static route arguments are part of the page cache identifier and
dynamic ones only together with a cHash, which these lists never carry (see
[Category filter routing](category-filter-routing.md#not-static-mappable)):

| Argument     | Aspect                 | Kind    | Page cache entries          |
|--------------|------------------------|---------|-----------------------------|
| filter       | `CategoryFilterMapper` | dynamic | one for all filters         |
| sorting      | `StaticValueMapper`    | static  | one per field and direction |
| active state | `StaticValueMapper`    | static  | one per state               |
| page         | none                   | dynamic | one for all pages           |

The `action` and `controller` a route maps are static arguments as well, so a
path without any static value is an entry, and the bare page another. That
makes eight entries per partner list, map or program list page and language
(six sortings, one path without a sorting, the bare page), and forty-five per
project list page (thirty for state and sorting, ten for a sorting alone, three
for a state alone, one for neither, the bare page). The lists always link state
and sorting, so a visitor fills the thirty. They are few and fixed, and a
static value is what lets the mapper translate it. The
page has no aspect on purpose: the profile list of `academic_persons` maps its
page with a `StaticRangeMapper` because its list action is cacheable, and the
partner list's is not. A range mapper here would make each page of each sorting
an entry of its own.

## The former program file

`academic_programs` shipped `Configuration/Yaml/Routes.yaml` with a sorting
route only. It now imports `Configuration/Routes/List.yaml`, and the enhancer
kept its key `AcademicPrograms`, so a site that imports the former path gets
the filter routes and keeps its `limitToPages`. Its sorting values are
translated now, so a German program list answers `/title/asc` with a 404 and
links `/titel/aufsteigend` instead.

## Tests

- `Routing/PartnerListRouteEnhancerTest` of `academic_partners`,
  `Routing/ProjectListRouteEnhancerTest` of `academic_projects` and
  `Routing/ProgramListRouteEnhancerTest` of `academic_programs` import the
  shipped file through the site configuration, as a site does. They generate
  every combination from plugin arguments, compare the path, and render it to
  see the list it resolves to, in English and German. They also check the
  links the list renders itself, the redirect of the filter form, and that a
  site without the import keeps its query arguments.
- The partner test counts page cache entries: two pages share one, two
  sortings have one each. It also adds French the way the manual describes.
- `Unit/Configuration/ListRoutesTest` of each extension compares the values of
  each mapper, per language, with the sorting options, and for projects the
  active states, of the extension, and checks that every variable stays within
  one segment and that no enhancer declares defaults.

## See also

- [List filter URLs](list-filter-urls.md): the arguments these routes map.
- [Category filter routing](category-filter-routing.md): the aspect of the
  filter segment.
- [TypoScript and site sets](typoscript-and-site-sets.md#route-enhancers-are-not-loaded-by-anything):
  how the development instances import the route files.
