## Purpose

Defines the items the type selects of email addresses, phone numbers and
physical addresses of `academic_persons` offer, built from the type lists an
integrator configures in the extension configuration.

## ADDED Requirements

### Requirement: Type selects offer the configured types without empty items

The system SHALL offer, in the type select of an email address, a phone number
and a physical address, the shipped "undefined" item followed by every
configured type. An empty entry of the configured type list, from an emptied
setting, a trailing comma or two commas in a row, MUST NOT add a further item.
A configured type without a label SHALL be labelled with its value, and an
entry with a label but no value SHALL be ignored, without raising a PHP
warning. This applies on TYPO3 v13 and v14.

#### Scenario: Trailing comma in the phone number types

- **WHEN** an integrator configures the phone number types as
  `private=Private,business=Business,` and an editor opens a phone number
- **THEN** the type select offers "undefined", "Private" and "Business", and
  no second item without a label

#### Scenario: Stored type stays selected

- **WHEN** a phone number was saved with the type `business` before the
  integrator added the trailing comma
- **THEN** the type select still shows "Business" as selected

#### Scenario: Type with an empty label

- **WHEN** an integrator configures the email address types as
  `private=,business=Business`
- **THEN** the type select offers "undefined", "private" and "Business"
