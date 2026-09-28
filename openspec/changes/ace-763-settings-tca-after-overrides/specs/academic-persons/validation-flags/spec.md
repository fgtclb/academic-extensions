## ADDED Requirements

### Requirement: The settings reach the backend form after the TCA overrides
The system SHALL apply the validators of the persons settings to the backend
form of the six person tables after the TCA overrides of every package, on
TYPO3 v13 and v14. For a column a field of the settings configures, the
settings SHALL decide whether it is required and read only, whatever a TCA
override set. A field whose column is not in the TCA SHALL add no column. The
settings SHALL still be applied when the TCA comes from the cache.

#### Scenario: A site package replaces a column
- **WHEN** the settings require the teaching area and a site package replaces
  the teaching area column in its TCA override, with a label of its own
- **THEN** the backend form shows the label of the site package and requires
  the teaching area

#### Scenario: A site package replaces a timeline record type
- **WHEN** a site package replaces the publication record type of the profile
  information table with a form layout of its own
- **THEN** the backend form shows that layout and still requires the title and
  the year of a publication

#### Scenario: A site package locks a column the settings leave editable
- **WHEN** a TCA override of a site package makes the profile title read only
  and the settings do not lock it
- **THEN** the backend form lets editors change the title

#### Scenario: A field without a column
- **WHEN** the settings declare a profile field whose column no TCA declares
- **THEN** the profile table has no such column and the TCA is built

#### Scenario: A later listener of the compiled TCA
- **WHEN** a listener of another package changes the compiled TCA and orders
  itself after the settings
- **THEN** its change is kept
