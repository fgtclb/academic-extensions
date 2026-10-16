# academic-partners/list-filter-urls Specification

## Purpose
Makes a filtered or sorted partner list or map addressable by a URL, so a
visitor can bookmark, share and reload it.

## Requirements

### Requirement: Filter submissions redirect to a GET URL

The partner list and map SHALL answer a POST submission of the filter and
sorting form with a `303 See Other` redirect to the same page and plugin. The
redirect target SHALL carry the submitted selection as GET arguments and a
valid cache hash, on TYPO3 v13 and v14.

#### Scenario: Visitor selects a region

- **WHEN** a visitor selects a region in the partner list filter and the form
  is submitted
- **THEN** the response is a 303 redirect whose target carries the selected
  region's category uid and a cache hash
- **AND** following the redirect shows the partner list restricted to that
  region

#### Scenario: Visitor changes the sorting

- **WHEN** a visitor submits the partner list form with a different sorting
- **THEN** the redirect target carries the sorting field and direction
- **AND** the list behind it is sorted accordingly

### Requirement: Only the normalised selection reaches the URL

Of the list's selection, the redirect target MUST carry only category filters
of the partner category group, the sorting field and the sorting direction,
next to the plugin's action and controller. Category uids that do not exist or
belong to another category group, and the form's internal bookkeeping
arguments, MUST NOT appear in it.

#### Scenario: Submission carries a foreign category uid

- **WHEN** a submitted filter contains a category uid that is not a partner
  category
- **THEN** the redirect target does not contain that uid

#### Scenario: Submission carries form bookkeeping

- **WHEN** the filter form is submitted with its referrer and trusted property
  fields
- **THEN** none of those fields appear in the redirect target

### Requirement: A filter URL renders the filtered list

A GET request carrying filter and sorting arguments in the redirect's shape
SHALL render the same partner list or map the corresponding POST rendered
before this change.

#### Scenario: Shared filter URL is opened

- **WHEN** a visitor opens a URL with a region filter they received from
  someone else
- **THEN** the partner list shows only partners of that region and the region
  is preselected in the filter

### Requirement: Clearing the filter overrides the editor's preselection

The redirect for a submission without any selected category SHALL carry no
category filter argument but SHALL still carry the sorting, so the list behind
it is unfiltered even when the content element preselects categories.

#### Scenario: Visitor clears a preselected region

- **WHEN** the content element preselects the region "Europe" and a visitor
  sets the region select to "all" and submits the form
- **THEN** the redirect target carries the sorting and no category filter
- **AND** the list behind it shows partners of all regions

#### Scenario: First visit applies the preselection

- **WHEN** a visitor opens the page of that content element without any filter
  argument
- **THEN** the list shows only partners of the region "Europe"

### Requirement: A submission replaces the selection of the URL it is sent to

The redirect SHALL carry the selection of the submitted form only. A form that
is submitted to the URL of a filtered partner list SHALL replace the selection
that URL carries, and a submission without a selection of this list - another
plugin's form on the same page - SHALL NOT be redirected, on TYPO3 v13 and
v14.

#### Scenario: Visitor clears a region on a filtered list whose form posts to the current URL

- **WHEN** a visitor is on the partner list filtered by the region "Americas",
  and a form that submits to the current URL is sent with the region set to
  "all"
- **THEN** the redirect target carries no category filter

#### Scenario: Another plugin's form is submitted on a filtered list

- **WHEN** a form of another plugin is submitted to the URL of the filtered
  partner list
- **THEN** the response is the page itself, with the partner list still
  filtered

### Requirement: Filter URLs share one page cache entry

The cache hash of a redirect target SHALL NOT depend on the selection, so
every filter URL of the partner list is served from one page cache entry, on
TYPO3 v13 and v14.

#### Scenario: Two regions are submitted one after the other

- **WHEN** a visitor submits the region "Americas" and then the region
  "Europe"
- **THEN** both redirect targets carry the same cache hash

#### Scenario: Two filter URLs are opened one after the other

- **WHEN** a visitor opens one filter URL of the list and then another one,
  and the second is served from the page the first one cached
- **THEN** each shows the list of its own selection

### Requirement: A hidden part of the form keeps the preset of the content element

Where the content element hides the sorting, the partner list and map SHALL
sort by its default sorting, whatever a submission or a URL carries for it.
Where it hides the category filter, they SHALL show only partners of all its
default categories, and categories a submission or a URL carries SHALL narrow
the list further and SHALL NOT replace a default category. This applies on
TYPO3 v13 and v14. The redirect target of a submission SHALL carry the
resulting categories and sorting. A part the content element shows keeps the
selection of the visitor, as before.

#### Scenario: Visitor sorts a list whose category filter is hidden

- **WHEN** the content element hides the category filter, shows the sorting
  and presets the region "Europe"
- **AND** a visitor submits the sorting title, descending
- **THEN** the redirect target carries the region "Europe" and the sorting
  title, descending
- **AND** the list behind it shows only partners of the region "Europe", by
  title, descending

#### Scenario: Visitor filters a list whose sorting is hidden

- **WHEN** the content element hides the sorting, shows the category filter
  and presets the sorting title, descending
- **AND** a visitor submits the region "Europe"
- **THEN** the redirect target carries the region "Europe" and the sorting
  title, descending
- **AND** the list behind it shows the partners of the region "Europe" by
  title, descending

#### Scenario: A URL carries a category for a list whose category filter is hidden

- **WHEN** the content element hides the category filter and presets the
  region "Europe"
- **AND** a visitor opens the list with a URL that carries the partner type
  "University"
- **THEN** the list shows only the European universities

#### Scenario: A URL carries a category for a hidden filter without defaults

- **WHEN** the content element hides the category filter and presets no
  category
- **AND** a visitor opens the list with a URL that carries the region
  "Americas"
- **THEN** the list shows only partners of the region "Americas"

#### Scenario: A submission carries a sorting for a list whose sorting is hidden

- **WHEN** the content element hides the sorting and presets the sorting
  title, descending
- **AND** a submission or a URL carries the sorting title, ascending
- **THEN** the list is sorted by title, descending

#### Scenario: Visitor sorts a map whose category filter is hidden

- **WHEN** the content element of a partner map hides the category filter,
  shows the sorting and presets the region "Europe"
- **AND** a visitor submits a sorting
- **THEN** the map behind the redirect shows only partners of the region
  "Europe"

### Requirement: A filter keeps the defaults of the category types it does not offer

A submission of the partner list or map form SHALL replace the default
categories of the category types the form offers, and SHALL keep the default
categories of every other type, on TYPO3 v13 and v14. Choosing "all" in a
select SHALL clear the default categories of its type. A URL that carries a
category filter SHALL remain the whole selection.

#### Scenario: The site offers the partner type only

- **WHEN** the site offers only the partner type as a filter and the content
  element presets the region "Europe"
- **AND** a visitor submits the partner type "University"
- **THEN** the redirect target carries "Europe" and "University"
- **AND** the list behind it shows only the European universities

#### Scenario: The form offers the type of the default

- **WHEN** the form offers the region and the partner type and the content
  element presets the region "Europe"
- **AND** a visitor submits the region "all" and the partner type "University"
- **THEN** the list behind the redirect shows the universities of every region

### Requirement: A sorting value given as a list is ignored

The partner list SHALL ignore a sorting, a sorting field or a sorting
direction of a request that is a list of values where the form sends one
value, like an unknown value, in a link and in a submission, on TYPO3 v13 and
v14. Where the element shows the sorting selects, the list SHALL render with
the default of the extension in its place, title, A to Z, and the redirect of
a submission SHALL carry that default. Where the element hides them, its
preset sorting SHALL apply, as for any sorting of a request. The other values
of the request SHALL apply as they do beside an unknown value.

#### Scenario: A link carries the sorting field as a list

- **WHEN** a visitor opens a partner list with a link that carries the sorting
  field as a list of one value
- **THEN** the list shows its partners sorted by title, A to Z

#### Scenario: A submission carries the sorting direction as a list

- **WHEN** a submission of the form of a partner list carries the sorting
  direction as a list of one value
- **THEN** the response is a 303 redirect whose target carries the sorting
  title, ascending
