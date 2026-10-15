# academic-programs/program-list-sorting Specification

## Purpose
Defines which orderings the program list offers to editors and site visitors,
and that every ordering the list offers is the ordering it renders.

## Requirements

### Requirement: Every offered field and direction combination is honoured

The program list SHALL render programs in the ordering a visitor selects in
the sort field and sort direction selects, for every combination of a field
and a direction those selects offer. The list MUST NOT silently fall back to
another ordering for a combination it offers. This applies to TYPO3 v12 and
v13 alike.

#### Scenario: Visitor reverses the manual order

- **WHEN** a visitor selects the sort field "Page sorting" and the direction
  "Descending"
- **THEN** the list renders the programs in the reverse of their manual order
- **AND** the direction select shows "Descending" as selected

#### Scenario: Visitor picks the ascending manual order

- **WHEN** a visitor selects the sort field "Page sorting" and the direction
  "Ascending"
- **THEN** the list renders the programs in their manual order

#### Scenario: An ordering reached through the shipped route enhancer

- **WHEN** a visitor opens a list URL whose path carries a sort field and a
  sort direction the enhancer maps
- **THEN** the list renders that ordering and the two selects show it as
  selected

#### Scenario: Programs share a sort value

- **WHEN** two programs are equal in the selected ordering
- **THEN** they are rendered in the same relative order on every request

### Requirement: Editors can configure the reversed manual order

The program list content element SHALL offer "Page sorting, reversed" as a
default ordering next to the existing orderings, on both supported TYPO3
versions.

#### Scenario: Editor configures the reversed manual order

- **WHEN** an editor sets the default ordering of a program list content
  element to "Page sorting, reversed"
- **THEN** a visitor who has not changed the sorting sees the programs in the
  reverse of their manual order

#### Scenario: Existing content element keeps its ordering

- **WHEN** a program list content element was saved with any previously
  available ordering
- **THEN** it renders that ordering unchanged

### Requirement: An incomplete default sorting keeps the default ordering

The program list SHALL apply its default sorting only when it is a field and
a direction. Any other value MUST be ignored, and the content element MUST
render, sorted by title, A to Z, instead of failing. A field and a direction
separated by more than one space SHALL be applied. Of a field and a
direction, an unknown one SHALL be ignored on its own while the other one
applies. A sorting chosen by a visitor SHALL be applied regardless of the
default sorting. This applies to TYPO3 v12 and v13 alike.

#### Scenario: An empty default sorting

- **WHEN** a program list content element has an empty default sorting
- **THEN** the list renders its programs sorted by title, A to Z

#### Scenario: A field without a direction

- **WHEN** the default sorting of a program list is set to a field alone, such
  as `title`, in TypoScript for an element that stores no sorting
- **THEN** the list renders its programs sorted by title, A to Z

#### Scenario: More than a field and a direction

- **WHEN** the default sorting is a value of three or more words
- **THEN** the list renders its programs sorted by title, A to Z

#### Scenario: A field and a direction with extra spaces

- **WHEN** the default sorting is "last updated, descending" written with
  spaces around and between its two words
- **THEN** the list renders its programs by last update, newest first

#### Scenario: An unknown field with a known direction

- **WHEN** the default sorting is a field the list does not offer followed
  by a direction, such as `uid desc`
- **THEN** the list keeps sorting by title and applies the direction, Z to A

#### Scenario: A visitor sorts a list with a broken default

- **WHEN** a program list has a default sorting of a field alone and a
  visitor chooses a sort field and a direction
- **THEN** the list renders the ordering the visitor chose

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
