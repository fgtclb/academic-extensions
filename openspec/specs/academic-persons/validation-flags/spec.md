# academic-persons/validation-flags Specification

## Purpose
Defines how the validator flags an integrator lists in the `academic_persons`
settings file affect the frontend profile editor and the backend form.

## Requirements

### Requirement: A frontend-only read-only flag
The system SHALL accept the case-insensitive validator flag
`frontendreadonly` for profile, contract, contract contact and document
fields, in the flag list and as `frontendreadonly: true` in the expanded map
of a document field. On TYPO3 v13 and v14, a field carrying it SHALL be shown
read-only in the frontend editor, with submitted values ignored, and SHALL
stay editable in the backend form. A field that is also `required` SHALL stay
required in the backend form and SHALL NOT be required in the frontend
editor.

#### Scenario: Last name locked for frontend users only
- **WHEN** an integrator lists `frontendreadonly` for the profile last name
- **THEN** the frontend editor shows the last name read-only and ignores a
  submitted change, while a backend user can still edit it

#### Scenario: Flag written in mixed case
- **WHEN** an integrator lists `FrontendReadOnly`
- **THEN** it behaves exactly like `frontendreadonly`

#### Scenario: Required contract position locked for owners
- **WHEN** an integrator lists `required` and `frontendreadonly` for the
  contract position
- **THEN** the backend form requires a position, the frontend editor shows it
  read-only without asking the owner for a value, and a submitted change is
  ignored

### Requirement: Existing read-only flags keep locking both places
The system SHALL keep locking a field in the frontend editor and in the
backend form when it carries `readonly` or `disabled`, also when
`frontendreadonly` is listed as well.

#### Scenario: Both flags listed
- **WHEN** a field lists `frontendreadonly` and `readonly`
- **THEN** the field is read-only in the frontend editor and in the backend
  form

### Requirement: A pre-3.0 settings file keeps its frontend-only lock
The system SHALL treat `frontendreadonly` as a flag of the pre-3.0
`validations` map. On TYPO3 v13 and v14, a field that such a map lists with
the flag SHALL keep the frontend-only lock after the update, and a field the
map does not list SHALL NOT carry it.

#### Scenario: A 2.4 site package with the flag is updated
- **WHEN** a site package still ships a `validations` map that lists
  `frontendreadonly` for the profile title
- **THEN** after the update the title is read-only in the frontend editor and
  editable in the backend form, as it was before

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
