## ADDED Requirements

### Requirement: The map allows its tiles under the enforced policy

On a site that enforces the frontend Content Security Policy of TYPO3, the
page that renders the partner map content element SHALL allow images from the
host of the tile URL the map uses, on TYPO3 v13 and v14. A host label with a
placeholder SHALL be allowed as a wildcard for every subdomain. The sources the
policy of the site allows for images SHALL stay allowed. A page without the
map SHALL not allow the tile host. A relative tile URL, which the site itself
serves, SHALL add nothing.

#### Scenario: Site with the default tile server

- **WHEN** a site that enforces the policy renders a page with the map and
  sets no tile URL
- **THEN** the policy of that page allows images from every subdomain of
  `tile.openstreetmap.org`, next to the images it allowed before

#### Scenario: Site with its own tile server

- **WHEN** an integrator sets a tile URL of another host
- **THEN** the policy of the map page allows images from that host and not
  from OpenStreetMap

#### Scenario: Page served from the cache

- **WHEN** the map page is requested again and served from the page cache
- **THEN** its policy allows the tile host as on the request that generated
  the page

#### Scenario: Site that inherits no default policy

- **WHEN** the site policy inherits nothing of the default policy of TYPO3
- **THEN** the policy of the map page allows the tile host as well

#### Scenario: Page without the map

- **WHEN** a page of the same site renders the partner list but no map
- **THEN** its policy does not allow the tile host
