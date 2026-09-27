## ADDED Requirements

### Requirement: Office hours render as sanitized HTML in the list items

When office hours are among the contract fields a list, list and detail, card,
selected profiles or selected contracts plugin shows, the system SHALL render
the paragraphs, lists and links of office hours written in the frontend editor
as markup, and SHALL keep the line breaks of plain text office hours. The
system MUST NOT render event handler attributes or script elements of the
stored value as markup. This applies on TYPO3 v13 and v14.

#### Scenario: Office hours from the frontend editor

- **WHEN** a contract's office hours are stored as
  `<p>Tuesday 10:00 to 12:00</p>` and the card shows office hours
- **THEN** the card shows the paragraph "Tuesday 10:00 to 12:00", not the tags
  as text

#### Scenario: Plain text office hours

- **WHEN** a contract's office hours are two lines of plain text
- **THEN** the card shows them on two lines

#### Scenario: Unsafe markup

- **WHEN** a contract's office hours contain a script element and an event
  handler attribute
- **THEN** the page contains neither as markup, and the script is shown as
  escaped text

#### Scenario: Default fields

- **WHEN** a card plugin has no fields to show selected
- **THEN** it shows office hours among its default fields, rendered the same
  way
