# academic-jobs/new-job-form Specification

## Purpose
Defines what the new-job form of `academic_jobs` offers a visitor, how it
stores the two job flags "internationals welcome" and "recommended by
alumni" and the dates a visitor enters, and where an integrator adds a field
of their own.

## Requirements

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

#### Scenario: Internationals flag label

- **WHEN** a visitor opens the new-job form on the default language
- **THEN** the internationals checkbox is labelled "International applicants
  welcome", and on a German site language "Internationale Bewerbungen
  willkommen"

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

#### Scenario: Form returned with an error

- **WHEN** a visitor submits the form with a flag checked and a required field
  empty
- **THEN** the form is shown again with the error, and the flag is still
  checked

### Requirement: Dates are stored as whole days

The form SHALL store the day a submitted job is shown from, the field "When",
at the start of that day and the application deadline at the last second of
its day, both in the time zone of the server, whatever the time of day of the
submission. The employment start date SHALL be stored as the chosen day. A date
left empty SHALL stay empty. This applies to TYPO3 v13 and v14 alike.

#### Scenario: Shown from, deadline and employment start date

- **WHEN** a visitor submits the form with "When" 2027-03-01, the application
  deadline 2027-03-31 and the employment start date 2027-04-01
- **THEN** the job is shown from 2027-03-01 00:00:00 and through
  2027-03-31 23:59:59, in the time zone of the server
- **AND** its employment start date is 2027-04-01

#### Scenario: No dates

- **WHEN** a visitor submits the form without "When" and an application
  deadline
- **THEN** the job is stored without either

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
