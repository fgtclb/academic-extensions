# academic-partners/map-configuration Specification

## Purpose

Lets integrators configure the partner map per site, lets editors choose its
layout, and lets page templates reuse the map for a single partner, on TYPO3
v13 and v14.

## Requirements

### Requirement: Integrators configure the map per site

The partner map SHALL read its fallback centre, initial zoom, maximum zoom,
padding, tile URL template and attribution from the site settings of the
partner map set. Without configuration it SHALL use the values it used
before this change: centre 51.1657 / 10.4515, zoom 6, maximum zoom 18,
padding 50 pixels and the OpenStreetMap tile server.

#### Scenario: Site without map settings

- **WHEN** a site uses the partner map without setting any map setting
- **THEN** the map looks and behaves as before this change

#### Scenario: Site limits the maximum zoom

- **WHEN** an integrator sets the maximum zoom to 12 and the map shows a
  single partner
- **THEN** the map zooms in no further than level 12

#### Scenario: Site uses its own centre

- **WHEN** an integrator sets the centre to a location in Austria and no
  partner matches the filter
- **THEN** the empty map is centred on that location at the configured zoom

#### Scenario: Site uses its own tile server

- **WHEN** an integrator sets a tile URL template and attribution
- **THEN** the map loads its tiles from that server and shows that
  attribution

### Requirement: Editors choose the map layout

The partner map content element SHALL offer a layout choice "content width"
(default) and "full width". "Full width" SHALL add a modifier class to the
map container and change nothing else.

#### Scenario: Editor chooses full width

- **WHEN** an editor sets the map layout to "full width"
- **THEN** the rendered map container carries the full width modifier class

#### Scenario: Existing map element

- **WHEN** a map content element created before this change is rendered
- **THEN** it renders with the content width layout

### Requirement: The map can be rendered for any partner list

The map markup SHALL be available to templates as a partial that takes a
list of partners. The partner map plugin SHALL render its map through that
partial, and a page template SHALL be able to render a map for the single
partner of a partner page.

#### Scenario: Partner page shows its own location

- **WHEN** an integrator renders the map partial in the partner page template
  for the page's partner, which has coordinates
- **THEN** the page shows a map with one marker at that partner's location

#### Scenario: Partner page uses the map settings of the site

- **WHEN** an integrator renders the map partial in the partner page template
  with the map settings the page offers, and the site limits the maximum zoom
- **THEN** the map of that partner zooms in no further than that level, on a
  `FLUIDTEMPLATE` and on a `PAGEVIEW` page object

#### Scenario: Partner without coordinates

- **WHEN** the partner of the page has no coordinates
- **THEN** the partial renders no map

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
