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
