## ADDED Requirements

### Requirement: The map allows its tiles under the enforced policy

On a site that enforces the frontend Content Security Policy of TYPO3, the
page that renders the partner map content element SHALL allow images from
every subdomain of `tile.openstreetmap.org`, the tile server of the map, on
TYPO3 v12 and v13. The sources the policy of the site allows for images SHALL
stay allowed. A page without the map SHALL not allow the tile host.

#### Scenario: Site that enforces the policy

- **WHEN** a site that enforces the policy renders a page with the map
- **THEN** the policy of that page allows images from every subdomain of
  `tile.openstreetmap.org`, next to the images it allowed before

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
