# List filter URLs

The filter and sorting forms of the partner, project and program lists submit
by POST. Each list plugin answers that submission with a `303 See Other` to
itself, carrying the selection as GET arguments — so a filtered list has a URL
of its own that can be bookmarked, shared and reloaded, and that a link can
carry. The pagination of the partner list, the active filter tags and the
route enhancers build on it.

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

The program finder of `academic_programs` has no redirect of its own. Its form
posts into the namespace of `ProgramList` on the target page, in the per-type
shape of the list's own form, `demand[filterCollection][<type>]`, and the
list's redirect answers it. That shape is therefore a contract the finder
depends on; see [List filter types](list-filter-types.md#the-program-finder).

## The program list requests its URLs without a reload

The program list of `academic_programs` takes a filter URL without reloading
the page. Its module `frontend/program-list.js` posts the form with `fetch()`
exactly as the browser would, `fetch()` follows the `303`, and the response is
the page of the filter URL with `response.url` set to that URL. The module
replaces every program list of the page with the one of the same content
element uid from that page, and pushes the URL into the history. Every list,
because all of them share the plugin namespace and the filter URL filters each
of them: the page shows what a reload of the URL shows. Back and forward request the URL of the history
entry with a GET and replace the list again.

- **Nothing about the URL moves into the browser.** The redirect still
  normalises the selection and computes the cache hash, so the address bar
  shows exactly the URL a reload would have shown, and the route enhancers
  apply unchanged.
- **The response is a whole page.** There is no endpoint or page type of its
  own, so routing, access and caching are the page's own. It costs the server
  what the reload cost and saves the browser the assets of a new page.
- **A failure ends in the reload it replaced.** A failed request, an error
  status or a page without the list submits the form the normal way.

The partner and project lists still submit on a change through inline
handlers. Their templates and the module would need the same parts. See
[Frontend assets](../development/frontend-assets.md#a-module-that-updates-part-of-the-page-asks-for-the-whole-page)
for the markup contract.

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
| `currentPage`                 | only on a pagination link        | the page, 1 and up; partner list only      |

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
  applies the content element's preset categories only when no demand argument
  arrives at all, and its sorting then and when a demand carries no sorting of
  its own (in `academic_programs`: a list with a hidden sorting select, the
  program finder). A visitor who clears a preset category gets a URL without a
  filter — but with the sorting, so it is not the bare page, and the preset
  stays cleared. The bare page URL is the one place the preset categories
  apply.

The keys arriving in alphabetical order is not a choice of this code: the page
router sorts the query arguments recursively by key (`PageArguments`).

## Pagination links

The partner list paginates on request of the content element, and its page
links are list URLs of this shape with `currentPage` added. The controller
assigns `demandArguments` - `createDemandArguments()` of the demand the
request asked for - next to the paginator, and `Partner/Pagination.html` adds
the page to it. So a link keeps the filter and the sorting, and on the bare
page it carries the editor's preselection explicitly, which the factory would
otherwise drop on the first request with a demand argument.

- **Read before the demand event**, the page as well as the link arguments,
  like the redirect's URL. A listener acts again on the request a page link
  leads to, so its changes need not travel in the URL, and a listener that
  hands back a demand of its own keeps the page instead of pinning the list to
  page one. A listener cannot choose the page.
- **The redirect never carries a page.** `createDemandArguments()` leaves
  `currentPage` out, so a filter submission starts on page one of the new
  selection, which may not have the page the visitor was on.
- **The factory reads the page and clamps it at 1**; the paginator clamps it
  at the last page. Neither is an error.
- **It is below `demand`**, so the prefix exclusion below covers it: every
  page of a list shares the one page cache entry, as every filter does.
- **The partner map is not paginated.** Its content element has a FlexForm
  data structure of its own without the pagination sheet, and its action
  ignores pagination values an element stored all the same.

The pattern is the one of the profile list of `academic_persons` -
`QueryResultPaginator`, `NumberedPagination` when `numbered_pagination` is
loaded and `SimplePagination` otherwise - whose page argument sits in the
demand too, and whose links are built as the next section describes.

## Active filter tags and the reset link

The partner, project and program lists can show the active filters as tags,
a reset link and the number of results, each switched on per site
(`settings.filter.showActiveFilters`, `showReset`, `showResultCount`, all off
by default). The partials are `<Extension>/ActiveFilters.html` and
`<Extension>/ResultCount.html`, rendered by `SortingAndFilters.html`.

- **A tag links to the list without its selection**, in the shape above. The
  filter argument comes from `ct:filterArgument` of `category_types`, which
  returns `CategoryFilterNormalizer::toFilterArgument()` of the demand's filter
  collection with one category left out, so a tag and a submitted form lead to
  the same URL. The template adds the sorting and, for projects, the active
  state. An active state other than `all` is a tag of its own and links to
  `all`.
- **The tags read the demand after the demand event**, unlike the pagination
  links: they show what the selects of the form show, which is the demand the
  list was found with. A category a listener adds to every request is a tag the
  visitor cannot remove, because the listener adds it again on the page the tag
  leads to, and the other tag links carry it.
- **Every selected category is a tag**, also one of a type the form does not
  offer, a preset one for example. The tags read `allCategoriesByType`, which
  holds the categories of the types the collection was created for: all of them
  for the collections the factories build, none for a collection a listener
  creates without type identifiers.
- **A tag without a category left still carries the sorting.** Removing the
  last category must not lead to the bare page, which would apply the editor's
  preselection again, the reason the sorting is always carried at all.
- **An empty filter is left out of a link**, not sent as an empty value: a
  route enhancer cannot generate one (`createDemandArguments()` omits it for
  the same reason). Fluid cannot drop a key from an array literal, so the
  partials choose between two literals.
- **The reset link is the bare page**, `f:link.page` without arguments, where
  the content element's preselection applies. It is offered when the request
  carried a demand argument and a category is selected or preselected (for
  projects also a state other than `all`): each list action assigns
  `visitorSelection` (`$demand !== null`, the factories' own test for applying
  the preselection), and the partial checks the demand and the element's
  `settings.categories` and `settings.activeState`. On the bare page the link
  would lead to the page shown, after the visitor removed a preselected
  category it is the one way back to it, and with nothing selected and nothing
  preselected it would reset no more than the sorting or the page.
- **The tag links name their action.** The partner map renders the partial of
  the list, whose form says `action="list"`, and Extbase gives a template no
  access to the action it runs in. `Templates/Partner/Map.html` sets the
  variable `filterAction` to `map` before it renders the partial. A template
  without it links to `list`, which the map plugin answers with its default
  action, under a second URL.
- **The tags loop over `allCategoriesByType`**, arrays per category type,
  never over the category collection itself. `CategoryCollection` is an
  `\Iterator` with the one internal array pointer as its position, and
  `toFilterArgument()` walks the same collection inside the loop: a loop over
  the collection ended after its first tag. It also puts the tags in the type
  order of the group, the order of the filters.
- **The count counts the result the list event handed back**, with
  `f:count`. On a `QueryResultInterface` that is the whole result, not the
  page a paginated partner list shows.

## The links of the profile list

The profile list of `academic_persons` chooses its page, its letter and its
view mode through links, the pagination, the letter navigation and the view
mode switch. Its two visitor filters have a form, which posts to an action of
its own, see the last item. Its links carry only what the visitor chose, built
from the demand as above, but two of the rules above do not hold for it: the
sorting is not carried, because the content element sets it, and the demand
stays in the cache hash, because the list action is cacheable. The mechanism is
its own.

- **One list of visitor values.** `ProfileController` names the demand
  properties a visitor sets, today `currentPage`, `alphabetFilter`,
  `viewMode`, `functionTypeFilter` and `organisationalUnitFilter`. The
  property mapping accepts exactly those, plus the keys of `settings.demand`,
  whose values the controller writes over the request's — so the sorting cannot
  be set from a URL however it is spelled. A change that lets a visitor set a
  further value adds it to that list and to nothing else.
- **`activeListArguments`, built from the demand.** The list action assigns the
  properties of that list whose value differs from the default of a fresh
  demand, read after the content element settings and **before** any event, for
  the reason given above. Unknown and foreign request values never reach it.
- **Merged by a view helper, not listed in a literal.**
  `Partner/Pagination.html` names every demand key in a Fluid array literal,
  because Fluid cannot merge arrays; a project had to copy both navigation
  partials of the profile list to forward one more value that way.
  `persons:listArguments` returns `activeListArguments` with the link's own
  change applied: `overrides` for the page or the letter, `remove` for the page
  on a letter link, so a new letter starts on page one.
- **`addQueryString` is rejected.** It carries every query parameter of the
  current request into the link, foreign ones included. A test with a foreign
  parameter, an unknown plugin argument and a sorting in the request guards
  that none of them reaches a link.
- **The demand is part of the cache hash here.** The profile list action is
  cacheable and there is no exclusion for its namespace, so every link variant
  is a page cache entry of its own, covered by its `cHash` - or, behind the
  shipped route enhancers, by its speaking path. A view mode is one entry per
  mode, like a letter.
- **The view mode is resolved before it is carried.** Unlike page and letter
  it names a partial, so the list action checks it against the switch of the
  content element and the allowed modes, and writes the result back into the
  demand: empty for the default mode and for a rejected one. A link therefore
  carries a mode only while it differs from the default, the list in its
  default mode keeps its URLs, and the switch link to the default mode removes
  the mode - or is the plain page, when nothing else is left to carry.
- **No pagination under a letter.** `listAction()` still switches pagination
  off while a letter is selected. Lifting that, and the route set that goes
  with it, is a change of its own.
- **The filter form posts to `filterAction()`, not to the list.** The list
  action is cacheable, and a POST to it would render the page. The `filter`
  action is non-cacheable, maps the demand as the list does, resets a value
  that is not an option, and throws a `303` to the list action with
  `activeListArguments` minus the page: a new filter starts on page one and
  keeps the view mode and the letter. With nothing left to carry it redirects
  to the page itself, which is the list as it starts. The target keeps its
  demand in the cache hash like every other link of this list, so the redirect
  signs what it carries. It may do so only because every value is limited
  first: the filters to their options, the view mode to the allowed modes, the
  letter to the letters of the navigation. A hand-made submission therefore
  gets no signed URL the list would not link itself, which is the risk the
  section below describes for the other lists. See
  [TypoScript and site sets](typoscript-and-site-sets.md#a-filter-form-reaches-a-route-through-a-redirect)
  for its routes.

## The links of the job list

The job list of `academic_jobs` paginates the same way as the partner list: a
FlexForm sheet "Pagination", the fallbacks of results per page and number of
links, and `QueryResultPaginator` with `NumberedPagination` or
`SimplePagination`. Its links are simpler, because the list has no filter and
no demand. The job type and whether hidden jobs show come from the content
element, so a page link carries the page and nothing else, as the plugin
argument `tx_academicjobs_list[currentPage]`.

- **The action reads the page itself**, not as an argument of its signature.
  A value that is no integer would fail the argument validation, and a POST
  reaches every list without a cache hash, so any job list could be turned
  into an error page. The action takes such a value, and one below 1, as the
  first page, as the demand factory of the partner list does. The paginator
  clamps a page beyond the last one.
- **The page is part of the cache hash.** There is no exclusion for the job
  list namespace, so every page of a list is a page cache entry of its own
  around the placeholder of the non-cacheable action. A visitor cannot add
  entries: only the list builds a valid `cHash`, and it builds one per page it
  has. The partner list excludes its demand for a reason the job list does not
  have, a redirect that would hand out a hash for any selection.
- **No demand, no event.** The page is not part of anything a listener sees
  before the query. A listener of the plugin view event finds `paginator` and
  `pagination` in the view, next to `jobs`. The paginator is built before that
  event, so a listener that replaces `jobs` changes nothing a paginated list
  renders. The profile list behaves the same, see
  [Plugin view event](plugin-view-event.md).

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
`cHash`, over `action` and `controller`. Behind the route enhancers the three
lists ship, which map those and put the demand into the path, it carries none,
and each sorting path is a page cache entry of its own: those segments are
static route arguments, like the `action` and `controller` the route maps,
and the page cache identifier contains them. See
[List route enhancers](list-route-enhancers.md#static-values-and-the-page-cache).

The `CategoryFilterMapper` aspect of `category_types` puts the filter into a
readable path without changing any of this: it is not static mappable, so the
filter stays a dynamic argument, excluded from the hash, and a filter path adds
no page cache entry either. See
[Category filter routing](category-filter-routing.md#not-static-mappable).

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
is back. The enhancers the partner, project and program lists ship in
`Configuration/Routes/List.yaml` declare none for that reason, so
`/title/asc` is always part of the path, and each extension's
`Unit/Configuration/ListRoutesTest` and `Routing/*ListRouteEnhancerTest` assert
it. The same holds for any enhancer a site writes itself.

The price is one route per combination of the arguments, see
[List route enhancers](list-route-enhancers.md#one-route-per-combination). A
link to the list that carries no argument at all, such as the form's own
action URL, enters no route and keeps its plugin arguments in the query string,
and a path with the sorting field alone does not resolve.

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
