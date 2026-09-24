## Context

See `proposal.md` for the motivation. On `main` the three list controllers
(`PartnerController`, `ProjectController`, `ProgramController`) take the
demand as `?array $demand` and hand it to their `DemandFactory`, which reads
`sorting` or `sortingField`/`sortingDirection`, `filterCollection` and, for
projects, `activeState`. The filter partials render an Extbase `f:form`
without a `method`, so `FormViewHelper` renders `post` (its argument default,
`cms-fluid` `FormViewHelper.php:111` on v14), and the selects submit on
change.

The map plugin renders the same `Partner/SortingAndFilters` partial, whose
form names `action="list"`. The URI builder resolves that action to the `List`
plugin, so the form's URL carries `tx_academicpartners_list` arguments, while
its fields are named after the plugin that renders them,
`tx_academicpartners_map[demand]`. The map therefore receives its own demand
today; the list namespace in the URL reaches no plugin on a map-only page.

All affected actions are registered as non-cacheable in each extension's
`ext_localconf.php` (partners `list` and `map`, projects `ProjectList` and
`ProjectListSingle`, programs `ProgramList`). The page around them is still
page-cached, and its cache identifier contains every argument the cache hash
covers (`PrepareTypoScriptFrontendRendering`, v13 and v14) - so the analysis'
open question 6 is not answered by the registration alone, see the cache hash
decision below.

`CategoryFilterNormalizer::toUidList()` in `category_types` turns any
submitted filter shape into a flat uid list; the factories resolve that list
with `CategoryRepository::findByGroupAndUidList()`, which already drops uids
outside the group.

`academic_programs` ships `Configuration/Yaml/Routes.yaml`, an Extbase route
enhancer that maps `demand/sortingField` and `demand/sortingDirection` into
the path and declared `defaults` of `title` and `asc` (ACE-454, not released
yet on either branch).

## Goals / Non-Goals

**Goals:**

- Post/Redirect/Get for the three lists, without template changes.
- One canonical GET shape of the demand, shared by the later pagination,
  active filter and routing changes.

**Non-Goals:**

- Making the actions cacheable.
- New route enhancers; the partner and project arguments stay in the plugin
  namespace, and the program enhancer keeps its one route.

## Decisions

### Redirect first in the action, from the submitted body, thrown

`listAction()` (and the partner `mapAction()`) first call a protected
`redirectFilterSubmission()`. When the request method is `POST` and the parsed
body carries a demand in the plugin's own namespace
(`ExtensionService::getPluginNamespace()`, through the final
`injectFilterRedirectExtensionService()`), it builds the demand object from
that body demand through the factory and throws a `PropagateResponseException`
carrying `$this->redirect($actionName, null, null, ['demand' => $arguments])`.
Extbase's `redirect()` answers `303` by default on v13 and v14
(`ActionController::redirect()`, `$statusCode = 303`); the `UriBuilder` builds
the target and the page router adds the cache hash.

Building the arguments from the demand object means the URL contains only what
the factory accepted: foreign category uids are already gone, and an invalid
active state has fallen back to its default. The redirect happens before the
demand event: the URL carries the visitor's selection, and a listener acts on
the request that follows the redirect. Protected, so a subclass that overrides
an action can keep the redirect with one call.

The body alone, not the action argument: on a POST Extbase merges the query
(and route) arguments into the body (`RequestBuilder`,
`array_replace_recursive`), and after the first redirect the URL carries a
demand itself. A form that posts to the URL it is on - an override without an
action, `addQueryString`, a script posting to `location.href` - would merge
the old flat filter back in, and a cleared category would return. A POST whose
body carries no demand of the plugin is another plugin's form, and the list
renders with the demand of the URL.

Thrown, not returned: a returned redirect does not become the PSR-7 response
of the page on TYPO3 v13. `Extbase\Core\Bootstrap::handleFrontendRequest()`
sends its status and headers there with `header()`, and the whole page renders
around the empty plugin; the browser gets the `303`, but no middleware and no
functional test sees it - measured: the PSR-7 response was `200`. v14 copies
the status into the page's `ResponseData`. The exception is caught by the
`ResponsePropagation` middleware on both versions, and
`ProductionExceptionHandler` rethrows an `ImmediateResponseException` instead
of rendering it as a content object error.

Rejected: injecting `ExtensionService` through the constructor; see the
factory decision below for why the controller constructors stay unchanged.

Rejected: `method="get"` on the forms. The URL would carry `__referrer` and
`__trustedProperties`, and the plugin arguments would arrive without a cache
hash, which answers 404 where `enforceValidation` is on - the setting every
new installation gets. It would also break every project override of the
filter partials that relies on the POST shape.

### One flat filter argument, produced in category_types

`CategoryFilterNormalizer::toUidList()` already discards the category type a
value was submitted under, and the factories resolve the types again through
the repository. The GET shape therefore carries the whole filter as one comma
list, `demand[filterCollection][categories]=12,31`, in ascending uid order so
one selection has exactly one URL. A new public method on the normaliser,
`toFilterArgument(?FilterCollection $filterCollection): string`, returns that
list from the resolved filter categories; the round trip with `toUidList()` is
unit tested in one class.

Each extension's `DemandFactory` assembles the rest (`sortingField`,
`sortingDirection`, `activeState`) in a new public method,
`createDemandArguments()`, the reverse of `createDemandObject()`; those keys
differ per extension and do not belong in `category_types`.

Rejected: a private method on each controller, as planned first. It needs the
normaliser, and a new constructor argument breaks every subclass of these
non-final controllers that calls `parent::__construct()` - ace-demo has one.
The factory already has the normaliser injected.

Rejected: one key per category type, the form's own shape. A route enhancer
maps one route variable to one argument, so per-type keys need one variable
per type (four for partners, thirteen for programs), and the route
combinations of `ace-tbd-list-route-enhancers` would multiply with them.
ace-demo's own enhancer uses a single `filters` variable for that reason.

Rejected: a shared trait in `academic_base` for the redirect. The three demand
classes do not share an interface, and a trait would have to know each of
them.

### A redirected URL always carries the sorting

Each `DemandFactory` applies the editor's preset categories
(`settings.categories`) and preset sorting only when no demand argument
arrives at all. A visitor who clears a preselected category must not get it
back after the redirect, so the redirect always carries `sortingField` and
`sortingDirection` (and, for projects, `activeState`), even when they equal
the default, and carries the filter argument only when a category is selected.
Only the bare list URL (first visit, reset link) applies the editor's preset,
exactly as a GET without arguments does today.

### The shipped program route enhancer declares no defaults

Symfony routing leaves a variable that equals its default out of a generated
path. With `defaults: title/asc` the redirect for the default sorting and no
filter was generated as the bare page URL, where the content element's presets
apply again - measured with the shipped file: clearing a preselected degree
redirected to `/home` and showed the preset degree only. The enhancer
therefore declares no `defaults`: every sorted URL has both segments,
`/home/title/asc` included, and the bare page stays the preset.

A one segment path such as `/home/last-updated` no longer resolves, and a link
without any sorting - the form's own action URL, a reset link - no longer maps
to the bare page but keeps `action`, `controller` and a `cHash` in the query
string; it still shows the preset list. No release carries a working enhancer
yet - ACE-454 made the file parseable and is in no tag on `main` or `2`.
Branch `2` carried the repaired file with its `defaults` for 2.4; the
maintainer decided to drop them there before 2.4.0 as well, under ACE-723 in a
pull request of its own on that branch (#732), without an OpenSpec change: its
forms still POST, so no redirect collapses there, but a link asking for
`title` and `asc` landed on the bare page and showed the sorting configured in
the content element. There, too, a link without any sorting - the action URL
of the plugin's own form - now keeps `action`, `controller` and a `cHash` in
its query string, so after a submit the address bar shows that URL instead of
the bare page. With both branches alike, the paths the enhancer generates with
2.4 keep resolving on 3.0. Decided with the maintainer over keeping `defaults`
and documenting the gap, or carrying an extra argument outside the route so
the URL never collapses.

### The demand is excluded from the cache hash

With the demand in the cache hash, the redirect hands every anonymous visitor
a valid hash for any subset of the group's categories, times the sortings
(times the active states for projects), and each is a page cache entry of its
own - before this change nothing signed a demand. Each list extension
therefore appends `^tx_<extension>_<plugin>[demand]` to
`FE.cacheHash.excludedParameters` in its `ext_localconf.php`; `^` is a prefix
match on v13 and v14 (`CacheHashConfiguration`). The actions are
non-cacheable, so the cached page never depends on the demand, and every
filter URL of a list shares one page cache entry. The URL keeps a `cHash` over
`action` and `controller`. Behind the shipped program route enhancer, which
maps those and puts the sorting into the path, it carries none, and each of
the six sorting paths is a page cache entry of its own: the
`StaticValueMapper` segments are static route arguments, and the page cache
identifier contains them. Decided with the maintainer over keeping the demand
in the hash and documenting the growth.

The setting is installation-wide, appended to what the installation
configures, and names these plugin namespaces only; a namespace changed with
`view.pluginNamespace` is not covered. Nothing else in the cached page may
read the demand either - a link with `addQueryString = untrusted` would carry
the demand of whoever filled the cache entry.

### Decided: the list actions stay non-cacheable

The partner `list` and `map`, the project `ProjectList` and
`ProjectListSingle` and the program `ProgramList` actions stay registered as
non-cacheable. The cache hash exclusion depends on it: a cacheable action
renders its demand into the cached page, and one cached page would answer
every filter URL.

Rejected for this change: making the actions cacheable and accepting one page
cache entry per filter combination. That is a performance decision of its own,
with its own measurements, and belongs to a later change.

## Risks / Trade-offs

- [A project's filter partial adds a field of its own and a listener reads it
  from the request] → the redirect carries the arguments the demand factory
  knows and nothing else, so the field is lost on the GET that follows; named
  in the Feature entries. A way to add arguments to the redirect, an event or
  a hook in `createDemandArguments()`, is a follow-up.
- [A project posts the form with `fetch()` and parses the HTML body] →
  `fetch()` follows 303 by default; named in the changelog entry, together
  with the redirect keeping the plugin's arguments only - a page type or
  another plugin's arguments of the submitted URL are dropped.
- [A project overrides `listAction()` in a subclass] → the subclass keeps
  working but does not redirect unless it calls the parent action or the
  protected `redirectFilterSubmission()` first; the ace-demo overlay removes
  its own PRG code in the same upgrade.
- [A site's own route enhancer declares `defaults` for the sorting] → the same
  collapse to the bare page; the programs changelog entry and the route
  enhancer documentation say why the shipped one has none.
- [A project registers a list action as cacheable] → one cached page would
  answer every filter URL; the Important changelog entry says to remove the
  cache hash exclusion then, and that every filter URL shared until then
  answers with a 404 afterwards.
- [The editor hides the sorting select, the category filter or the project
  active state] → the form posts no sorting, no categories or no active state,
  the factory ignores the preset sorting, the preset categories and the preset
  active state once any demand arrives, and the redirect writes the default
  sorting, an unrestricted list or `activeState=all` into a shareable URL.
  With a hidden filter that drops the editor's category restriction; with a
  hidden active state, a list preset to active projects shows completed ones.
  This is how the POST behaved before; falling back to the presets for what
  the form does not offer is a follow-up.

## Migration Plan

Nothing to migrate in the database or configuration, but URLs change. A URL
whose `cHash` was computed with the demand and that carries `action` or
`controller` as well - as every URL `UriBuilder::uriFor()` builds does -
produced by a project's own redirect after the POST, or by a template link
with demand arguments, fails the cache hash check after the update and answers
with a 404 under the default `pageNotFoundOnCHashError`; the Important entries
say so, and the Feature entries point there where they tell such projects to
drop their redirect.

A rollback restores POST rendering, and with it the demand as part of the
cache hash: a filter URL shared until then carries a `cHash` computed without
the demand and answers with a 404 the same way. The enhanced program URLs
carry no `cHash`; they keep rendering, and answer 404 only with
`enforceValidation` on, which new installations have.

## Open Questions

None.
