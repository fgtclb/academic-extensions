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
The navigation SHALL be rendered only when there is more than one page. With
numbered pagination installed it SHALL link at most the number of pages the
site configures, five by default. Without it, it SHALL link every page.

#### Scenario: Visitor opens the last page

- **WHEN** a visitor opens page three of a list with five jobs and two results
  per page
- **THEN** exactly the fifth job is shown

#### Scenario: Only one page

- **WHEN** pagination is enabled with ten results per page and the list has
  five jobs
- **THEN** the five jobs are shown and no navigation is rendered

#### Scenario: Numbered pagination limits the page links

- **WHEN** numbered pagination is installed, the site allows two page links
  and the list has three pages
- **THEN** the first page links pages one and two by number, and page three
  only as the last page

#### Scenario: Invalid page number

- **WHEN** a visitor requests page zero or a page beyond the last one
- **THEN** the first or the last page is shown respectively

#### Scenario: A page that is no number

- **WHEN** a request carries a page that is no number, by link or by form
  submission, to a list with or without pagination
- **THEN** the list renders as for page one, and no error is shown

### Requirement: Paging keeps the configured job type

Pagination SHALL page within the job type and the hidden-record setting the
content element is configured with.

#### Scenario: Paging a thesis list

- **WHEN** a list configured for theses is paged
- **THEN** every page shows theses only

#### Scenario: Paging a list that shows hidden jobs

- **WHEN** a list configured to show hidden jobs is paged
- **THEN** the hidden jobs are paged with the others
