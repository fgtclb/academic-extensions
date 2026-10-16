# Content Security Policy

TYPO3 sends a Content Security Policy for the frontend once a site enforces it,
with the feature `security.frontend.enforceContentSecurityPolicy` or the
disposition of its `csp.yaml`, on v12 and v13 alike. Its `script-src` allows
`'self'` and a nonce, so a script element of the extensions passes as long as
it is a file of the site or is registered through the asset collector. An
inline event handler attribute does not pass: a nonce does not cover it, and
the default policy has no `'unsafe-inline'` for scripts.

## The handlers that exist

The sorting and filter forms of the partner list and map, of both project lists
and of the program list submit the form from an inline handler on each select,
`additionalAttributes="{'onchange': 'this.form.submit()'}"`, ten of them in
`Partner/DemandSorting.html`, `Partner/DemandCategories.html`,
`Project/DemandSorting.html`, `Project/DemandCategories.html`,
`Project/DemandActiveState.html`, `Program/DemandSorting.html` and
`Program/DemandCategories.html`. Under an enforced policy changing a select did
nothing, and the forms had no button to fall back on (ACE-924). On `main` the
program list replaced its handlers in 3.0 by a module that updates the list in
place. It needs the redirect of a filter submission and `f:asset.module`, and
this branch has neither, v12 ships no `ModuleViewHelper`. The program list
therefore keeps its handlers here and is allowed like the other two.

The profile editor of `academic_persons_edit` has eight inline handlers of its
own, six of them built from a variable, which no hash can allow. It loads
CKEditor 4 from `cdn.ckeditor.com` as well, which the default policy blocks
too, so allowing its handlers would not make the editor work under an enforced
policy. It is left out on purpose, the editor of 3.0 has none of them.

## Allowed by hash, in `script-src-attr`

`academic_partners`, `academic_projects` and `academic_programs` each ship a
`Configuration/ContentSecurityPolicies.php`. TYPO3 reads that file of every
active package on its own, when it builds the container, and applies its
mutations of the generic frontend scope to every site that inherits the
default policy, which is every site whose `csp.yaml` does not set
`inheritDefault: false`. All three files hold one mutation of `academic_base`:

```php
new InlineEventHandlerMutation('this.form.submit()'),
```

`FGTCLB\AcademicBase\Security\InlineEventHandlerMutation` is a `Mutation` that
extends `script-src-attr` by `'unsafe-hashes'` and the sha256 hash of every
text it is given. It is internal to the academic extensions. The header TYPO3
12.4.45 then sends for the partner list starts like this, the nonce shortened:

```text
default-src 'self'; script-src 'self' 'nonce-…' 'report-sample'; …;
script-src-attr 'self' 'nonce-…' 'unsafe-hashes' 'sha256-osjxnKEPL/pQJbFk1dKsF7PYFmTyMWGmVSiL9inhxJY=' 'report-sample'; …
```

TYPO3 13.4.36 sends the same without the nonce, in both directives, as long as
the page consumes none. TYPO3 v12 never drops a nonce: a page that uses one,
and every page with uncached content such as the plugins of these forms, sends
it in `script-src`.

- **`script-src-attr`, not `script-src`.** The browser reads `script-src-attr`
  for event handler attributes only, and `script-src` keeps deciding about
  script elements as before. A hash in `script-src` would also make the browser
  ignore an `'unsafe-inline'` a site gave its script elements.
- **`Extend`, not `Set` or `Append`.** `Extend` first copies the sources of the
  nearest ancestor that exists, `script-src` or `default-src`, when the
  directive does not exist yet, and then appends. That is why `'self'` and the
  nonce appear, which is harmless in an attribute directive. `Set` would
  replace a hash another package added before. `Extend` is the mode the core
  uses to add sources.
- **`'unsafe-hashes'` is required.** Without it a browser applies a hash to
  script elements only, never to an attribute.
- **The hash is of the exact text.** The browser hashes the attribute value as
  it reads it, after decoding character references. All ten handlers use the
  one text `this.form.submit()`, so one hash covers them. A handler with
  another text, even one that differs only in whitespace, needs a hash of its
  own.
- **A class of its own.** The mutation is recognised by its class in the
  listener below. TYPO3 hands the mutations to that listener without their
  origin, and a site may declare a mutation with exactly the same sources in
  its `csp.yaml`, which must count as the site's.

The API is the same on v12 and v13: `Map::fromEntries()`, `Scope::frontend()`,
`MutationMode::Extend`, `Directive::ScriptSrcAttr`,
`SourceKeyword::unsafeHashes`, `HashValue::hash()` and `PolicyMutatedEvent`
all exist on 12.4, and so does the `inheritDefault` of a site.

A `csp.yaml` belongs to a site, and site sets, which do nothing on v12 anyway,
cannot carry Content Security Policy mutations, so the extension file is the
one place an extension can make its handlers work on every site at once.
Replacing the handlers by a script was the alternative. It was not taken: it
needs a script, JavaScript tests and a changelog of its own per extension for a
form that only has to submit, and an override of a partial that keeps the
handler would still need the hash.

## A policy that allows inline scripts already

Once a policy has `script-src-attr`, the browser no longer falls back to
`script-src` for attributes, and next to a hash it ignores `'unsafe-inline'`.
A site that allows inline handlers of its own with `'unsafe-inline'` in
`script-src` would lose them on every page through the mutation of an
extension. Extending the new directive by `'unsafe-inline'` in its `csp.yaml`
would not help either, for the same reason.

`DropInlineEventHandlerHashesWhereInlineIsAllowed` of `academic_base` listens
to `PolicyMutatedEvent` and removes `script-src-attr` from a frontend policy
when both hold:

- The directive exists only because of an `InlineEventHandlerMutation`. The
  listener builds the policy again from every other mutation of the event, to
  see whether the site or another package declares one as well.
- The directive the browser falls back to, `script-src` or else `default-src`,
  carries `'unsafe-inline'`, or neither exists.

A `script-src-attr` that the site or another package declares as well keeps
its sources. Where it carries `'unsafe-inline'` itself, the listener takes the
hashes of the `InlineEventHandlerMutation`s out of it with `reduce()`, by the
instances the mutations hold, which the policy keeps, and leaves
`'unsafe-hashes'`, which alone does not disable `'unsafe-inline'`.

The handlers of the forms then follow the configuration of the site, as the
site's own handlers do. TYPO3 v12 never drops a nonce: a page that uses one,
and every page with uncached content such as the plugins of these forms, sends
it in `script-src`. There a browser ignores `'unsafe-inline'` and blocks every
inline handler, those of the site and those of the form alike, unless the site
takes the nonce out of its policy. A fully cached v12 page that uses no nonce
sends none, and `'unsafe-inline'` applies. TYPO3 13.4 decides after the event
and drops the nonce on a fully cacheable page where it is unused or
`'unsafe-inline'` makes it unnecessary, and `'unsafe-inline'` then applies. Keeping the hash instead
would have made the forms work where the site's own handlers do not, and a
`script-src-attr 'unsafe-inline'` would have allowed every handler on pages
where the site's policy blocks them.

The listener is registered with the `event.listener` tag in the
`Configuration/Services.yaml` of `academic_base`, because
`#[AsEventListener]` of TYPO3 does not exist on v12. It builds the other policy
from a new `Policy`, not from the `defaultPolicy` of the event. The clones of a
policy share the map of their directives, so after `PolicyProvider` has applied
the mutations, the default policy carries them as well. It uses only methods
TYPO3 12.4 has: `Policy::get()` does not exist there, so it asks `has()` and
`containsDirective()`, and a `HashValue` implements no equality, so it compares
hashes by instance only.

Recognising the mutations by their class relies on TYPO3 handing over the
objects the `ContentSecurityPolicies.php` files return. `PolicyProvider` caches
no policy yet, its `@todo add policy cache per scope` says so. A cache that
rebuilt the mutations from their values would lose the class, and the listener
would then leave every policy as it is.

## Where the policy does not reach

- A site whose `csp.yaml` sets `inheritDefault: false` gets none of the
  mutations of the extension, and neither does a site that leaves the package
  out with `packages`, which TYPO3 reads inside an `enforce:` or `report:`
  section of the `csp.yaml` only. Such a site adds the two sources to its own
  `csp.yaml`, the manuals of the three extensions show how.
- A copy of a partial that changes the handler text needs the hash of its own
  text in the `csp.yaml` of the site.
- A policy that the web server or a proxy sends is enforced next to the one of
  TYPO3, or in its place. TYPO3 does not know it, so it needs the same sources
  itself.
- A page template that draws a map with the map script of `academic_partners`
  itself, outside the content element, gets no source for the tiles of its
  map, see below.

## The tiles of the partner map

The partner map loads its tiles as images from OpenStreetMap,
`https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png`, which `img-src` of the
default policy does not allow. On this branch the URL is a fixed value of the
map script, `Resources/Private/TypeScript/frontend/map.ts`, there is no setting
for it. A `ContentSecurityPolicies.php` would still allow the host on every
page of every site, so the map registers the source itself instead, for the
page it is rendered on (ACE-933). `main` does the same for the tile URL its
sites configure.

`MapTileSourceRegistration` of `academic_partners` derives the source from the
tile URL: scheme, host and port, with every host label up to the last one that
holds a placeholder replaced by `*`, so `https://{s}.tile.openstreetmap.org/…`
becomes `https://*.tile.openstreetmap.org`. The derivation is the one of
`main`, which handles configured URLs as well, and its constant
`DEFAULT_TILE_URL` has to stay the URL of the map script, which a unit test
checks. It appends one `MutationCollection` with an `Extend` of `img-src` to the
`PolicyRegistry` of TYPO3, the request scoped registry `PolicyProvider` applies
after the mutations of the packages and the site, whatever `inheritDefault`
says, on v12 and v13 alike.

### Where it registers

The map action is not cacheable, and when the policy is built differs between
the two core versions:

- **TYPO3 v12** builds the policy in `ContentSecurityPolicyHeaders` after the
  whole request handler, the non-cacheable parts included. A mutation the map
  action registers reaches every response, generated or cached.
- **TYPO3 v13** prepares the policy right after the cacheable content is
  rendered and before the non-cacheable parts run, `RequestHandler::handle()`
  with its comment on nonces in non-cacheable parts says so, and
  `ContentSecurityPolicyHeaders` then keeps the prepared policy. A mutation the
  action registers reaches the header of a page served from the cache only.

The registration therefore also happens in the cacheable part of the content
element. `ext_localconf.php` adds the data processor
`academic-partners-map-tile-source` to `tt_content.academicpartners_map`, with
`addTypoScript(…, 'defaultContentRendering')` right after `configurePlugin()`,
so it extends the rendering definition of the plugin wherever that exists. The
definition copies `lib.contentElement` by reference, and the processor runs
next to the processors of that object. It is the same on both core versions,
on v12 it is not needed and changes nothing. The processor carries its
`data.processor` tag as a Symfony `#[AutoconfigureTag]` attribute and is
published with `#[Autoconfigure(public: true)]`, both of which exist on
Symfony 6.4 and 7.4, and it is `final`, so no subclass inherits the tag, see
[Dependency injection](dependency-injection.md).

TYPO3 writes the mutation collections of the registry into the page cache with
the page, `MetaDataState` of EXT:frontend, on v12 and v13, and restores them
before the non-cacheable parts of a cached page run. So the source the
processor registered applies to every later request of the cached page as
well. The map action registers the tile URL once more: on a cached page that
changes nothing, because `SourceCollection::with()` drops an equal source, and
it covers the cached pages of a site that renders the element without the
processors of `lib.contentElement`.

### What the map does not need

The stylesheets, the scripts and the marker images are files of the extension,
and Leaflet's only `data:` image is allowed by the default policy. Leaflet sets
the position of tiles and markers through the style properties of the elements,
which a policy does not restrict, and the default policy has
`style-src-attr 'unsafe-inline'` as well. Attribution links are navigation, not
loads.

## The button without JavaScript

`Partner/SortingAndFilters.html`, `Project/SortingAndFilters.html` and
`Program/SortingAndFilters.html` render a submit button inside `<noscript>`,
in a row of its own after the row of the selects, labelled `filter.submit` of
the extension. A visitor with JavaScript sees the form as before. A button that
a script hides was the alternative, and it needs the script the change avoids.
The button sends no field of its own, so its request equals the one of the
handler and the list reads the filter from the body as before.

The button does not help where scripting is enabled and a policy blocks the
handler anyway, a policy of the web server for example. `<noscript>` renders
only where the browser runs no script at all.

## Tests

- `InlineEventHandlerTest` of `packages-dev/monorepo-shared` fails for every
  inline event handler of a template whose text no `InlineEventHandlerMutation`
  in the `ContentSecurityPolicies.php` of the same extension allows, and for
  every allowed hash no handler needs. `academic_persons_edit` is the one
  documented exception, see
  [Unit tests](../testing/unit-tests.md#inline-event-handlers).
- `DropInlineEventHandlerHashesWhereInlineIsAllowedTest` of `academic_base`
  runs the listener over policies built the way `PolicyProvider` builds them:
  the default frontend policy, `'unsafe-inline'` in `script-src` before and
  after the extension, in `default-src` with and without a `script-src`, no
  ancestor at all, a `script-src-attr` of the site, also one with the same
  hash, and the backend scope.
- `AcademicPartnersContentSecurityPolicyTest`,
  `AcademicProjectsContentSecurityPolicyTest` and
  `AcademicProgramsContentSecurityPolicyTest` enforce the frontend policy and
  assert, for every plugin with the form, that every handler of the page is
  allowed by `script-src-attr` with `'unsafe-hashes'`, that `script-src`
  carries neither the hash nor `'unsafe-hashes'` nor `'unsafe-inline'`, that a
  site with `'unsafe-inline'` in `script-src` gets no `script-src-attr`, that a
  `script-src-attr` of the site keeps its own hash and the one of the
  extension, that one with `'unsafe-inline'` loses the hash, that the form
  renders the one button inside `<noscript>`, and that a submission of the
  form shows the filtered list, on v12 and v13. They write the `csp.yaml` of
  the site and reset the scopes of the policy, which are singletons holding the
  site object of an earlier request of the process.
- `MapTileSourceRegistrationTest` of `academic_partners` covers the source of
  a tile URL, the URLs without one, the mutation it registers, and that the
  default tile URL is the one of the map script.
- `AcademicPartnersMapTileSourceTest` enforces the frontend policy and asserts
  `img-src` of the map page on the request that generates it and on the one
  served from the page cache, with the default tile URL and on a site with
  `inheritDefault: false`, that the page cache carries the source, and that the
  page of the list gets no tile host. With the data processor removed by
  TypoScript, the map action alone allows the tiles on both pages on v12 and on
  the cached page only on v13, a test per core version with the groups
  `not-core-13` and `not-core-12`. The `PolicyRegistry` is a singleton as well:
  the test empties it before each request, as a new process starts with an
  empty one.

## See also

- [List filter types](list-filter-types.md): the selects of the forms the
  handlers sit on.
- [Label overrides](label-overrides.md): how a site changes the label of the
  button.
- [Frontend assets](../development/frontend-assets.md): how the extensions load
  their scripts, as same-origin files that `'self'` allows.
- [Dependency injection](dependency-injection.md): registering event listeners
  with the `event.listener` tag on this branch.
