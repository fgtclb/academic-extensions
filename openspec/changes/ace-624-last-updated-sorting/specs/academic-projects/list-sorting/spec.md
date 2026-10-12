## Purpose

Defines how the project list content elements order their projects for the
sorting options they offer to editors and visitors.

## ADDED Requirements

### Requirement: "Last updated" orders undated projects by their last save
When a project list is sorted by "last updated", each project SHALL be ordered
by the last updated date an editor entered on its page and, when no date was
entered, by the time its page was last saved. The same rule SHALL apply in
both directions, whether the sorting comes from the content element, the
sorting select or the list URL, on TYPO3 v13 and v14 and on every supported
database system. Projects equal in that ordering SHALL keep the same relative
order on every request.

#### Scenario: Undated project in a descending list
- **WHEN** project A has the entered date 1 January 2026, project B has no
  entered date and was last saved on 1 June 2026, and project C has the
  entered date 1 January 2025
- **AND** the project list is sorted by "last updated", descending
- **THEN** the list renders B, A, C

#### Scenario: Undated project in an ascending list
- **WHEN** the same three projects are sorted by "last updated", ascending
- **THEN** the list renders C, A, B

#### Scenario: An entered date wins over a later save
- **WHEN** a project has the entered date 1 January 2025 and its page was
  saved again yesterday
- **THEN** it is ordered by 1 January 2025

#### Scenario: Sorting combined with the active state filter
- **WHEN** a visitor filters the project list by "Active" and sorts it by
  "last updated"
- **THEN** only active projects are listed, in the "last updated" ordering
