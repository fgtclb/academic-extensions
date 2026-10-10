## MODIFIED Requirements

### Requirement: The map loads its libraries as published releases

The partner map SHALL load Leaflet and its marker cluster plugin as the
published releases named in the extension, as ES modules, and
SHALL set no global variable, unless the integrator switches the map script
off. This SHALL apply on TYPO3 v13 and v14.

#### Scenario: A map with partners

- **WHEN** a page renders the partner map content element on a site that
  leaves the map script switched on
- **THEN** the map is drawn with the Leaflet and marker cluster releases of
  the extension, loaded as modules, and no classic map script is loaded

#### Scenario: The area of a cluster

- **WHEN** a visitor rests the pointer on a cluster of partners
- **THEN** the area the cluster covers is drawn

### Requirement: The map brings the stylesheets of its libraries only
A page that carries the partner map SHALL load the stylesheets of the map
libraries, which the map needs to work, unless the integrator switches the map
script off, and SHALL NOT load a stylesheet of the extension in any
configuration. Sizing the map is up to the site.

#### Scenario: A page with the map
- **WHEN** a page carries the partner map on a site that leaves the map script
  switched on
- **THEN** the page loads the stylesheets of Leaflet and of its marker cluster
  plugin and no other stylesheet of the extension

#### Scenario: A site that does not size the map
- **WHEN** the stylesheet of a site gives the map element no height
- **THEN** the page shows no map

## ADDED Requirements

### Requirement: The map script can be switched off
The integrator SHALL be able to switch the map script off through the site
setting or the TypoScript constant of the same name. Switched off, a page with
the partner map content element, and a partner page whose template renders
the map with the switch, SHALL load neither the map script nor the stylesheets
of the map libraries, and SHALL render the same map element and the same
hidden list of partners for a script of the site. A page template that renders
the map without passing the switch SHALL keep loading both. This SHALL apply on
TYPO3 v13 and v14.

#### Scenario: Switched off in the site settings
- **WHEN** the site setting for the map script is off and a page carries the
  partner map content element
- **THEN** the page loads neither the map script nor the library stylesheets,
  and renders the map element with its settings and the partners

#### Scenario: Switched off through the constant
- **WHEN** a site that includes the static template sets the constant for the
  map script to off
- **THEN** the page with the partner map loads neither the map script nor the
  library stylesheets

#### Scenario: A partner page template that passes the switch
- **WHEN** the switch is off, through the site setting or the constant, and a
  partner page template renders the map of its partner with the switch the
  page offers
- **THEN** the partner page loads neither the map script nor the library
  stylesheets, and renders the map element

#### Scenario: A partner page template that does not pass the switch
- **WHEN** the switch is off and a partner page template renders the map of its
  partner without passing the switch
- **THEN** the partner page loads the map script and the library stylesheets
