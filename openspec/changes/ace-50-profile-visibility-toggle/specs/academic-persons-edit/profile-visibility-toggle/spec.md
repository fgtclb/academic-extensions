## Purpose

Defines how the owner of a profile shows or hides that profile in the
frontend editor, and what the owner still reaches while it is hidden.

## ADDED Requirements

### Requirement: The owner shows or hides the own profile
The profile editor SHALL offer the owner of a profile a switch that shows the
profile publicly or hides it. Hiding SHALL remove the profile from every public
output that already omits a profile hidden in the backend, in every language
of the profile. This applies on TYPO3 v13 and v14.

#### Scenario: Owner hides the profile
- **WHEN** the owner switches "Show my profile publicly" off and a visitor
  opens a list and the detail page of that profile
- **THEN** the list omits the profile and the detail page answers with the
  site's page-not-found response

#### Scenario: Translated profile
- **WHEN** the owner switches the profile off, in the default language or in
  a translated site language
- **THEN** the profile is hidden in the default language and in every
  translation

#### Scenario: Backend shows the owner's choice
- **WHEN** an editor opens a profile its owner switched off
- **THEN** the profile is hidden in the backend

### Requirement: A hidden profile stays editable for its owner
The editor SHALL list and open an own profile of the logged-in owner while it
is hidden, including its image, so that the owner can show it again. The
profile's start time, end time and frontend user groups SHALL still apply.
Profiles of other owners SHALL stay unreachable.

#### Scenario: Owner shows the profile again
- **WHEN** the owner of a hidden profile opens the editor
- **THEN** the profile is listed, it opens, and switching it on makes it
  public again

#### Scenario: Another owner's hidden profile
- **WHEN** a logged-in visitor requests the editor for a hidden profile that
  is not theirs
- **THEN** the editor refuses it as before

#### Scenario: Profile outside its time window
- **WHEN** the owner's profile has an end time in the past
- **THEN** the editor does not list it, exactly as before

### Requirement: An installation decides whether owners hold the switch
The switch SHALL be available by default. An installation SHALL be able to
make it read-only, disable it or remove it through the editor configuration.
Where it is read-only, disabled or removed, a submitted value SHALL be refused
and the profile's visibility SHALL stay as the backend set it.

#### Scenario: Switch disabled by the installation
- **WHEN** the installation disables the switch and a request tries to show a
  profile an editor hid
- **THEN** the profile stays hidden

#### Scenario: Backend editors keep the checkbox
- **WHEN** the installation makes the switch read-only or disables it
- **THEN** a backend editor still shows and hides the profile in the backend
