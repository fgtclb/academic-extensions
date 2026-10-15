## ADDED Requirements

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
