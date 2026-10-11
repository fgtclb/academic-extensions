# academic-partners/map-libraries Specification

## Purpose

Defines which map libraries the partner map loads and what the popup of a
partner marker shows, on TYPO3 v13 and v14.

## Requirements

### Requirement: The map loads its libraries as published releases

The partner map SHALL load Leaflet and its marker cluster plugin as the
published releases named in the extension, as ES modules, and
SHALL set no global variable. This SHALL apply on TYPO3 v13 and v14.

#### Scenario: A map with partners

- **WHEN** a page renders the partner map content element
- **THEN** the map is drawn with the Leaflet and marker cluster releases of
  the extension, loaded as modules, and no classic map script is loaded

#### Scenario: The area of a cluster

- **WHEN** a visitor rests the pointer on a cluster of partners
- **THEN** the area the cluster covers is drawn

### Requirement: The popup shows the partner title as written

The popup of a partner marker SHALL show the title of the partner as it is
written, linked to the partner page.

#### Scenario: Ordinary title

- **WHEN** a visitor opens the popup of a partner titled "Alpha University"
- **THEN** the popup shows "Alpha University" in bold, linked to the page of
  that partner

#### Scenario: Title with special characters

- **WHEN** a partner title contains characters such as `<`, `>` or `&`
- **THEN** the popup shows those characters as they are written

### Requirement: The classic files stay available until 4.0

The classic script and stylesheet files the map loaded before SHALL stay
available at their paths, unchanged, until version 4.0.

#### Scenario: A project template that loads the classic files

- **WHEN** a project template loads the classic Leaflet script by its path
- **THEN** the file is still delivered

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
