# academic-projects/list-filter-urls Specification

## Purpose
Makes a filtered or sorted project list addressable by a URL, so a visitor can
bookmark, share and reload it, including the active state selection.

## Requirements

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

### Requirement: A hidden part of the form keeps the preset of the content element

Where the content element hides the sorting, both project lists SHALL sort by
its default sorting, and where it hides the state select, they SHALL show the
projects of its active state, whatever a submission or a URL carries for that
part. Where it hides the category filter, they SHALL show only projects of all
its default categories, and categories a submission or a URL carries SHALL
narrow the list further and SHALL NOT replace a default category. This applies
on TYPO3 v13 and v14. The redirect target of a submission SHALL carry the
resulting values. A part the content element shows keeps the selection of the
visitor, as before.

#### Scenario: Visitor sorts a list whose category filter is hidden

- **WHEN** the content element hides the category filter, shows the sorting
  and presets the project category "Energy Research"
- **AND** a visitor submits the sorting title, descending
- **THEN** the redirect target carries the project category "Energy Research"
  and the sorting title, descending
- **AND** the list behind it shows only projects of that category, by title,
  descending

#### Scenario: Visitor filters a list whose sorting is hidden

- **WHEN** the content element hides the sorting, shows the category filter
  and presets the sorting title, descending
- **AND** a visitor submits a project category
- **THEN** the redirect target carries that category and the sorting title,
  descending
- **AND** the list behind it shows the projects of that category by title,
  descending

#### Scenario: Visitor filters a list whose state select is hidden

- **WHEN** the content element hides the state select, shows the category
  filter and presets the active state "active"
- **AND** a visitor submits a project category
- **THEN** the redirect target carries that category and the active state
  "active"
- **AND** the list behind it shows only active projects of that category

#### Scenario: Visitor sorts a list whose state select is hidden

- **WHEN** the content element hides the state select, shows the sorting and
  presets the active state "active"
- **AND** a visitor submits the sorting title, descending
- **THEN** the redirect target carries the active state "active"
- **AND** the list behind it shows only active projects

#### Scenario: A submission carries a sorting for a list whose sorting is hidden

- **WHEN** the content element hides the sorting and presets the sorting
  title, descending
- **AND** a submission or a URL carries the sorting title, ascending
- **THEN** the list is sorted by title, descending

#### Scenario: A URL carries a category for a list whose category filter is hidden

- **WHEN** the content element hides the category filter and presets the
  project category "Energy Research"
- **AND** a visitor opens the list with a URL that carries the cooperation
  "Industry"
- **THEN** the list shows only the energy research projects with industry

#### Scenario: A URL carries a category for a hidden filter without defaults

- **WHEN** the content element hides the category filter and presets no
  category
- **AND** a visitor opens the list with a URL that carries the project
  category "Quantum Physics"
- **THEN** the list shows only projects of that category

#### Scenario: A URL carries another state for a list whose state select is hidden

- **WHEN** the content element hides the state select and presets the active
  state "active"
- **AND** a visitor opens the list with a URL that carries the active state
  "completed"
- **THEN** the list shows only active projects

### Requirement: A filter keeps the defaults of the category types it does not offer

A submission of the form of a project list SHALL replace the preset categories
of the category types the form offers, and SHALL keep the preset categories of
every other type, on TYPO3 v13 and v14. Choosing "all" in a select SHALL clear
the preset categories of its type. A URL that carries a category filter SHALL
remain the whole selection.

#### Scenario: The site offers the cooperation only

- **WHEN** the site offers only the cooperation as a filter and the content
  element presets the project category "Energy Research"
- **AND** a visitor submits the cooperation "Industry"
- **THEN** the redirect target carries "Energy Research" and "Industry"
- **AND** the list behind it shows only the energy research projects with
  industry

#### Scenario: The form offers the type of the default

- **WHEN** the form offers the project category and the cooperation and the
  content element presets the project category "Energy Research"
- **AND** a visitor submits the project category "all" and the cooperation
  "Industry"
- **THEN** the list behind the redirect shows every project with industry

### Requirement: A value given as a list is ignored

Both project list plugins SHALL ignore a sorting, a sorting field, a sorting
direction or an active state of a request that is a list of values where the
form sends one value, like an unknown value, in a link and in a submission, on
TYPO3 v13 and v14. Where the element shows that part of the form, the list
SHALL render with the default of the extension in its place, title, A to Z,
and all projects, and the redirect of a submission SHALL carry that default.
Where the element hides that part, its preset SHALL apply, as for any value of
a request. The other values of the request SHALL apply as they do beside an
unknown value.

#### Scenario: A link carries the sorting field as a list

- **WHEN** a visitor opens a project list with a link that carries the sorting
  field as a list of one value
- **THEN** the list shows all projects sorted by title, A to Z

#### Scenario: A submission carries the active state as a list

- **WHEN** a submission of the form of a project list carries the active state
  as a list of one value
- **THEN** the response is a 303 redirect whose target carries the active state
  "all" and the sorting title, ascending
