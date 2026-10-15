# academic-persons/profile-list-pagination Specification

## Purpose

Defines how the list and list-and-detail plugins of `academic_persons` split
the profiles they show into pages, for a query result and for a manual
selection alike.

## Requirements

### Requirement: A paginated manual selection keeps the editor's order

When an editor selects profiles manually and enables pagination, the system
SHALL render the selected profiles the listeners of the profile query event
leave, in the order of the selection, split into pages of the configured size.
This applies on TYPO3 v13 and v14.

#### Scenario: First page of a selection

- **WHEN** a list plugin selects the profiles 3, 1 and 2 in that order with
  pagination enabled and two results per page, and a visitor opens the list
- **THEN** the first page shows profile 3 followed by profile 1
- **AND** profile 2 is not shown

#### Scenario: Second page of a selection

- **WHEN** the visitor follows the link to page 2 of the same list
- **THEN** the page shows only profile 2

#### Scenario: A listener excludes one of the selected profiles

- **WHEN** an installed extension excludes profile 1 of that selection
- **THEN** the first page shows profile 3 followed by profile 2
- **AND** there is no second page

### Requirement: Every selected profile appears on exactly one page

The system MUST produce the same page contents for the same selection on every
supported database system, so that no selected profile is shown on two pages or
on none. A profile that a listener of the profile query event excluded is not
shown at all, and the requirement holds for the profiles that remain.

#### Scenario: Pages on PostgreSQL

- **WHEN** the paginated selection is rendered on PostgreSQL
- **THEN** the union of all pages contains each selected profile exactly once
- **AND** the pages match the pages rendered on SQLite

#### Scenario: A listener excludes one of the selected profiles

- **WHEN** an installed extension excludes one profile of a paginated selection
- **THEN** the excluded profile is on none of the pages
- **AND** every other selected profile is still on exactly one page, on every
  supported database system

### Requirement: Lists without a selection paginate as before

The system SHALL paginate a list without a manual selection in the order
derived from the plugin's sorting and grouping settings, unchanged by this
requirement set.

#### Scenario: List sorted by last name

- **WHEN** a list plugin without a selection sorts by last name with pagination
  enabled
- **THEN** the pages show the profiles in last name order, as before

### Requirement: An empty pagination field of the element takes the site value

When the person list or the person list and details element has pagination
switched on and its field "Results per Page" or "Number of Links" was saved
empty or as `0`, the system SHALL use the site setting of the same name, as if
the element did not carry the field. A value the editor entered SHALL win over
the site setting. This applies on TYPO3 v13 and v14.

#### Scenario: Empty results per page in a list

- **WHEN** a person list element with pagination switched on has an empty
  "Results per Page", the site sets two results per page, and a visitor opens
  the list of twelve profiles
- **THEN** the list renders without an error
- **AND** it shows two profiles on the first of six pages

#### Scenario: Empty results per page in a list and details element

- **WHEN** a person list and details element with pagination switched on has
  an empty "Results per Page", and the site sets two results per page
- **THEN** the list shows two profiles on the first page

#### Scenario: Empty results per page in a manual selection

- **WHEN** a person list element selects profiles by hand with pagination
  switched on and an empty "Results per Page", and the site sets two results
  per page
- **THEN** the list shows the first two profiles of the selection on the first
  page

#### Scenario: Results per page saved as zero

- **WHEN** a person list element with pagination switched on has "Results per
  Page" saved as `0`, and the site sets two results per page
- **THEN** the list shows two profiles on the first page

#### Scenario: Empty number of links

- **WHEN** a person list element with pagination switched on has an empty
  "Number of Links", the site sets three links, and the list has more pages
  than that
- **THEN** the page navigation links three page numbers

#### Scenario: The editor's value wins

- **WHEN** a person list element sets three results per page and the site sets
  two
- **THEN** the list shows three profiles on the first page

### Requirement: A number of results per page below one falls back to ten

The system SHALL paginate a person list with ten profiles per page when the
number of results per page that applies is below 1 or empty, whether it comes
from the element, the site setting or TypoScript, and SHALL NOT end the request
with an error. A number of page links below 1 SHALL fall back to five links.
This applies on TYPO3 v13 and v14.

#### Scenario: The site sets zero

- **WHEN** the site sets the results per page to `0` and a person list element
  with pagination switched on leaves the field empty, and the list has twelve
  profiles
- **THEN** the list renders without an error
- **AND** it shows ten profiles on the first of two pages

#### Scenario: The site sets an empty value

- **WHEN** the site sets an empty number of results per page and a person list
  element with pagination switched on leaves the field empty, and the list has
  twelve profiles
- **THEN** the list shows ten profiles on the first of two pages

#### Scenario: The element holds a negative number

- **WHEN** a person list element with pagination switched on has "Results per
  Page" saved as `-3`, and the list has twelve profiles
- **THEN** the list shows ten profiles on the first of two pages

#### Scenario: A manual selection with a number below one

- **WHEN** a person list element selects twelve profiles by hand with
  pagination switched on, the field is empty, and the site sets `0`
- **THEN** the list shows the first ten profiles of the selection on the first
  page

#### Scenario: The site sets zero links

- **WHEN** the site sets the number of links to `0` and the list has twelve
  pages
- **THEN** the page navigation links five page numbers

### Requirement: The shipped default is ten results per page

The system SHALL ship ten results per page as the default of the site setting
and of the TypoScript constant of the person list pagination, with and without
a site set. A person list element with pagination switched on that sets no
number of its own, on a site that sets none either, SHALL show ten profiles
per page. This applies on TYPO3 v13 and v14.

#### Scenario: Nothing sets the number

- **WHEN** a person list element with pagination switched on leaves "Results
  per Page" empty on a site that keeps the shipped settings, and the list has
  twelve profiles
- **THEN** the list shows ten profiles on the first of two pages
