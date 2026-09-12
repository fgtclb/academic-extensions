## Purpose

Makes a filtered or sorted program list addressable by a URL, so a visitor can
bookmark, share and reload it.

## ADDED Requirements

### Requirement: Filter submissions redirect to a GET URL

The program list SHALL answer a POST submission of the filter and sorting
form with a `303 See Other` redirect to the same page and plugin, carrying
the category filters of the program category group and the sorting field and
direction as GET arguments with a valid cache hash, on TYPO3 v13 and v14.

#### Scenario: Visitor filters by degree

- **WHEN** a visitor selects a degree in the program list filter and the form
  is submitted
- **THEN** the response is a 303 redirect whose target carries the degree's
  category uid and a cache hash
- **AND** following the redirect shows only programs with that degree

### Requirement: Only the normalised selection reaches the URL

The redirect target MUST NOT carry category uids outside the program category
group or the form's internal bookkeeping arguments.

#### Scenario: Submission carries a partner category uid

- **WHEN** a submitted program filter contains the uid of a partner category
- **THEN** the redirect target does not contain that uid

### Requirement: A filter URL renders the filtered list

A GET request carrying filter and sorting arguments in the redirect's shape
SHALL render the same program list the corresponding POST rendered before
this change.

#### Scenario: Reload of a filtered program list

- **WHEN** a visitor reloads a filtered program list reached through the
  redirect
- **THEN** the browser does not ask to re-send form data and the same
  filtered list is shown
