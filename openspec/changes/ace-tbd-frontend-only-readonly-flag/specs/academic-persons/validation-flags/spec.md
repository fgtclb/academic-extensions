## Purpose

Defines how the validator flags an integrator lists in the `academic_persons`
settings file affect the frontend profile editor and the backend form.

## ADDED Requirements

### Requirement: A frontend-only read-only flag
The system SHALL accept the case-insensitive validator flag
`frontendreadonly` for profile, contract and contract contact fields. On
TYPO3 v13 and v14, a field carrying it SHALL be shown read-only in the
frontend editor, with submitted values ignored, and SHALL stay editable in
the backend form.

#### Scenario: Last name locked for frontend users only
- **WHEN** an integrator lists `frontendreadonly` for the profile last name
- **THEN** the frontend editor shows the last name read-only and ignores a
  submitted change, while a backend user can still edit it

#### Scenario: Flag written in mixed case
- **WHEN** an integrator lists `FrontendReadOnly`
- **THEN** it behaves exactly like `frontendreadonly`

### Requirement: Existing read-only flags keep locking both places
The system SHALL keep locking a field in the frontend editor and in the
backend form when it carries `readonly` or `disabled`, also when
`frontendreadonly` is listed as well.

#### Scenario: Both flags listed
- **WHEN** a field lists `frontendreadonly` and `readonly`
- **THEN** the field is read-only in the frontend editor and in the backend
  form
