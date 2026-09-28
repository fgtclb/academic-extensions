# academic-partners/map-show-on-map Specification

## Purpose

Decides which partners the partner map draws, from the "Show on map" switch
of a partner page, on TYPO3 v13 and v14.

## Requirements

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
