## Purpose

Keeps the category type an editor selects unambiguous, by refusing type
identifiers that two groups declare.

## ADDED Requirements

### Requirement: A type identifier belongs to exactly one group
Loading the category types SHALL fail with an error naming the identifier and
both declaring extensions when, after every package is read, the same type
identifier exists in two different groups. This SHALL apply on TYPO3 v13 and
v14.

#### Scenario: Two groups declare the same identifier
- **WHEN** one extension declares the type `department` in the group
  `programs` and another declares `department` in the group `projects`
- **THEN** loading the category types fails with an error that names
  `department` and both extension keys

#### Scenario: Override within the same group
- **WHEN** a later extension overrides the type `department` of the group
  `programs` with `useExisting: true`
- **THEN** loading succeeds and the overridden type is used

#### Scenario: Collision resolved by a removal
- **WHEN** one of two colliding types is removed by a later extension with
  `remove: true`
- **THEN** loading succeeds with the remaining type
