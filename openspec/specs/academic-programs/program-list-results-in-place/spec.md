# academic-programs/program-list-results-in-place Specification

## Purpose
Defines how the program list responds to a change of its filter or sorting:
in place, with an address bar that follows, an announcement for screen reader
users, and a plain form where JavaScript is not available.

## Requirements

### Requirement: A change of the filter or sorting updates the list in place
The program list SHALL update its results and its filter form without
reloading the page when a visitor changes a category filter or the sorting,
on TYPO3 v13 and v14. The results and the form SHALL be those a reload of the
filter URL shows, including the options it offers disabled.

#### Scenario: Visitor filters by degree
- **WHEN** a visitor selects the degree "Master" in the filter of a program
  list
- **THEN** the list shows only the Master programs without a page reload
- **AND** the degree filter shows "Master" as selected
- **AND** the degree filter keeps the focus

#### Scenario: A request fails
- **WHEN** the list cannot be requested in place after a change
- **THEN** the page reloads with the filtered list

#### Scenario: Two lists on one page
- **WHEN** a page carries two program list elements and a visitor changes the
  filter of one of them
- **THEN** both lists show the selection, as a reload of the filter URL shows
  them, also when one of them hides its filter and its sorting
- **AND** only the changed list announces its result

### Requirement: The address bar follows the list
After an update the address bar SHALL show the filter URL of the selection,
and the browser history SHALL step back to the previous selection.

#### Scenario: Reloading after an update
- **WHEN** a visitor filters the list and reloads the page
- **THEN** the page shows the same filtered list

#### Scenario: Going back
- **WHEN** a visitor filters the list twice and uses the back button of the
  browser
- **THEN** the list shows the first selection again, with its filter

### Requirement: Screen reader users hear the result
After an update the program list SHALL announce the number of programs found
through a polite status message.

#### Scenario: Result count announced
- **WHEN** a filter change leaves three programs
- **THEN** a status message states that three programs were found

### Requirement: The form works without JavaScript
Without JavaScript the filter form SHALL offer a submit button, and
submitting it SHALL open the filter URL of the selection. With JavaScript the
button SHALL be hidden.

#### Scenario: JavaScript is off
- **WHEN** a visitor without JavaScript selects a degree and presses the
  submit button
- **THEN** the page reloads with the filtered list

### Requirement: Overrides keep working
A project override of the list templates that lacks the parts the in-place
update needs SHALL keep updating the list on a change, with a page reload
where it cannot be done in place.

#### Scenario: List template overridden
- **WHEN** a project overrides the list template with a copy of the one of
  version 3.0 before this change and a visitor changes a filter
- **THEN** the page reloads with the filtered list

#### Scenario: Filter partials overridden with their inline handlers
- **WHEN** a project overrides the filter partials with copies that keep the
  inline handlers and a visitor changes a filter
- **THEN** the page reloads with the filtered list

#### Scenario: One filter partial overridden with its inline handlers
- **WHEN** a project overrides the sorting partial with a copy that keeps the
  inline handlers and a visitor changes a category filter
- **THEN** the list shows the filtered programs without a page reload

#### Scenario: List template and form partial overridden
- **WHEN** a project overrides the list template and the partial of the form
  with copies of the ones of version 3.0 before this change and a visitor
  changes a filter
- **THEN** the page reloads with the filtered list

#### Scenario: Form partial overridden
- **WHEN** a project overrides the partial of the form with a copy of the one
  of version 3.0 before this change and a visitor changes a filter
- **THEN** the list shows the filtered programs without a page reload
