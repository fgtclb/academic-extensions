## Why

The "Partner Map" of `academic_partners` (`packages/fgtclb/academic-partners`)
loads its tiles as images from OpenStreetMap. Once a site enforces the
frontend Content Security Policy of TYPO3, `img-src` blocks every tile and the
markers stay on an empty map (ACE-933). This backports the change of `main`,
archived there as `openspec/changes/archive/2026-10-16-ace-933-map-tile-csp`.

## What Changes

- The map content element extends `img-src` of the policy of its page by
  `https://*.tile.openstreetmap.org`, the host of the tile URL of the map
  script, with its placeholder label `{s}` turned into a wildcard. A page
  without the map gets nothing.
- The source applies to the page that generates it and to the same page when
  it is served from the page cache, on TYPO3 v12 and v13. The behaviour a
  visitor sees is the same on both, the point where the source is registered
  differs.
- Unlike `main`, the tile URL is a fixed value of the map script on this
  branch, there is no setting for it, so the source is always the one of
  OpenStreetMap.
- The configuration page of the manual, a 2.4 `Important` entry identical to
  the one of `main` and `docs/architecture/content-security-policy.md`
  describe it.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-partners/partner-map`: a new requirement, the map allows its tiles
  under the enforced frontend policy of TYPO3.

## Impact

- `academic_partners`: the map action, a data processor of the map content
  element and the service both use, `ext_localconf.php`, a unit and a
  functional test, `Configuration/Index.rst` and
  `Changelog/2.4/Important-PartnerMapTilesUnderAContentSecurityPolicy.rst`,
  which is identical on `main`.
- `docs/architecture/content-security-policy.md`.
- TYPO3 v12 and v13.

## Non-goals

- A setting for the tile URL, which `main` has and this branch does not get
  here.
- A static rule in `Configuration/ContentSecurityPolicies.php`, which would
  apply to every page of every site.
- A listener on the mutation event of the policy.
- A page template that draws a map with the map script itself, outside the
  content element: the site adds the source in its `csp.yaml`.
- A policy the web server or a proxy sends.
