## ADDED Requirements

### Requirement: A hidden sorting keeps the default sorting

Where the content element hides the sorting, the program list SHALL sort by
its default sorting for every request, whatever a submission of the visible
category filter carries for the sorting. Where the content element shows the
sorting, the sorting of the visitor SHALL apply, as before. This applies to
TYPO3 v12 and v13 alike.

#### Scenario: Visitor filters a list whose sorting is hidden

- **WHEN** the content element hides the sorting, shows the category filter
  and presets the sorting title, descending
- **AND** a visitor submits the degree "Master of Science"
- **THEN** the list shows the programs with that degree by title, descending

#### Scenario: A submission carries a sorting for a list whose sorting is hidden

- **WHEN** the content element hides the sorting and presets the sorting
  title, descending
- **AND** a submission of its filter carries the sorting title, ascending
- **THEN** the list is sorted by title, descending
