## Context

See `proposal.md` for the motivation. On `main` the three list controllers
(`PartnerController`, `ProjectController`, `ProgramController`) take the
demand as `?array $demand` and hand it to their `DemandFactory`, which reads
`sorting` or `sortingField`/`sortingDirection`, `filterCollection` and, for
projects, `activeState`. The filter partials render an Extbase `f:form`
without a `method`, so `FormViewHelper` renders `post` (its argument default,
`cms-fluid` `FormViewHelper.php:110`), and the selects submit on change.

All affected actions are registered as non-cacheable in each extension's
`ext_localconf.php` (partners `list` and `map`, projects `ProjectList` and
`ProjectListSingle`, programs `ProgramList`). A GET URL with a cache hash
therefore does not multiply page cache entries per filter combination, which
answers open question 6 of the analysis for the current registration.

`CategoryFilterNormalizer::toUidList()` in `category_types` turns any
submitted filter shape into a flat uid list; the factories resolve that list
with `CategoryRepository::findByGroupAndUidList()`, which already drops uids
outside the group.

## Goals / Non-Goals

**Goals:**

- Post/Redirect/Get for the three lists, without template changes.
- One canonical GET shape of the demand, shared by the later pagination,
  active filter and routing changes.

**Non-Goals:**

- Making the actions cacheable.
- A route enhancer; the GET arguments stay in the plugin namespace.

## Decisions

### Redirect in the action, after the demand is built

`listAction()` (and the partner `mapAction()`) keep building the demand
object as today. When the request method is `POST` and a demand argument was
submitted, the action returns `$this->redirect($actionName, null, null,
['demand' => $arguments])` instead of rendering. Extbase's `redirect()`
answers `303` by default on v13 and v14 (`ActionController::redirect()`,
`$statusCode = 303`) and builds the target through the `UriBuilder`, which
adds the cache hash for plugin arguments.

Redirecting after the factory has run means the URL contains only what the
factory accepted: foreign category uids are already gone, and an invalid
active state has fallen back to its default.

Rejected: `method="get"` on the forms. The URL would carry `__referrer` and
`__trustedProperties`, and the plugin arguments would arrive without a cache
hash, which is a 404 or an uncached page depending on the installation's
`cacheHash` settings. It would also break every project override of the
filter partials that relies on the POST shape.

### One flat filter argument, produced in category_types

`CategoryFilterNormalizer::toUidList()` already discards the category type a
value was submitted under, and the factories resolve the types again through
the repository. The GET shape therefore carries the whole filter as one comma
list, `demand[filterCollection][categories]=12,31`, in ascending uid order so
one selection has exactly one URL. A new public method on the normaliser,
`toFilterArgument(?FilterCollection $filterCollection): string`, returns that
list from the resolved filter categories; the round trip with `toUidList()`
is unit tested in one class.

Each extension's controller assembles the rest (`sortingField`,
`sortingDirection`, `activeState`) from the demand object in a private
method; those keys differ per extension and do not belong in
`category_types`.

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
`sortingDirection`, even when they equal the default, and carries the filter
argument only when a category is selected. Only the bare list URL (first
visit, reset link) applies the editor's preset, exactly as a GET without
arguments does today.

### Decided: the list actions stay non-cacheable

The partner `list` and `map`, the project `ProjectList` and
`ProjectListSingle` and the program `ProgramList` actions stay registered as
non-cacheable, and the redirect stays tied to that. The changelog entry and
`docs/` name it as the reason the GET URLs do not multiply page cache
entries.

Rejected for this change: making the actions cacheable and accepting one page
cache entry per filter combination. That is a performance decision of its
own, with its own measurements, and belongs to a later change.

## Risks / Trade-offs

- [A project posts the form with `fetch()` and parses the HTML body] →
  `fetch()` follows 303 by default; named in the changelog entry.
- [A project overrides `listAction()` in a subclass] → the subclass keeps
  working but does not redirect; the ace-demo overlay removes its own PRG code
  in the same upgrade.
- [A future cacheable registration of these actions] → every filter
  combination would become a page cache entry; the changelog and `docs/` name
  this as the reason the actions stay non-cacheable.

## Migration Plan

Nothing to migrate. Rollback restores POST rendering; existing GET URLs then
still render the filtered list because the factory reads GET and POST alike.

## Open Questions

None.
