# academic-partners/partner-map Specification

## Purpose
Defines when the partner map draws and which partners it shows.

## Requirements

### Requirement: The map draws whenever the module runs
The partner map SHALL be drawn whether its module is evaluated before or after
the document has finished parsing.

#### Scenario: The module is evaluated after parsing finished
- **WHEN** a visitor opens a page with the partner map and the browser
  evaluates the module after the document is parsed, which an asynchronously
  loaded module regularly is
- **THEN** the map, its tiles and its markers are drawn

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

### Requirement: The partner map draws only partners switched on for it
The partner map content element SHALL leave out every partner whose "Show on
map" switch is off, together with the partners that have no coordinates. This
SHALL apply on TYPO3 v12 and v13.

#### Scenario: Partner switched off
- **WHEN** a partner page with coordinates has "Show on map" switched off
- **THEN** the partner map does not draw that partner

#### Scenario: Partner switched on
- **WHEN** a partner page with coordinates keeps "Show on map" switched on,
  which is the default
- **THEN** the partner map draws that partner as before

#### Scenario: The partner list is not affected
- **WHEN** a partner has "Show on map" switched off
- **THEN** the partner list content element still lists it

### Requirement: A template can apply the rule to a single partner
A partner SHALL tell a template whether the map draws it: it has coordinates to
draw and its "Show on map" switch is on.

#### Scenario: Partner page of a partner hidden from the map
- **WHEN** a page template renders a map for a partner that has coordinates
  and "Show on map" switched off, and checks whether the partner is shown on the
  map
- **THEN** the partner is not shown on the map

### Requirement: A translation follows its default record
The map SHALL read the switch of the partner in the language of the page. A
translation SHALL follow the switch of its default record unless an editor
detached it from its default record.

#### Scenario: Default record switched off
- **WHEN** an editor switches a partner off the map in the default language
  and the translation is not detached
- **THEN** the map draws that partner in no language

#### Scenario: Detached translation
- **WHEN** an editor detached the switch of a translation and switched it off
- **THEN** the map of that language leaves the partner out, and the map of the
  default language still draws it

#### Scenario: Translation made before the update
- **WHEN** a translation made before the update holds a different switch than
  its default record and is not detached
- **THEN** after the upgrade wizard ran, the map of that language follows the
  default record
