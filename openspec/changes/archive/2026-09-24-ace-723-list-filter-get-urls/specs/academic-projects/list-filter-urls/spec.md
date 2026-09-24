## Purpose

Makes a filtered or sorted project list addressable by a URL, so a visitor can
bookmark, share and reload it, including the active state selection.

## ADDED Requirements

### Requirement: Filter submissions redirect to a GET URL

Both project list plugins SHALL answer a POST submission of the filter and
sorting form with a `303 See Other` redirect to the same page and plugin,
carrying the category filters of the project category group, the sorting field
and direction, and the active state as GET arguments with a valid cache hash,
on TYPO3 v13 and v14.

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

#### Scenario: Submission carries a partner category uid

- **WHEN** a submitted project filter contains the uid of a partner category
- **THEN** the redirect target does not contain that uid

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

### Requirement: A submission replaces the selection of the URL it is sent to

The redirect SHALL carry the selection of the submitted form only. A form that
is submitted to the URL of a filtered project list SHALL replace the selection
that URL carries, and a submission without a selection of this list - another
plugin's form on the same page - SHALL NOT be redirected, on TYPO3 v13 and
v14.

#### Scenario: Visitor clears a project category on a filtered list whose form posts to the current URL

- **WHEN** a visitor is on the project list filtered by the project category
  "Energy Research", and a form that submits to the current URL is sent with
  the project category set to "all"
- **THEN** the redirect target carries no category filter

#### Scenario: Another plugin's form is submitted on a filtered list

- **WHEN** a form of another plugin is submitted to the URL of the filtered
  project list
- **THEN** the response is the page itself, with the project list still
  filtered

### Requirement: Filter URLs share one page cache entry

The cache hash of a redirect target SHALL NOT depend on the selection, so
every filter URL of the project list is served from one page cache entry, on
TYPO3 v13 and v14.

#### Scenario: Two project categories are submitted one after the other

- **WHEN** a visitor submits the project category "Energy Research" and then
  the project category "Quantum Physics"
- **THEN** both redirect targets carry the same cache hash

#### Scenario: Two filter URLs are opened one after the other

- **WHEN** a visitor opens one filter URL of the list and then another one,
  and the second is served from the page the first one cached
- **THEN** each shows the list of its own selection

### Requirement: Clearing the filter overrides the editor's preselection

The redirect for a submission without any selected category SHALL carry no
category filter argument but SHALL still carry the sorting and the active
state, so the list behind it is unfiltered even when the content element
preselects categories, on TYPO3 v13 and v14.

#### Scenario: Visitor clears a preselected project category

- **WHEN** the content element preselects the project category "Energy
  Research" and a visitor sets the category select to "all" and submits the
  form
- **THEN** the redirect target carries the sorting and the active state and
  no category filter
- **AND** the list behind it shows projects of all categories

#### Scenario: First visit applies the preselection

- **WHEN** a visitor opens the page of that content element without any
  filter argument
- **THEN** the list shows only projects of the category "Energy Research"
