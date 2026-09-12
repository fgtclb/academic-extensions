## Purpose

Lets an editor split a long partner list into pages while visitors keep their
filter and sorting when they move between pages.

## ADDED Requirements

### Requirement: Editors can enable pagination per partner list

The partner list content element SHALL offer an "Enable pagination" switch,
off by default, and a "Results per page" value. With the switch off the list
SHALL render every matching partner, as before this change.

#### Scenario: Pagination is off

- **WHEN** an editor leaves pagination disabled on a list with five partners
- **THEN** all five partners are rendered and no pagination navigation is
  shown

#### Scenario: Pagination is on

- **WHEN** an editor enables pagination with two results per page on a list
  with five partners
- **THEN** the first page shows partners one and two and a pagination
  navigation with three pages

### Requirement: Visitors can move between pages

With pagination enabled, the list SHALL render the requested page of
partners and a navigation to the other pages. Numbered page links SHALL be
rendered when numbered pagination is installed, previous and next links
otherwise.

#### Scenario: Visitor opens page two

- **WHEN** a visitor follows the link to page two of a paginated list with
  two results per page and five partners
- **THEN** exactly partners three and four are shown

#### Scenario: Requested page is beyond the last page

- **WHEN** a visitor requests page nine of a list that has three pages
- **THEN** the last page is shown

### Requirement: Paging keeps filter and sorting

Pagination links MUST carry the active category filter and sorting. A new
filter or sorting submission SHALL start at page one.

#### Scenario: Paging a filtered list

- **WHEN** a visitor filtered the list by a region and follows the link to
  page two
- **THEN** page two of the region-filtered list is shown and the region stays
  selected

#### Scenario: Changing the filter on page two

- **WHEN** a visitor on page two selects another region
- **THEN** page one of the newly filtered list is shown

### Requirement: The partner map is not paginated

The partner map SHALL keep drawing every matching partner, regardless of any
pagination configuration.

#### Scenario: Map with many partners

- **WHEN** the map content element shows a filter that matches thirty partners
- **THEN** all thirty drawable partners appear on the map
