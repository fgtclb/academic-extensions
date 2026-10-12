## ADDED Requirements

### Requirement: "Last updated" orders undated programs by their last save
When a program list is sorted by "last updated", each program SHALL be ordered
by the last updated date an editor entered on its page and, when no date was
entered, by the time its page was last saved. The same rule SHALL apply in
both directions, whether the sorting comes from the content element, the
sorting select or the list URL, on TYPO3 v13 and v14 and on every supported
database system. Programs equal in that ordering SHALL keep the same relative
order on every request.

#### Scenario: Undated program in a descending list
- **WHEN** program A has the entered date 1 January 2026, program B has no
  entered date and was last saved on 1 June 2026, and program C has the
  entered date 1 January 2025
- **AND** the list is sorted by "last updated", descending
- **THEN** the list renders B, A, C

#### Scenario: Undated program in an ascending list
- **WHEN** the same three programs are sorted by "last updated", ascending
- **THEN** the list renders C, A, B

#### Scenario: An entered date wins over a later save
- **WHEN** a program has the entered date 1 January 2025 and its page was
  saved again yesterday
- **THEN** it is ordered by 1 January 2025

#### Scenario: Two undated programs saved at the same time
- **WHEN** two programs have no entered date and the same save time
- **THEN** they are rendered in the same relative order on every request
