## Purpose

Defines how the partner list and the partner map order their partners for the
sorting options they offer to editors and visitors.

## ADDED Requirements

### Requirement: "Last updated" orders undated partners by their last save
When the partner list or the partner map is sorted by "last updated", each
partner SHALL be ordered by the last updated date an editor entered on its
page and, when no date was entered, by the time its page was last saved. The
same rule SHALL apply in both directions, whether the sorting comes from the
content element, the sorting select or the list URL, on TYPO3 v13 and v14 and
on every supported database system. Partners equal in that ordering SHALL keep
the same relative order on every request.

#### Scenario: Undated partner in a descending list
- **WHEN** partner A has the entered date 1 January 2026, partner B has no
  entered date and was last saved on 1 June 2026, and partner C has the
  entered date 1 January 2025
- **AND** the partner list is sorted by "last updated", descending
- **THEN** the list renders B, A, C

#### Scenario: Undated partner in an ascending list
- **WHEN** the same three partners are sorted by "last updated", ascending
- **THEN** the list renders C, A, B

#### Scenario: An entered date wins over a later save
- **WHEN** a partner has the entered date 1 January 2025 and its page was
  saved again yesterday
- **THEN** it is ordered by 1 January 2025

#### Scenario: Two undated partners saved at the same time
- **WHEN** two partners have no entered date and the same save time
- **THEN** they are rendered in the same relative order on every request

### Requirement: A paginated list sorted by "last updated" stays complete
A partner list that is paginated and sorted by "last updated" SHALL show every
partner of the result exactly once across its pages, and SHALL report the same
number of results as the same list sorted by any other option, on TYPO3 v13
and v14.

#### Scenario: Second page of a sorted list
- **WHEN** a partner list shows two partners per page, holds five partners, and
  is sorted by "last updated", descending
- **THEN** the second page shows the third and fourth partner of that ordering,
  and the pagination counts three pages
