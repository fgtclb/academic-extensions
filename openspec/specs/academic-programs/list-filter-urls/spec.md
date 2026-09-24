# academic-programs/list-filter-urls Specification

## Purpose
Makes a filtered or sorted program list addressable by a URL, so a visitor can
bookmark, share and reload it.

## Requirements

### Requirement: Filter submissions redirect to a GET URL

The program list SHALL answer a POST submission of the filter and sorting form
with a `303 See Other` redirect to the same page and plugin, carrying the
category filters of the program category group and the sorting field and
direction as GET arguments, on TYPO3 v13 and v14. Without a route enhancer the
target carries a valid cache hash.

#### Scenario: Visitor filters by degree

- **WHEN** a visitor selects a degree in the program list filter and the form
  is submitted
- **THEN** the response is a 303 redirect whose target carries the degree's
  category uid and, without a route enhancer, a cache hash
- **AND** following the redirect shows only programs with that degree

### Requirement: Only the normalised selection reaches the URL

The redirect target MUST NOT carry category uids outside the program category
group or the form's internal bookkeeping arguments.

#### Scenario: Submission carries a partner category uid

- **WHEN** a submitted program filter contains the uid of a partner category
- **THEN** the redirect target does not contain that uid

### Requirement: A filter URL renders the filtered list

A GET request carrying filter and sorting arguments in the redirect's shape
SHALL render the same program list the corresponding POST rendered before this
change.

#### Scenario: Reload of a filtered program list

- **WHEN** a visitor reloads a filtered program list reached through the
  redirect
- **THEN** the browser does not ask to re-send form data and the same filtered
  list is shown

### Requirement: The shipped route enhancer keeps the sorting in the path

With the route enhancer the extension ships for its list imported into the
site configuration, the redirect SHALL carry the sorting as path segments,
including the default sorting, and a category filter as a query argument, on
TYPO3 v13 and v14. The redirect target MUST NOT be the bare page URL, which
applies the content element's presets.

#### Scenario: Visitor clears a preselected degree on a site with the enhancer

- **WHEN** the content element preselects the degree "Bachelor of Science",
  the site imports the shipped route enhancer, and a visitor sets the degree
  select to "all" and submits the form with the default sorting
- **THEN** the redirect target is the list page followed by `/title/asc`
- **AND** the list behind it shows programs of all degrees

#### Scenario: Visitor sorts on a site with the enhancer

- **WHEN** a visitor on such a site sorts the list by last update, descending
- **THEN** the redirect target is the list page followed by
  `/last-updated/desc`

### Requirement: A submission replaces the selection of the URL it is sent to

The redirect SHALL carry the selection of the submitted form only. A form that
is submitted to the URL of a filtered program list SHALL replace the selection
that URL carries, and a submission without a selection of this list - another
plugin's form on the same page - SHALL NOT be redirected, on TYPO3 v13 and
v14.

#### Scenario: Visitor clears a degree on a filtered list whose form posts to the current URL

- **WHEN** a visitor is on the program list filtered by the degree "Master of
  Science", and a form that submits to the current URL is sent with the degree
  set to "all"
- **THEN** the redirect target carries no category filter

#### Scenario: Another plugin's form is submitted on a filtered list

- **WHEN** a form of another plugin is submitted to the URL of the filtered
  program list
- **THEN** the response is the page itself, with the program list still
  filtered

### Requirement: Filter URLs share one page cache entry

The cache hash of a redirect target SHALL NOT depend on the selection, so
every filter URL of the program list is served from one page cache entry, on
TYPO3 v13 and v14. Behind the shipped route enhancer the sorting is part of
the path, and every sorting path is a page cache entry of its own.

#### Scenario: Two degrees are submitted one after the other

- **WHEN** a visitor submits the degree "Master of Science" and then the
  degree "Bachelor of Science"
- **THEN** both redirect targets carry the same cache hash

#### Scenario: Two filter URLs are opened one after the other

- **WHEN** a visitor opens one filter URL of the list and then another one,
  and the second is served from the page the first one cached
- **THEN** each shows the list of its own selection

### Requirement: Clearing the filter overrides the editor's preselection

The redirect for a submission without any selected category SHALL carry no
category filter argument but SHALL still carry the sorting, so the list
behind it is unfiltered even when the content element preselects categories,
on TYPO3 v13 and v14.

#### Scenario: Visitor clears a preselected degree

- **WHEN** the content element preselects the degree "Bachelor of Science"
  and a visitor sets the degree select to "all" and submits the form
- **THEN** the redirect target carries the sorting and no category filter
- **AND** the list behind it shows programs of all degrees

#### Scenario: First visit applies the preselection

- **WHEN** a visitor opens the page of that content element without any
  filter argument
- **THEN** the list shows only programs with the degree "Bachelor of Science"
