## Purpose

Defines the ordering the partner list and the partner map start with, before
a visitor chose one, and what happens to a default sorting that is not a
field and a direction.

## ADDED Requirements

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
