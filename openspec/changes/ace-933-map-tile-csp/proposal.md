## Why

The "Partner Map" of `academic_partners` (`packages/fgtclb/academic-partners`)
loads its tiles as images from a tile server, OpenStreetMap by default. Once a
site enforces the frontend Content Security Policy of TYPO3, `img-src` blocks
every tile and the markers stay on an empty map (ACE-933).

## What Changes

- The map content element extends `img-src` of the policy of its page by the
  host of the tile URL the map uses, with a host label holding a placeholder
  such as `{s}` turned into a wildcard. A configured tile server is followed
  without a change of the site, and a page without the map gets nothing.
- The source applies to the page that generates it and to the same page when
  it is served from the page cache.
- A relative tile URL adds nothing, `'self'` covers it. A tile URL the
  extension cannot turn into a source adds nothing either, and the manual shows
  the `csp.yaml` rule for that case.
- The manual of the map, the configuration page, a 2.4 `Important` entry and
  `docs/architecture/content-security-policy.md` describe it.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-partners/map-configuration`: a new requirement, the map allows
  its tiles under the enforced frontend policy of TYPO3.

## Impact

- `academic_partners`: the map action, a data processor of the map content
  element and the service both use, `ext_localconf.php`, a unit and a
  functional test, `Integrator/PartnerMap.rst`, `Configuration/Index.rst` and
  `Changelog/2.4/Important-PartnerMapTilesUnderAContentSecurityPolicy.rst`,
  which must be identical on branch `2`.
- `docs/architecture/content-security-policy.md`.
- TYPO3 v13 and v14 behave the same.

## Non-goals

- A static rule in `Configuration/ContentSecurityPolicies.php`, which would
  apply to every page of every site and cover the default host only.
- A listener on the mutation event of the policy.
- A page template that renders the map partial itself, a partner page for
  example: the shipped partner page renders no map, and a site template that
  adds one adds the source in its `csp.yaml`.
- A policy the web server or a proxy sends.
