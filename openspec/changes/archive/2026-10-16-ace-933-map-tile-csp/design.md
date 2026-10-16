## Context

See proposal.md for the motivation. The default frontend policy of
`cms-frontend/Configuration/ContentSecurityPolicies.php` has
`img-src 'self' data: *.ytimg.com *.vimeocdn.com` and
`style-src-attr 'unsafe-inline'`, identical on TYPO3 13.4.35, 14.3.7 and 14.3.9. The
tile URL is `plugin.tx_academicpartners.map.tileUrl`, a constant and a setting
of the map set, and reaches the map module as a data attribute. The map action
is not cacheable.

`PolicyRegistry` of EXT:core is a request scoped registry whose mutation
collections `PolicyProvider::provideFor()` applies after those of the packages
and the site, whatever `inheritDefault` says, the same on 13 and 14. The core
registers the hash of an import map there. `MetaDataState` of EXT:frontend
writes its collections into the page cache entry and restores them in
`PrepareTypoScriptFrontendRendering`, before the non-cacheable parts of a
cached page run.

On a request that generates a page, `RequestHandler::handle()` prepares the
policy right after the cacheable content and before the non-cacheable parts,
and `ContentSecurityPolicyHeaders` keeps that prepared policy, on 13 and 14.

## Goals / Non-Goals

**Goals:** the tile host of the configured tile URL on the map page only, on
the generating and on the cached request, without a change of the site.

**Non-Goals:** see proposal.md.

## Decisions

- **The registry, not a static rule and not a listener.** The maintainer chose
  `PolicyRegistry::appendMutationCollection()`. A rule in
  `ContentSecurityPolicies.php` knows no site settings and applies to every
  page, a listener of `PolicyMutatedEvent` runs for every policy of every page
  and would have to find the map on its own.
- **Registered by the content element, not only by the action.** The maintainer
  asked for the map action. The functional test showed on 13 and 14 that a
  mutation the uncached action registers misses the header of the request that
  generates the page, and of every page that is never cached: the policy is
  prepared before the action runs. A data processor
  `academic-partners-map-tile-source` on `tt_content.academicpartners_map` runs
  with the cacheable content element, before the policy is prepared, and
  `MetaDataState` carries its mutation to every cached request. It is added in
  `ext_localconf.php` with `addTypoScript(…, 'defaultContentRendering')` right
  after `configurePlugin()`, so it extends the rendering definition of the
  plugin wherever that exists, and the reference to `lib.contentElement` keeps
  the processors of that object. The action registers the tile URL it renders
  as well: idempotent, since an equal source is not added twice, and it covers
  the cached pages of a site whose rendering of the element has no data
  processors. A cached `stdWrap` user function or an event of the content
  object renderer were the alternatives: the first is the same TypoScript
  wiring in a less obvious place, the second a listener.
- **The tile URL of the processor is the one of the action.** The processor
  reads `settings.map.tileUrl` in the order `FrontendConfigurationManager`
  merges the settings on 13 and 14: `plugin.tx_academicpartners_map`, which
  `overrideConfigurationFromPlugin()` merges last, over the plugin object `20`
  of the content element, over `plugin.tx_academicpartners`. The FlexForm of
  the map holds no tile URL. A constant in the processor configuration would
  miss a site that sets the plugin setting directly.
- **The source from the URL.** Scheme, host and port. Every host label up to
  the last one with a placeholder becomes `*`, because a source can only carry
  a wildcard as its first label: `https://{s}.tile.openstreetmap.org/…` is
  `https://*.tile.openstreetmap.org`. A URL without a scheme yields a source
  without one, which the browser matches against the page. `UriValue` of
  EXT:core parses `https://*.host` and `*.host` on 13 and 14 alike. An empty
  URL is the default of the map module. A relative URL, an unreadable host, a
  placeholder in scheme or port, another scheme, a wildcard on a top level
  domain, a port outside 1 to 65535, on which `UriValue` throws, and a
  scheme-less URL with a port yield nothing.
- **`Extend` on `img-src`.** Keeps `'self'` and `data:`, and copies
  `default-src` where `img-src` is missing.
- **One stateless service** `MapTileSourceRegistration`, final readonly, with
  the registry injected, used by the action and the processor. The processor
  is final and internal and carries its tag as an `#[AutoconfigureTag]`
  attribute and `#[Autoconfigure(public: true)]`: a processor with a
  collaborator is published, so it is constructed through the container also
  where TypoScript names its class.

## Risks / Trade-offs

- [A site renders the element without the data processors of
  `lib.contentElement`] → the action covers its cached pages, the manual
  documents the `csp.yaml` rule for the rest.
- [A core release that caches policies, `@todo add policy cache per scope` of
  `PolicyProvider`] → the registry is applied per request today. The
  functional test fails if that changes.
- [Leaflet] its tiles and markers are positioned through the style properties
  of elements, which no policy restricts, its assets are local and its only
  image URL is `data:`. Nothing else is needed.
