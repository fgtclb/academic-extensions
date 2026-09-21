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
place, and SHALL report every partner it skips on the browser console.

#### Scenario: A partner that was never geocoded
- **WHEN** a partner carries no coordinates, or coordinates that are not a
  number, or the pair 0 / 0
- **THEN** no marker is drawn for it and its name is reported on the console

#### Scenario: A partner on the prime meridian or the equator
- **WHEN** a partner carries a single coordinate of 0 and a usable other one
- **THEN** its marker is drawn at that place
