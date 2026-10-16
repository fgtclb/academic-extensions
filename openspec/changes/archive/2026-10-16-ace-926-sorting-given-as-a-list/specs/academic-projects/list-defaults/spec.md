## Purpose

Defines the sorting and the active state both project lists show before the
visitor chose one, as the settings of the content element preset them.

## ADDED Requirements

### Requirement: A sorting setting given as a list keeps the default sorting

Both project lists SHALL treat a sorting setting that is a list of values like
an unknown value, on TYPO3 v12 and v13: the list SHALL sort by title, A to Z,
and the element SHALL render its projects instead of an error. TypoScript
makes such a list of a setting with a child, also next to a value of its own,
and the sorting an element stores does not replace it.

#### Scenario: TypoScript gives the sorting a child

- **WHEN** the TypoScript of the site sets
  `plugin.tx_academicprojects.settings.sorting.field = title`
- **THEN** a project list element that stores no sorting of its own renders
  its projects sorted by title, A to Z
- **AND** a project list element that stores the sorting title, Z to A,
  renders its projects sorted by title, A to Z as well

#### Scenario: TypoScript gives the sorting a value and a child

- **WHEN** the TypoScript of the site sets
  `plugin.tx_academicprojects.settings.sorting = title desc` and
  `plugin.tx_academicprojects.settings.sorting.field = title`
- **THEN** a project list element that stores no sorting of its own renders
  its projects sorted by title, A to Z

#### Scenario: A preset of the select still applies

- **WHEN** the TypoScript of the site gives the sorting no child
- **AND** a project list element stores the sorting title, Z to A
- **THEN** the element renders its projects sorted by title, Z to A

### Requirement: An active state setting given as a list shows every state

Both project lists SHALL treat an active state setting that is a list of
values like an unknown value, on TYPO3 v12 and v13: the list SHALL show the
projects of every state, without a PHP warning. TypoScript makes such a list
of a setting with a child, also next to a value of its own, and the state an
element stores does not replace it.

#### Scenario: TypoScript gives the active state a child

- **WHEN** the TypoScript of the site gives `settings.activeState` a child
- **THEN** a project list element that stores no state of its own renders the
  projects of every state
- **AND** a project list element that stores the state "Active" renders the
  ended projects as well
