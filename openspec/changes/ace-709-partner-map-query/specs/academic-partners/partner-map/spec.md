## MODIFIED Requirements

### Requirement: Only a partner with a location is drawn
The partner map SHALL draw a marker only for a partner whose coordinates are a
place, and SHALL report every partner it skips on the browser console. A
partner without a location SHALL NOT reach the page at all, and SHALL still
appear in the partner list.

#### Scenario: A partner that was never geocoded
- **WHEN** a partner carries no coordinates, or coordinates that are not a
  number, or the pair 0 / 0
- **THEN** no marker is drawn for it and its name is reported on the console

#### Scenario: A partner on the prime meridian or the equator
- **WHEN** a partner carries a single coordinate of 0 and a usable other one
- **THEN** its marker is drawn at that place

#### Scenario: The map of a site with unlocated partners
- **WHEN** a visitor opens a page with the partner map and some partners have no
  location
- **THEN** those partners are not in the page at all, and the located ones are
  drawn

#### Scenario: The list of the same site
- **WHEN** the same site renders the partner list
- **THEN** every partner is listed, including the ones without a location

## ADDED Requirements

### Requirement: A map with nothing to draw says so
When no partner of the map has a location, the extension SHALL tell the visitor
so instead of rendering an empty map, and SHALL not load the map assets.

#### Scenario: No partner has a location
- **WHEN** a page renders the partner map and no partner of it has a location
- **THEN** a message is shown in place of the map, and neither the map canvas
  nor its libraries are in the page

#### Scenario: The visitor filtered everything away
- **WHEN** the result is empty because the visitor's category filter matched
  nothing
- **THEN** the message says that, rather than blaming the missing locations

### Requirement: A translated partner is drawn at the same place
A place does not move when a partner page is translated, so the extension SHALL
draw a translated partner at the coordinates of its default record, and SHALL
bring translations created before the coordinates existed in line once.

#### Scenario: The map in a translated language
- **WHEN** a visitor opens the partner map in a language the partner is
  translated into
- **THEN** the partner is drawn at its place, under its translated title

#### Scenario: A translation created before geocoding ran
- **WHEN** an installation updates and a translation carries no coordinates of
  its own while its default record does
- **THEN** running the upgrade wizard gives the translation its default
  record's coordinates, unless an editor detached them deliberately
