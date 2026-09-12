## Purpose

Lets an editor split a long job list into pages that visitors can move
between.

## ADDED Requirements

### Requirement: Editors can enable pagination per job list

The job list content element SHALL offer an "Enable pagination" switch, off
by default, and a "Results per page" value. With the switch off the list
SHALL render every job of the configured type, as before this change.

#### Scenario: Pagination is off

- **WHEN** an editor leaves pagination disabled on a list with five jobs
- **THEN** all five jobs are rendered and no pagination navigation is shown

#### Scenario: Pagination is on

- **WHEN** an editor enables pagination with two results per page on a list
  with five jobs
- **THEN** the first page shows the first two jobs in the list's order and a
  navigation with three pages

### Requirement: Visitors can move between pages

With pagination enabled, the list SHALL render the requested page of jobs.
The navigation SHALL be rendered only when there is more than one page;
numbered page links SHALL be rendered when numbered pagination is installed,
previous and next links otherwise.

#### Scenario: Visitor opens the last page

- **WHEN** a visitor opens page three of a list with five jobs and two results
  per page
- **THEN** exactly the fifth job is shown

#### Scenario: Only one page

- **WHEN** pagination is enabled with ten results per page and the list has
  four jobs
- **THEN** the four jobs are shown and no navigation is rendered

#### Scenario: Invalid page number

- **WHEN** a visitor requests page zero or a page beyond the last one
- **THEN** the first or the last page is shown respectively

### Requirement: Paging keeps the configured job type

Pagination SHALL page within the job type and the hidden-record setting the
content element is configured with.

#### Scenario: Paging a thesis list

- **WHEN** a list configured for theses is paged
- **THEN** every page shows theses only
