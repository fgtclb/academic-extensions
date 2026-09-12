## Purpose

Defines which letters the persons list offers a visitor, which of them can be
selected, and when the letter navigation is shown at all.

## ADDED Requirements

### Requirement: Letters without profiles cannot be selected
The letter navigation SHALL render a letter as a link only when selecting it
yields at least one profile. Any other letter SHALL be rendered disabled,
without a link, and marked as disabled for assistive technology. This applies
on TYPO3 v13 and v14.

#### Scenario: No last name starts with the letter
- **WHEN** no profile of the list has a last name starting with C
- **THEN** the letter C is rendered disabled and without a link

#### Scenario: A last name starts with the letter
- **WHEN** a profile of the list has a last name starting with B
- **THEN** the letter B is rendered as a link to the list filtered by B

### Requirement: Availability follows the list's own constraints
The availability of a letter SHALL be determined by the same constraints as
the list: storage folders, the editor's function type and organisational
unit restriction, the hidden-records option and the current language. The
active letter itself MUST NOT reduce the availability of other letters.

#### Scenario: Restriction by organisational unit
- **WHEN** the editor restricted the list to one organisational unit and the
  only profile starting with D belongs to another unit
- **THEN** the letter D is rendered disabled

#### Scenario: Another letter is active
- **WHEN** a visitor selected the letter A and profiles starting with B exist
- **THEN** the letter B is still rendered as a link

### Requirement: The active letter is marked as current
The active letter SHALL be marked as the current item. By default it SHALL
not be a link. When an integrator enables the reset option, the active letter
SHALL link to the list without a letter.

#### Scenario: Default behaviour
- **WHEN** a visitor selected the letter A and the reset option is off
- **THEN** A is marked as current and rendered without a link

#### Scenario: Reset option enabled
- **WHEN** a visitor selected the letter A and the reset option is on
- **THEN** A is marked as current and links to the list without a letter

### Requirement: No letter navigation for a manual selection
The list SHALL render no letter navigation when the plugin shows a manual
selection of profiles, even when the letter navigation is enabled.

#### Scenario: Manual selection with letter navigation enabled
- **WHEN** an editor selected profiles manually and enabled the letter
  navigation
- **THEN** the list renders the selected profiles and no letter navigation
