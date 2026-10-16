# academic-partners/list-sorting Specification

## Purpose
Defines the ordering the partner list and the partner map start with, before
a visitor chose one, and what happens to a default sorting that is not a
field and a direction.

## Requirements

### Requirement: An incomplete default sorting keeps the default ordering

The partner list and the partner map SHALL apply their default sorting only
when it is a field and a direction. Any other value MUST be ignored, and the
content element MUST render, sorted by title, A to Z, instead of failing. A
field and a direction separated by more than one space SHALL be applied. Of
a field and a direction, an unknown one SHALL be ignored on its own while
the other one applies. A sorting chosen by a visitor SHALL be applied
regardless of the default sorting. This applies to TYPO3 v12 and v13 alike.

#### Scenario: An empty default sorting

- **WHEN** a partner list content element has an empty default sorting
- **THEN** the list renders its partners sorted by title, A to Z

#### Scenario: A field without a direction

- **WHEN** the default sorting of a partner list or map is set to a field
  alone, such as `title`, in TypoScript for an element that stores no sorting
- **THEN** the element renders its partners sorted by title, A to Z

#### Scenario: More than a field and a direction

- **WHEN** the default sorting is a value of three or more words
- **THEN** the element renders its partners sorted by title, A to Z

#### Scenario: A field and a direction with extra spaces

- **WHEN** the default sorting is "last updated, descending" written with
  spaces around and between its two words
- **THEN** the element renders its partners by last update, newest first

#### Scenario: An unknown field with a known direction

- **WHEN** the default sorting is a field the element does not offer followed
  by a direction, such as `uid desc`
- **THEN** the element keeps sorting by title and applies the direction, Z to A

#### Scenario: A value of the default sorting select

- **WHEN** an editor chose one of the orderings the "Default sorting" select
  offers
- **THEN** a visitor who has not changed the sorting sees that ordering, as
  before

#### Scenario: A visitor sorts a list with a broken default

- **WHEN** a partner list has a default sorting of a field alone and a visitor
  chooses a sort field and a direction
- **THEN** the list renders the ordering the visitor chose

### Requirement: A hidden sorting keeps the default sorting

Where the content element hides the sorting, the partner list and the partner
map SHALL sort by its default sorting for every request, whatever a
submission of the visible category filter carries for the sorting. Where the
content element shows the sorting, the sorting of the visitor SHALL apply, as
before. This applies to TYPO3 v12 and v13 alike.

#### Scenario: Visitor filters a list whose sorting is hidden

- **WHEN** the content element hides the sorting, shows the category filter
  and presets the sorting title, descending
- **AND** a visitor submits the region "Europe"
- **THEN** the list shows the partners of the region "Europe" by title,
  descending

#### Scenario: A submission carries a sorting for a list whose sorting is hidden

- **WHEN** the content element hides the sorting and presets the sorting
  title, descending
- **AND** a submission of its filter carries the sorting title, ascending
- **THEN** the list is sorted by title, descending

### Requirement: A sorting value given as a list is ignored

The partner list and the partner map SHALL ignore a sorting, a sorting field
or a sorting direction of a request that is a list of values where the form
sends one value, like an unknown value, in a submission and in a link, on
TYPO3 v12 and v13. Where the element shows the sorting selects, the element
SHALL render with the default of the extension in its place, title, A to Z.
Where the element hides them, its preset sorting SHALL apply, as for any
sorting of a request. The other values of the request SHALL apply as they do
beside an unknown value.

#### Scenario: A submission carries the sorting field as a list

- **WHEN** a submission of the form of a partner list carries the sorting
  field as a list of one value
- **THEN** the list shows its partners sorted by title, A to Z

#### Scenario: A link carries the sorting direction as a list

- **WHEN** a visitor opens a partner list with a link that carries the sorting
  direction as a list of one value
- **THEN** the list shows its partners sorted by title, A to Z

#### Scenario: A submission to the map carries the sorting as a list

- **WHEN** a submission of the form of a partner map carries the sorting as a
  list of one value
- **THEN** the map renders its partners instead of an error

#### Scenario: The direction beside a sorting field given as a list

- **WHEN** a submission carries the sorting field as a list of one value and
  the direction descending
- **THEN** the list shows its partners sorted by title, Z to A
