# academic-projects/list-defaults Specification

## Purpose
Defines the sorting and the active state both project lists show before the
visitor chose one, as the settings of the content element preset them.

## Requirements

### Requirement: A preset given as a list keeps the default of the extension

Both project lists SHALL treat a sorting setting or an active state setting
that is a list of values like an unknown value, on TYPO3 v13 and v14: a list
with a sorting setting given as a list SHALL sort by title, A to Z, and a list
with an active state setting given as a list SHALL show the projects of every
state. The element SHALL render its projects instead of an error. TypoScript
makes such a list of a setting with a child, also next to a value of the
setting itself, and the value an element stores does not replace it.

#### Scenario: TypoScript gives the sorting a child

- **WHEN** the TypoScript of the site sets
  `plugin.tx_academicprojects.settings.sorting.field = title`
- **THEN** a project list element that stores no sorting of its own renders
  its projects sorted by title, A to Z
- **AND** a project list element that stores the sorting title, Z to A,
  renders its projects sorted by title, A to Z as well

#### Scenario: TypoScript gives the active state a child

- **WHEN** the TypoScript of the site gives `settings.activeState` a child
- **THEN** a project list element renders the projects of every state,
  without a PHP warning
- **AND** a project list element that stores the active state "active"
  renders the ended projects as well

#### Scenario: A preset of the select still applies

- **WHEN** the TypoScript of the site gives the sorting no child
- **AND** a project list element stores the sorting title, Z to A
- **THEN** the element renders its projects sorted by title, Z to A
