## Purpose

Defines how the frontend job form offers a stored date for editing, and how a
job's dates are shown to a visitor.

## ADDED Requirements

### Requirement: The job form offers a stored date for editing
When a job that already carries a date is opened in the job form, the date
control SHALL be prefilled with that date. Submitting the form unchanged SHALL
leave the date unchanged.

#### Scenario: An existing employment start date is edited
- **WHEN** a job whose employment start date is 1 October 2026 is opened in the
  job form
- **THEN** the date control offers 1 October 2026

#### Scenario: The form is submitted unchanged
- **WHEN** a job carrying dates is opened and submitted without touching them
- **THEN** the stored dates are unchanged

### Requirement: A job date is shown in the site's notation
A job date shown to a visitor SHALL be formatted for the locale of the matched
site language rather than in a fixed notation.

#### Scenario: A job is listed on two site languages
- **WHEN** a job with the employment start date 1 October 2026 is listed on the
  German and on the US English site language
- **THEN** the German listing shows `01.10.2026` and the US English listing
  shows `Oct 1, 2026`
