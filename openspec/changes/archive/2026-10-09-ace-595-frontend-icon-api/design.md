## Context

See `proposal.md` for the motivation. The frontend icon registry of
`academic_base` (`ace-810-frontend-icon-registry`) is in place on `main`:
`FrontendIconRegistry` merges every `Configuration/FrontendIcons.php` and the
listeners of `CollectFrontendIconsEvent`, `FrontendIconFactory` renders an icon
of it with the wrapper of TYPO3's `Icon`, and `<ab:icon>` renders it in a
template. The registry lists its identifiers since the shared icon set
(`getAllRegisteredIconIdentifiers()`, ACE-584).

What the backend offers, read on both installed cores (13.4.35 and 14.3.7):
`@typo3/backend/icons.js` fetches from the backend AJAX route `/ajax/icons`,
which answers a logged-in backend user only, and that route renders through
core's `IconFactory`, so from the backend registry. Nothing of it can be used
on a frontend page.

The frontend middleware stack was ordered with core's
`DependencyOrderingService` over every `RequestMiddlewares.php` of the
installed vendor tree, once per core: `typo3/cms-frontend/site` provides the
site, the site language and the route tail, `maintenance-mode` follows it, then
`backend-user-authentication`, `authentication`, the redirect middleware of
EXT:redirects, `base-redirect-resolver`, `static-route-resolver` and
`page-resolver`.

## Goals / Non-Goals

**Goals:**

- One decision of what may leave the server, shared by every path, so the JSON
  map and the endpoint can never serve different icons or different markup.
- No session, no cookie, no database for the endpoint, so a proxy can cache it
  like a file.
- Markup identical to what a template renders inline, so a site package's
  replacement reaches the script without a second override.
- The same behaviour on v13 and v14 without a version switch.

**Non-Goals:**

- Public PHP classes. The renderer, the ViewHelper class and the middleware
  are `@internal`, the contract is what a template, a request and a module
  see.
- Migrating the `<template>` clones of `academic_persons_edit`.
- Overlays, states, alternative markups and the `localStorage` cache of the
  backend module.

## Decisions

### One renderer, the frontend registry as the allow-list

`Imaging\FrontendIconRenderer`, `final readonly`, `#[Autoconfigure(public:
true)]`, decides with `isServable()` and renders with
`FrontendIconFactory::getIcon($identifier, $size)->render('inline')`. Served
is an identifier that matches `IDENTIFIER_PATTERN`
(`\A[a-z0-9_][a-z0-9_.-]{0,99}\z`, `\A` and `\z` because `$` also matches
before a trailing line feed), is registered in the frontend registry, is not
`default-not-found`, and whose provider is an `AbstractSvgIconProvider` other
than `SvgSpriteIconProvider`. `IconRegistry` and `IconFactory` of TYPO3 are
never injected.

Rejected:

- A prefix list (`tx-academic*`) or an event to widen what is served. The
  frontend registry already is the list of what is meant for visitors, and a
  site package registers an icon there anyway to render it with `<ab:icon>`.
- Serving a bitmap icon. Its markup is an `<img>` whose URL is built from the
  request of a page, which the endpoint does not have. A sprite icon is an
  `<svg><use>` into a sprite without a size of its own.
- Answering an unknown identifier with the placeholder, as a template does. A
  script could not tell a typo from an icon.

### The JSON map is a data block, not a script

`ViewHelpers\FrontendIconMapViewHelper`, `final`, renders
`<script type="application/json" data-academic-icons>` with
`JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT`, so no markup can end
the element, and `JSON_INVALID_UTF8_SUBSTITUTE`, so a broken file degrades
instead of failing the content element. With `endpoint="1"` it adds the
endpoint below the base of the current site language and the version token.
The request is read through `RenderingContextInterface::getAttribute()`, the
API both cores share.

Rejected: an inline script assigning a global (needs a CSP nonce), one data
attribute per icon (markup in attributes, no order), and a `<template>` per
icon, which stays the right choice where the markup around the icon is Fluid's.

### The endpoint is a middleware between the site resolver and the authenticators

`Middleware\FrontendIconEndpoint`, `final readonly`, registered in
`Configuration/RequestMiddlewares.php` after `typo3/cms-frontend/site` and
`maintenance-mode`, before `backend-user-authentication`, `authentication` and
`page-resolver`. It matches the route tail the site resolver leaves below a
real `Site`, so every site language base and a subfolder installation work. On
both cores it sorts directly after `maintenance-mode` and before
`request-token-middleware`.

Rejected:

- Naming `base-redirect-resolver` or `static-route-resolver`. EXT:redirects
  sits between the authenticators and those two, and "after
  `base-redirect-resolver`, before `authentication`" is a dependency cycle on
  both cores.
- A page type or an Extbase plugin. Both run after the frontend user
  authentication, so a request can start a session and send a cookie, and both
  need a page record.
- `typo3/cms-frontend/tsfe` as an anchor. It exists on v13 only.

### A version token decides the cache lifetime

`FrontendIconRenderer::getVersion()` hashes, per served identifier sorted by
name, the registration and the `filemtime()` of its source, per provider the
`filemtime()` of its class file and of every parent class, and
`PackageDependentCacheIdentifier::toString()`. The endpoint answers the
current token with `public, max-age=31536000, immutable`, any other with
`public, max-age=300`, both with a body hash as `ETag` and `304` for a
matching `If-None-Match`. The token is compared with `hash_equals()`.

Rejected: an `ETag` alone. Every page view would revalidate every icon. And
the composer lock hash alone, which does not change when the code of a path
package or of a checkout is edited in place, the reason the provider class
files count.

### The TypeScript factory follows the backend module, without what needs the backend

`@fgtclb/academic-base/frontend/icons.js`, published through
`Configuration/JavaScriptModules.php` for the `frontend/` prefix only. An
`IconFactory` answers `getIcon()`, `getIconElement()` and `prefetch()`, reads
every JSON map lazily on a miss, and batches the calls of one microtask into
one request per endpoint and size, sorted by identifier and split at 32. The
sort gives a set of icons one URL, so the immutable answer is one cache entry
in the browser and a proxy whatever order a module asks in. The endpoint keeps
the order it is asked in for other callers. One promise per icon and size is
kept at module level. Requests are sent with `credentials: 'omit'` and
aborted after ten seconds through `AbortController` and `setTimeout`, which
node's mock timers can drive, unlike `AbortSignal.timeout()`. `Sizes` is an
`as const` object and there are no parameter properties, because node strips
types and does not transform them.

`IDENTIFIER_PATTERN` and the batch size are written down in PHP and in
TypeScript. `Tests/JavaScript/icons.test.ts` reads both PHP constants out of
the sources and fails when they differ.

The maps are read from the whole document, not from the root of a factory, and
the first map that carries an icon in a size wins. The promise cache is shared
by every factory of the page, so a map scoped to one root would answer every
other factory as well. Two maps can only disagree through markup injected into
the page, and an injection like that places its markup on the page without the
factory as well. An icon the endpoint refused stays refused for the page,
except where a map read later carries it: the factory reads the maps again
before it hands out a cached refusal, and a map read while the request was
under way answers the icon instead of the refusal.

Rejected: `localStorage`. The endpoint answers the current token as immutable,
so the browser cache already holds it, and web storage would be one more place
injected script could plant markup for later pages.

### A public contract, internal classes

The JSON icon map, the endpoint and the module are public API: the tag name
and arguments of the ViewHelper and the JSON it renders, the path,
parameters, answer and caching of the endpoint, and the exports of the
module, which carry `@api`. The extension points page names them the way it
names `<ab:icon>`, by tag name and arguments and without a class, so the
renderer, the ViewHelper class and the middleware stay `@internal` and can be
refactored in any release. Each part gets a Feature entry of its own in the
commit that adds it.

### The demonstration lives in the overview element

The icon overview element of `packages-dev/dev-site` gets a section with two
lists and a module of its own, `icon-demo.ts`: one list answered from the JSON
map next to it, one from the endpoint, `actions-add` included to show a
backend icon being left out. The seed and the SQLite snapshots do not change.

## Risks / Trade-offs

- [`PackageDependentCacheIdentifier` and `AbstractSvgIconProvider` are
  `@internal` in TYPO3] → Byte identical on both cores, and the frontend
  registry already keys its cache with the first. The call site says so and
  has to be re-read on every core update.
- [Code edited in place that is neither a provider nor a parent of one, the
  wrapper of core's `Icon` or the SVG sanitiser library] → Not seen by the
  token. The manual says so and names the remedy, touching the SVG files.
- [The token covers file times and the project path, so web nodes that deploy
  separately compute different tokens] → A page rendered on one node asks
  another one with a token that is not current there, and gets the correct
  answer cached for five minutes instead of a year. The manual says so.
- [The inline markup of core's `SvgIconProvider` is not sanitised on v13] →
  The same exposure a server rendered inline icon has. A provider is
  registered by an integrator, never by a visitor. Documented, not sanitised a
  second time, which would make the markup differ from the template's.
- [A web server rule serving `*.json` as static files answers `404`] → The
  manual names the requirement next to the endpoint.
- [A page with the slug `_academic/icons.json` becomes unreachable] → Named in
  the Impact of `Feature-FrontendIconEndpoint.rst`.

## Migration Plan

Nothing to migrate. A site package that wants to hand an icon of its own to
its JavaScript registers it in its `Configuration/FrontendIcons.php` and names
`academic_base` in the dependencies of its import map.
