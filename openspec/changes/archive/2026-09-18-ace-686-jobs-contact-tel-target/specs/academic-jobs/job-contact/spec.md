## Purpose

Defines what the contact block of a job detail view shows a site visitor, and
how the phone number of that block is offered as a link that a device can act
on.

## ADDED Requirements

### Requirement: The contact phone link carries a dialable target

The job detail view MUST render the link target of the contact phone number
without the spaces of the stored number, and SHALL keep the stored spelling as
the visible link text. This applies on TYPO3 v12 and v13 alike.

#### Scenario: Stored number contains spaces

- **WHEN** a visitor opens the detail view of a job whose contact phone number
  is stored as `+49 89 1234`
- **THEN** the phone link points at `tel:+49891234`
- **AND** the visible link text reads `+49 89 1234`

#### Scenario: Stored number contains no spaces

- **WHEN** the contact phone number of a job is stored as `+49891234`
- **THEN** the phone link points at `tel:+49891234`
- **AND** the visible link text reads `+49891234`

#### Scenario: Contact without a phone number

- **WHEN** a visitor opens the detail view of a job whose contact carries a name
  and an e-mail address but no phone number
- **THEN** the contact block renders
- **AND** it shows no phone row and no phone link
