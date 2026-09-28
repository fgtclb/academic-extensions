## Purpose

Decides which partners the partner map draws, from the "Show on map" switch
of a partner page.

## ADDED Requirements

### Requirement: The partner map draws only partners switched on for it

The partner map content element SHALL leave out every partner whose "Show on
map" switch is off, together with the partners that have no coordinates. This
SHALL apply on TYPO3 v13 and v14.

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

### Requirement: The map partial respects the switch for a single partner

The map partial SHALL render no map for a single partner whose "Show on map"
switch is off, as it renders none for a partner without coordinates.

#### Scenario: Partner page of a switched off partner

- **WHEN** a page template renders the map partial for a partner that has
  coordinates and "Show on map" switched off
- **THEN** no map is rendered

### Requirement: The default language decides

The switch SHALL be read from the partner in the default language. A
translation of a partner page SHALL NOT hold a value of its own.

#### Scenario: Translated partner

- **WHEN** a partner is switched off in the default language and the map is
  rendered in another language
- **THEN** the map does not draw that partner in that language either
