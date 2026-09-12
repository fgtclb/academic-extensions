## Purpose

Defines how an integrator makes a project-specific column of the profile
editable in the frontend profile editor of `academic_persons_edit`, on TYPO3
v13 and v14.

## ADDED Requirements

### Requirement: Integrators declare project profile fields
The system SHALL accept a profile field declared as a project field with its
database column, field type, renderer and validators, and SHALL show it in the
frontend editor at the configured position.

#### Scenario: Declared name prefix
- **WHEN** an integrator ships the column `tx_test_prefix` with SQL and TCA
  and declares it as a project field with a text renderer
- **THEN** the editor shows the field with the stored value of the profile

#### Scenario: Column missing in TCA
- **WHEN** an integrator declares a project field whose column is not in the
  profile TCA
- **THEN** building the settings fails with a message naming the column

### Requirement: Project field values are validated and stored
The system SHALL validate, limit and sanitise a submitted project field value
exactly as a regular field with the same configuration, and SHALL store an
accepted value in the declared column.

#### Scenario: Saving a project field
- **WHEN** a person submits a new name prefix
- **THEN** the column `tx_test_prefix` of the profile holds the new value

#### Scenario: Invalid project field value
- **WHEN** a person submits a value that fails a configured validator
- **THEN** the response reports the validation error and neither the project
  field nor any other field of the request is stored

### Requirement: Project fields follow the edited language
The system SHALL read and write a project field on the translation when the
person edits a translation of the profile.

#### Scenario: Translated name prefix
- **WHEN** the person saves a name prefix while editing the language-1
  translation
- **THEN** the language-1 profile row holds the value and the default-language
  row keeps its own
