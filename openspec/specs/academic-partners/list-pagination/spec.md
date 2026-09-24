# academic-partners/list-pagination Specification

## Purpose
Lets an editor split a long partner list into pages while visitors keep their
filter and sorting when they move between pages.

## Requirements

### Requirement: Editors can enable pagination per partner list

The partner list content element SHALL offer an "Enable pagination" switch,
off by default, and a "Results per page" value, ten by default. With the
switch off the list SHALL render every matching partner, as before this
change.

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
partners and a navigation to the other pages, on TYPO3 v13 and v14 alike.
When numbered pagination is installed, the navigation SHALL link at most the
number of page numbers the integrator configured, five by default, and mark
the pages it leaves out; without it, the navigation SHALL link every page. A
list that fits on one page SHALL render no navigation.

#### Scenario: Visitor opens page two

- **WHEN** a visitor follows the link to page two of a paginated list with
  two results per page and five partners
- **THEN** exactly partners three and four are shown

#### Scenario: Requested page is beyond the last page

- **WHEN** a visitor requests page nine of a list that has three pages
- **THEN** the last page is shown

#### Scenario: Numbered pagination limits the page links

- **WHEN** numbered pagination is installed, the integrator configured two
  page links, and a visitor opens the first of three pages
- **THEN** the navigation links pages one and two, marks that more pages
  follow, and still offers the next and the last page

#### Scenario: Every page is linked without numbered pagination

- **WHEN** numbered pagination is not installed and a visitor opens the first
  of three pages
- **THEN** the navigation links all three pages

#### Scenario: One page only

- **WHEN** the filtered list fits on one page
- **THEN** no pagination navigation is shown

### Requirement: Paging keeps filter and sorting

Pagination links MUST carry the active category filter and sorting. A new
filter or sorting submission SHALL start at page one.

#### Scenario: Paging a filtered list

- **WHEN** a visitor filtered the list by a region and follows the link to
  page two
- **THEN** page two of the region-filtered list is shown and the region stays
  selected

#### Scenario: Paging a preselected list

- **WHEN** an editor preselected a region and a visitor follows the link to
  page two of the list without having filtered it
- **THEN** page two of the list filtered by that region is shown

#### Scenario: Changing the filter on page two

- **WHEN** a visitor on page two selects another region
- **THEN** page one of the newly filtered list is shown

### Requirement: The partner map is not paginated

The partner map SHALL keep drawing every matching partner, regardless of any
pagination configuration, and its content element SHALL offer no pagination
fields.

#### Scenario: Map with many partners

- **WHEN** the map content element shows a filter that matches thirty partners
- **THEN** all thirty drawable partners appear on the map

#### Scenario: Map element carrying pagination values

- **WHEN** a map content element carries stored pagination values, as one
  switched from a list to a map does
- **THEN** every drawable partner still appears on the map
