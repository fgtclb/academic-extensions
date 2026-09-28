## Purpose

Defines which map libraries the partner map loads on the 2.x branch and what the
popup of a partner marker shows.

## ADDED Requirements

### Requirement: The map loads its libraries as published releases

The partner map SHALL load Leaflet and its marker cluster plugin as the
published releases named in the extension, as classic scripts at their existing
paths that publish the global `LeafletObject`. This SHALL apply on TYPO3 v12
and v13.

#### Scenario: The area of a cluster

- **WHEN** a visitor rests the pointer on a cluster of partners
- **THEN** the area the cluster covers is drawn

#### Scenario: A project template that loads the scripts

- **WHEN** a project template loads the Leaflet script by its path and reads
  `LeafletObject`
- **THEN** it finds Leaflet there as before

### Requirement: The popup shows the partner title as written

The popup of a partner marker SHALL show the title of the partner as it is
written, in bold, linked to the partner page.

#### Scenario: Title with special characters

- **WHEN** a partner title contains characters such as `<`, `>` or `&`
- **THEN** the popup shows those characters as they are written
