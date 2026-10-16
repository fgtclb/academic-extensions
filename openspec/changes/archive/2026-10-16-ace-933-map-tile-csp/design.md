## Context

See proposal.md for the motivation. The default frontend policy of
`cms-frontend/Configuration/ContentSecurityPolicies.php` has
`img-src 'self' data: *.ytimg.com *.vimeocdn.com` and
`style-src-attr 'unsafe-inline'`, identical on TYPO3 12.4.45 and 13.4. The
tile URL `https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png` is a fixed value
of `Resources/Private/TypeScript/frontend/map.ts`. The map action is not
cacheable.

`PolicyRegistry` of EXT:core is a request scoped registry whose mutation
collections `PolicyProvider::provideFor()` applies after those of the packages
and the site, whatever `inheritDefault` says, on 12 and 13. `MetaDataState` of
EXT:frontend writes its collections into the page cache entry and restores
them before the non-cacheable parts of a cached page run, on 12 in
`TypoScriptFrontendController` and on 13 in
`PrepareTypoScriptFrontendRendering`.

The two cores differ in when the policy is built. TYPO3 12
`ContentSecurityPolicyHeaders` builds it after the whole request handler, the
non-cacheable parts included. TYPO3 13 `RequestHandler::handle()` prepares it
right after the cacheable content, before the non-cacheable parts, and the
middleware keeps that prepared policy.

## Goals / Non-Goals

**Goals:** the tile host on the map page only, on the generating and on the
cached request, on 12 and 13, with the code of `main` wherever the branch
allows it.

**Non-Goals:** see proposal.md.

## Decisions

- **The design of `main`, unchanged where possible.** The registry, a data
  processor `academic-partners-map-tile-source` on
  `tt_content.academicpartners_map` added in `ext_localconf.php` with
  `addTypoScript(…, 'defaultContentRendering')` right after
  `configurePlugin()`, and a registration in the map action. The service
  `MapTileSourceRegistration` keeps the derivation of `main` and its unit
  test, so a later setting for the tile URL needs no new code.
- **The processor on both cores.** On 12 the action alone covers every
  request, the functional test is green with the processor removed. On 13 the
  action alone misses the generating request, the test is red without the
  processor. A switch in `ext_localconf.php` would save one data processor
  call per map element on 12 and make the two cores differ in their rendering
  definition, so the processor runs on both. Its mutation is stored with the
  page on 12 as well, and an equal source is not added twice.
- **The fixed tile URL.** The processor and the action register
  `MapTileSourceRegistration::DEFAULT_TILE_URL`. The processor reads no
  TypoScript, as there is no setting to read. A unit test fails when the
  constant and the URL of the map script differ.
- **PHP 8.1.** `final class` with `private readonly` properties instead of
  `final readonly class`. The controller gets the service through a final
  `inject` method, as its other late collaborators, because project subclasses
  call its constructor.
- **The tag as an attribute, published.** The processor carries its
  `data.processor` tag as Symfony's `#[AutoconfigureTag]` and is published with
  `#[Autoconfigure(public: true)]`, as on `main`, while `Services.yaml` of the
  extension tags the older processors. Published, because a processor with a
  collaborator that TypoScript names by its class is only constructed through
  the container when it is public. Both attributes exist on Symfony 6.4 and
  7.4, the functional test is green on 12 with PHP 8.1 and Symfony 6.4, and the
  class is `final`, so no subclass inherits the identifier. The rejected
  alternative was a YAML entry, which would make the class differ from `main`
  for no behaviour.

## Risks / Trade-offs

- [A site renders the element without the data processors of
  `lib.contentElement`] → on 12 the action covers it fully, on 13 the action
  covers its cached pages and the manual documents the `csp.yaml` rule.
- [A core release that caches policies] → the registry is applied per request
  today. The functional test fails if that changes.
- [The tile URL of the map script changes] → the unit test fails until the
  constant follows.
