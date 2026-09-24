# List filter URLs

The filter and sorting forms of the partner, project and program lists submit
by POST. Each list plugin answers that submission with a `303 See Other` to
itself, carrying the selection as GET arguments — so a filtered list has a URL
of its own that can be bookmarked, shared and reloaded, and that a link can
carry. The planned pagination, active filter and route enhancer changes build
on it.

This page is about the **shape** of that URL and the reasons behind it. What an
integrator sees is documented in each extension's changelog.

## Which plugins

| Extension           | Plugins                            | Action                    |
|---------------------|------------------------------------|---------------------------|
| `academic_partners` | `List`, `Map`                      | `listAction`, `mapAction` |
| `academic_projects` | `ProjectList`, `ProjectListSingle` | `listAction` (shared)     |
| `academic_programs` | `ProgramList`                      | `listAction`              |

The redirect goes to the action that received the submission, in the namespace
of the plugin that rendered the form. The partner map renders the partial whose
form names `action="list"`; its URL therefore points at the `List` plugin, but
its fields carry the map's own namespace, and so does the redirect.

## The demand in the URL

```text
?tx_academicpartners_list[action]=list
&tx_academicpartners_list[controller]=Partner
&tx_academicpartners_list[demand][filterCollection][categories]=3,6
&tx_academicpartners_list[demand][sortingDirection]=asc
&tx_academicpartners_list[demand][sortingField]=title
&cHash=…
```

`action` and `controller` are what `UriBuilder::uriFor()` adds to every plugin
link; the demand keys are the ones below.

| Key                           | Carried                          | Values                                     |
|-------------------------------|----------------------------------|--------------------------------------------|
| `sortingField`                | always                           | the sorting fields of the extension        |
| `sortingDirection`            | always                           | `asc`, `desc`                              |
| `activeState`                 | always, projects only            | `all`, `active`, `completed`               |
| `filterCollection.categories` | only when a category is selected | one comma list of category uids, ascending |

- **Built from the demand object, not from the request.** Each extension's
  `DemandFactory::createDemandArguments()` is the reverse of
  `createDemandObject()`, so the URL carries only what the factory accepted: a
  category of another group, a uid no category has and an active state the
  list does not offer are gone, and so are `__referrer` and
  `__trustedProperties`.
- **One filter argument for all category types.** The factories resolve the
  type of every category through the repository anyway, so the type a value
  was submitted under is not kept.
  `CategoryFilterNormalizer::toFilterArgument()` in `category_types` writes the
  list, `toUidList()` reads it back — and reads the form's own per-type shape
  as well, which is why the factory needs no second code path. Ascending order
  gives one selection one URL, and a later route enhancer one variable instead
  of one per category type.
- **The sorting is always carried**, even when it equals the default. A factory
  applies the content element's preset categories and sorting only when no
  demand argument arrives at all. A visitor who clears a preset category gets a
  URL without a filter — but with the sorting, so it is not the bare page, and
  the preset stays cleared. The bare page URL is the one place the preset
  applies.

The keys arriving in alphabetical order is not a choice of this code: the page
router sorts the query arguments recursively by key (`PageArguments`).

## The demand is not part of the cache hash

A page with a non-cacheable plugin is still page-cached — the page around the
plugin, with a placeholder where the plugin renders — and the cache identifier
contains every argument the cache hash covers. With the demand in the hash, the
redirect would hand every visitor a valid hash for any combination of
categories, sortings and active states they care to submit, and each would be a
page cache entry of its own.

So each list extension appends its demand to
`$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters']` in its
`ext_localconf.php`, `^tx_academicpartners_list[demand]` and so on: the `^`
makes it a prefix, so every key below `demand` is excluded. The actions are not
cacheable, so nothing the cached page holds depends on the demand, and every
filter URL of a list shares one page cache entry. The URL still carries a
`cHash` — over `action` and `controller`. Behind the program route enhancer
`academic_programs` ships, which maps those and puts the sorting into the path,
it carries none, and each of the six sorting paths is a page cache entry of its
own: those segments are static route arguments, and the page cache identifier
contains them.

The setting is installation-wide; it names the plugin namespaces and nothing
else. A plugin namespace changed with `view.pluginNamespace` in TypoScript is
not covered by it. And nothing else in the cached page may read the demand: a
link with `addQueryString = untrusted` would carry the demand of whoever filled
the cache entry. Removing the setting again — a rollback, a cacheable list
action — turns every filter URL shared until then that carries a `cHash` into a
404, because the hash was computed without the demand. The other way round, a
URL whose `cHash` was computed *with* the demand and that carries `action` or
`controller` as well — from a project's own redirect before this one existed —
answers with a 404 after the update. With EXT:seo, the canonical URL of a
filtered list is the list URL without the demand, as core's
`CanonicalizationUtility` leaves out every argument excluded from the cache hash
— consistent with the one shared page, and the language menu drops the filter
the same way.

The redirect carries what the demand factory accepted and nothing else. A field
a project adds to its filter partial and reads in a listener does not survive
it; adding arguments to the redirect is a follow-up.

## Thrown, not returned

`redirectFilterSubmission()` throws `PropagateResponseException` around
`ActionController::redirect()` instead of returning the redirect. A returned
response of a content element plugin does not become the PSR-7 response of the
page on TYPO3 v13: `Extbase\Core\Bootstrap::handleFrontendRequest()` sends its
status and headers with `header()`, and the whole page renders around the empty
plugin. The browser still gets the `303`, but no middleware sees it and a
functional test gets a `200`. v14 copies the status into the page's response
data. The exception reaches the `ResponsePropagation` middleware on both
versions, and the content object exception handler rethrows it rather than
rendering an error.

It is thrown **before the demand event**: the URL carries the visitor's
selection, and a listener acts on the GET request that follows.

The demand of the redirect is read from the **body** of the POST alone. Extbase
merges the arguments of the URL into those of the body, and the URL of a
filtered list carries a demand of its own — a form that posts to the URL it is
on would otherwise bring back a category the visitor just cleared. A POST whose
body carries no demand of this plugin is another plugin's form, and the list
renders as usual, with the demand of the URL.

## Route enhancers must not declare defaults for the sorting

Symfony routing leaves a variable that equals its default out of a generated
path. An enhancer with `defaults` for the sorting therefore turns the redirect
for the default sorting and no filter into the bare page URL — and the preset
is back. The enhancer `academic_programs` ships in
`Configuration/Yaml/Routes.yaml` declares none for that reason, so
`/title/asc` is always part of the path, and
`Routing/ProgramListRouteEnhancerTest` asserts it. The same holds for any
enhancer a site writes itself.

The price: a link to the list that carries no sorting, such as the form's own
action URL, does not enter the route and keeps its plugin arguments in the query
string, and a path with the sorting field alone does not resolve.

## Why the actions stay non-cacheable

Every list action is registered non-cacheable in its `ext_localconf.php`, and
the cache hash exclusion above depends on it: a cacheable action would render
its demand into the cached page, and one cached page would then answer every
filter URL. Making the actions cacheable means taking the demand back into the
cache hash and accepting one page cache entry per filter combination — a
performance decision with its own measurements, not a side effect of the
redirect.

## See also

- [List plugin events](list-plugin-events.md) — the demand event the redirect
  runs before.
- [Testing helper](../testing/testing-helper.md) — `submitFrontendForm()`,
  which submits a rendered form with the fields it holds.
- [Database queries](database-queries.md) — how the demand becomes a query.
