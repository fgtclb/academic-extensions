## Purpose

Defines how the program list responds to a change of its filter or sorting:
in place, with an address bar that follows, an announcement for screen reader
users, and a plain form where JavaScript is not available.

## ADDED Requirements

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

#### Scenario: Two lists on one page
- **WHEN** a page carries two program list elements and a visitor changes the
  filter of one of them
- **THEN** only that list is updated

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
A project override of the list templates that lacks the results region SHALL
keep submitting the form with a page reload.

#### Scenario: Results partial overridden
- **WHEN** a project overrides the results partial with a copy of the one of
  version 3.0 before this change and a visitor changes a filter
- **THEN** the page reloads with the filtered list
