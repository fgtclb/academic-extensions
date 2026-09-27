# academic-persons/validation-flags Specification

## Purpose
Defines how the validator flags an integrator lists in the `academic_persons`
settings file affect the frontend profile editor and the backend form.

## Requirements

### Requirement: A frontend-only read-only flag
The system SHALL accept the case-insensitive validator flag
`frontendreadonly` in every set of the `validations` map. On TYPO3 v12 and
v13, a field carrying it SHALL be marked read-only in the frontend editor,
with submitted values ignored, and SHALL stay editable in the backend form. A
field that is also `required` SHALL stay required in the backend form and
SHALL NOT be required in the frontend editor.

#### Scenario: A read-only select stays operable in the browser
- **WHEN** an integrator lists `frontendreadonly` for the contract function
  type, which the editor renders as a select
- **THEN** the owner can still change the select in the browser, and the
  changed value is discarded when the form is saved

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
