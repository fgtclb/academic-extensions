## Purpose

Defines what the coordinates of a partner page are, how an editor enters them,
which partners a map can draw, and what the update does with the coordinates
already stored, on TYPO3 v13 and v14.

## ADDED Requirements

### Requirement: A coordinate is a number or absent

The latitude and the longitude of a partner page SHALL each be either a number
or absent. A partner page without a coordinate SHALL never report the
coordinate `0` in its place. This SHALL apply on TYPO3 v13 and v14 and on every
supported database.

#### Scenario: Partner page that was never geocoded

- **WHEN** a template outputs the latitude and longitude of a partner page that
  has no coordinates
- **THEN** both render as empty, not as `0`

#### Scenario: Partner page with one coordinate missing

- **WHEN** a partner page has a latitude and no longitude
- **THEN** a template that outputs the longitude renders it as empty

#### Scenario: Coordinate zero

- **WHEN** a partner page has the latitude `51.477928` and the longitude `0`
- **THEN** a template renders the longitude as `0`

### Requirement: The backend field accepts coordinates only

The latitude and longitude fields of a partner page SHALL save a number within
the range of its coordinate, latitude from -90 to 90 and longitude from -180 to
180, rounded to seven decimal places. They SHALL accept a decimal point and a
single decimal comma, and SHALL save the comma as a point. An emptied field
SHALL save "no coordinate". A value that is not a number or is out of range
SHALL NOT be saved.

#### Scenario: Editor enters a coordinate with a decimal point

- **WHEN** an editor enters `52.520008` as the latitude and saves
- **THEN** the partner page has the latitude `52.520008`

#### Scenario: Editor enters a coordinate with a decimal comma

- **WHEN** an editor enters `52,520008` as the latitude and saves
- **THEN** the partner page has the latitude `52.520008`

#### Scenario: Editor empties the field

- **WHEN** an editor empties the longitude of a partner page and saves
- **THEN** the partner page has no longitude, on every supported database

#### Scenario: Editor enters a latitude out of range

- **WHEN** an editor enters `152.5` as the latitude and saves
- **THEN** the latitude is not saved and the field keeps its previous value

#### Scenario: Editor enters text

- **WHEN** an editor enters `north` as the latitude and saves
- **THEN** the latitude is not saved and the field keeps its previous value

#### Scenario: Editor enters more decimals than are stored

- **WHEN** an editor enters `52.123456789` as the latitude and saves
- **THEN** the partner page has the latitude `52.1234568` on every supported
  database

### Requirement: One rule decides whether a partner can be drawn

A partner SHALL be drawable when it has a latitude and a longitude, both within
their range, and they are not both zero. The partner map content element, the
map partial for a single partner and the map script SHALL apply this rule
alike, so a partner the map content element hands to the page is always drawn
by the script, and a partner page whose single partner is not drawable renders
no map. A partner with a single zero coordinate SHALL be drawable.

#### Scenario: Partner with both coordinates

- **WHEN** a partner has the latitude `48.137154` and the longitude `11.576124`
- **THEN** the partner map draws it, and the map partial renders a map for it

#### Scenario: Partner with one coordinate missing

- **WHEN** a partner has a latitude and no longitude
- **THEN** the partner map leaves it out, and the map partial renders no map
  for it

#### Scenario: Both coordinates zero, however they were entered

- **WHEN** the only partner a partner map matches has the latitude and the
  longitude zero, stored before or after the update in any spelling
- **THEN** the partner map shows its message for a map without partners
  instead of an empty map

#### Scenario: Partner on the prime meridian or the equator

- **WHEN** a partner has the longitude `0` and a latitude that is not zero, or
  the latitude `0` and a longitude that is not zero
- **THEN** the partner map draws it

#### Scenario: Markup of a template override

- **WHEN** a template override renders a partner item with a coordinate out of
  range, not a number, empty, or the pair `0/0`
- **THEN** the map script draws no marker for it, as it draws none for a
  partner the map content element leaves out

### Requirement: The update keeps every coordinate it can read

The update SHALL carry every stored coordinate over to the numeric storage.
A coordinate with a decimal comma SHALL be read as the number it spells. A
stored value that is empty SHALL become "no coordinate". A stored value that is
not a number or out of range SHALL become "no coordinate" and SHALL be named in
the output of the upgrade wizard with the page it belongs to and the value it
held. A translation SHALL keep its own coordinates and whether an editor
detached them from its default record.

#### Scenario: Coordinate stored with a decimal point

- **WHEN** a partner page stored the latitude `52.520008` before the update
- **THEN** after the upgrade wizard ran it has the latitude `52.520008`, and the
  map draws it where it drew it before

#### Scenario: Coordinate stored with a decimal comma

- **WHEN** a partner page stored the latitude `52,52` before the update
- **THEN** after the upgrade wizard ran it has the latitude `52.52`, the map
  draws it, and the wizard names the converted value

#### Scenario: Unreadable coordinate

- **WHEN** a partner page stored the latitude `n/a` before the update
- **THEN** after the upgrade wizard ran it has no latitude, the map leaves it
  out, and the wizard names the page and the value `n/a`

#### Scenario: Detached translation

- **WHEN** an editor detached the coordinates of a translation before the update
- **THEN** after the upgrade wizard ran the translation keeps its own
  coordinates and stays detached

#### Scenario: Installation without stored coordinates

- **WHEN** an installation has no coordinates stored in the previous form
- **THEN** the upgrade wizard reports that nothing has to be done

### Requirement: Geocoding stores the coordinates it receives

The geocoding command SHALL store the coordinates the geocoding service
returns, rounded to seven decimal places. When the service finds no location,
the command SHALL leave the coordinates of the partner page as they are.

#### Scenario: Address found

- **WHEN** the geocoding service returns the latitude `52.5170365` and the
  longitude `13.3888599` for a partner page
- **THEN** the partner page has these coordinates and the map draws it

#### Scenario: Address not found

- **WHEN** the geocoding service finds no location for a partner page that has
  no coordinates
- **THEN** the partner page still has no coordinates and the map leaves it out
