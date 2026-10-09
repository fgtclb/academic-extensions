## ADDED Requirements

### Requirement: The map brings the stylesheets of its libraries only
A page that carries the partner map SHALL load the stylesheets of the map
libraries, which the map needs to work, and SHALL NOT load a stylesheet of the
extension. Sizing the map is up to the site.

#### Scenario: A page with the map
- **WHEN** a page carries the partner map
- **THEN** the page loads the stylesheets of Leaflet and of its marker cluster
  plugin and no other stylesheet of the extension

#### Scenario: A site that does not size the map
- **WHEN** the stylesheet of a site gives the map element no height
- **THEN** the page shows no map
