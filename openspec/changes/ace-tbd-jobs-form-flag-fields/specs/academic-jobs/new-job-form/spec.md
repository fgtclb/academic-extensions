## Purpose

Defines what the new-job form offers a frontend user and how it stores the
two job flags "internationals welcome" and "recommended by alumni".

## ADDED Requirements

### Requirement: The form offers the two job flags

The new-job form SHALL offer "internationals welcome" and "recommended by
alumni" as optional checkboxes with translated labels. This applies to
TYPO3 v13 and v14 alike.

#### Scenario: Form is rendered

- **WHEN** a visitor opens the new-job form
- **THEN** the form contains a checkbox for each of the two flags

#### Scenario: Alumni flag label

- **WHEN** a visitor opens the new-job form on the default language
- **THEN** the alumni checkbox is labelled "Recommended by alumni", and on a
  German site language "Von Alumni empfohlen"

### Requirement: Flags are stored as set or not set

A submitted job SHALL store a flag as set when its checkbox was checked and as
not set when it was unchecked. An unchecked flag MUST NOT cause a validation
or mapping error.

#### Scenario: Flag unchecked

- **WHEN** a visitor submits the form with both flag checkboxes unchecked and
  all required fields filled
- **THEN** the job is created with both flags not set and no error is shown

#### Scenario: Flag checked

- **WHEN** a visitor submits the form with the "internationals welcome"
  checkbox checked
- **THEN** the job is created with that flag set

### Requirement: The form has a slot for additional fields

The new-job form SHALL render an additional-fields partial before its submit
button. The shipped partial SHALL render nothing, so an integrator can add
fields by overriding that one partial.

#### Scenario: Default installation

- **WHEN** the form is rendered without an override of the partial
- **THEN** the form output is unchanged apart from the two flag checkboxes

#### Scenario: Integrator adds a field

- **WHEN** an integrator overrides the additional-fields partial with a field
- **THEN** the field is rendered before the submit button
