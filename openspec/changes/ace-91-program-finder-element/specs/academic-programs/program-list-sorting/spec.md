## ADDED Requirements

### Requirement: A filter without a sorting keeps the configured sorting
The program list SHALL render a request whose filter carries no sorting in the
default ordering configured for the content element, not in the default
ordering of the extension, on TYPO3 v13 and v14. A filter that carries a
sorting is rendered in that sorting, as before.

#### Scenario: Filtering a list whose sorting select is hidden
- **WHEN** a program list is configured to sort by title, descending, and to
  hide its sorting select
- **AND** a visitor submits its category filter
- **THEN** the filtered list is sorted by title, descending, and its URL
  carries that sorting

#### Scenario: Opening the list from the program finder
- **WHEN** a program finder submits a selection to a program list configured
  to sort by title, descending
- **THEN** the list shows the matching programs by title, descending
