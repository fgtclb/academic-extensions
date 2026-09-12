## Purpose

Makes a filtered or sorted project list addressable by a URL, so a visitor can
bookmark, share and reload it, including the active state selection.

## ADDED Requirements

### Requirement: Filter submissions redirect to a GET URL

Both project list plugins SHALL answer a POST submission of the filter and
sorting form with a `303 See Other` redirect to the same page and plugin,
carrying the category filters of the project category group, the sorting
field and direction, and the active state as GET arguments with a valid cache
hash, on TYPO3 v13 and v14.

#### Scenario: Visitor filters completed projects of one category

- **WHEN** a visitor selects a project category and the active state
  "completed" and the form is submitted
- **THEN** the response is a 303 redirect whose target carries the category
  uid, the active state and a cache hash
- **AND** following the redirect shows only completed projects of that
  category

### Requirement: Only the normalised selection reaches the URL

The redirect target MUST NOT carry category uids outside the project category
group, active state values the project list does not offer, or the form's
internal bookkeeping arguments.

#### Scenario: Submission carries an unknown active state

- **WHEN** the form is submitted with an active state value the list does not
  offer
- **THEN** the redirect target carries the default active state instead

### Requirement: A filter URL renders the filtered list

A GET request carrying filter, sorting and active state arguments in the
redirect's shape SHALL render the same project list the corresponding POST
rendered before this change.

#### Scenario: Bookmarked project filter is opened

- **WHEN** a visitor opens a bookmarked URL with a category filter and an
  active state
- **THEN** the list shows the matching projects and both selections are
  preselected in the form
