# academic-persons/profile-query-constraints Specification

## Purpose
Defines how an installed extension narrows the profiles and contracts the
`academic_persons` plugins show, without replacing the extension's own query
logic.

## Requirements

### Requirement: Extensions can narrow the profiles of the profile plugins

The system SHALL let an event listener of an installed extension add
conditions to the profile query of the list, list-and-detail, card and
selected-profiles plugins. A profile that does not satisfy an added condition
MUST NOT be shown. This applies on TYPO3 v12 and v13.

#### Scenario: Listener restricts the list to one last name

- **WHEN** an installed extension adds the condition "last name is
  Achterberg" and a visitor opens a list plugin of three profiles
- **THEN** only the profile with the last name Achterberg is shown

#### Scenario: Selected profiles are narrowed too

- **WHEN** the same listener is active and an editor selected all three
  profiles in a selected-profiles plugin
- **THEN** only the profile with the last name Achterberg is shown

#### Scenario: No listener installed

- **WHEN** no extension listens
- **THEN** every plugin shows the same profiles as before

### Requirement: Extensions can narrow the selected contracts

The system SHALL let an event listener add conditions to the contract query of
the selected-contracts plugin. A contract that does not satisfy them MUST NOT
be shown. This applies on TYPO3 v12 and v13.

#### Scenario: Listener excludes a contract

- **WHEN** an installed extension adds a condition that one of two selected
  contracts does not satisfy
- **THEN** the plugin renders only the other contract

### Requirement: Added conditions narrow, never widen, and pagination follows

The system MUST combine added conditions with the plugin's own conditions
(storage folders, editor filters, letter filter, hidden records, language).
They SHALL apply before pagination, so that the number of pages and the
profiles on each page reflect them. The order of the result SHALL remain the
one the plugin defines.

#### Scenario: Paginated list with a restricting listener

- **WHEN** a list plugin paginates two profiles per page, a listener leaves two
  of five profiles, and a visitor opens the list
- **THEN** one page is shown, containing both remaining profiles in the order
  the plugin's sorting defines

#### Scenario: Listener combined with an editor filter

- **WHEN** an editor restricts a list to one organisational unit and a
  listener restricts it to one last name
- **THEN** only profiles matching both are shown

#### Scenario: Paginated manual selection

- **WHEN** an editor selects five profiles with pagination at two per page and
  a listener leaves two of them
- **THEN** one page is shown, with both remaining profiles in the order of the
  editor's selection

### Requirement: The extension keeps the order, whatever a listener asks for

The order of a plugin's result SHALL be the one the plugin defines, even when
an installed extension asks the query for another.

#### Scenario: Listener asks for a different order

- **WHEN** an installed extension asks a list plugin's query to be ordered by
  last name descending
- **THEN** the list is shown in the order the plugin defines, and the request
  is ignored without a message

### Requirement: A condition still narrows when it is expressed as a replacement

A condition an installed extension expresses by replacing what the plugin asked
the query for, rather than by adding to it, SHALL narrow the result together
with the plugin's own conditions rather than take their place.

#### Scenario: Listener replaces the plugin's condition instead of adding one

- **WHEN** an editor restricts a list to one organisational unit and an
  installed extension expresses "last name is Achterberg" by replacing the
  query's condition rather than adding one
- **THEN** only profiles satisfying both are shown, and the editor's
  restriction is not lost

### Requirement: A listener narrows every plugin of the extension alike

On this version line the system does NOT tell a listener which plugin asked,
which settings the content element carries or which request is being rendered.
A listener SHALL therefore narrow every profile and contract query of the
plugins alike.

#### Scenario: Two plugins on one page

- **WHEN** a list plugin and a card plugin stand on the same page and an
  installed extension adds a condition
- **THEN** both are narrowed by it

### Requirement: A demand listener changes the query of the list, not its request

When an installed extension changes the demand a persons list is queried
with, the system SHALL apply the change to the profiles the list queries, and
the change SHALL decide nothing else. The order of a selection by hand, the
page the pagination shows, whether a letter switches the pagination off, the
demand an extension receives when it is told about the listed profiles after
the query and the demand the list template receives SHALL follow the demand
of the request. The list SHALL ask the extension once per rendering, for
its profiles, with the demand of the request. The same MUST hold whether the
extension changes the demand it receives or hands back another one. This
applies on TYPO3 v12 and v13.

#### Scenario: Listener selects profiles for a list without a selection

- **WHEN** a list shows profiles 1, 2 and 3 without a selection by hand, and
  an installed extension changes the demand it receives to select profiles 3
  and 1
- **THEN** only profiles 1 and 3 are shown, profile 1 first, because the
  request selects nothing by hand
- **AND** an extension told about the listed profiles after the query, and
  the list template, receive a demand without a selection

#### Scenario: Listener narrows a selection by hand

- **WHEN** an editor selected profiles 3, 1 and 2 in that order, and an
  installed extension changes the selection of the demand it receives to
  profiles 2 and 3
- **THEN** only profiles 3 and 2 are shown, in the order of the editor's
  selection
- **AND** an extension told about the listed profiles after the query, and
  the list template, receive the editor's selection

#### Scenario: Listener clears a selection by hand

- **WHEN** an editor selected profiles 3 and 1 in that order, and an
  installed extension clears the selection of the demand it receives
- **THEN** only profiles 3 and 1 are shown, in the order of the editor's
  selection
- **AND** an extension told about the listed profiles after the query, and
  the list template, receive the editor's selection

#### Scenario: Listener changes the page

- **WHEN** a list paginates three profiles at one per page, a visitor opens
  the first page, and an installed extension changes the page of the demand
  it receives to the second page
- **THEN** the first page is shown
- **AND** an extension told about the listed profiles after the query, and
  the list template, receive the demand of the first page
- **AND** the extension is asked once, with the page of the request

#### Scenario: Listener sets a letter

- **WHEN** a list paginates three profiles at one per page, and an installed
  extension sets the letter A on the demand it receives
- **THEN** only the profile whose last name starts with A is shown
- **AND** the pagination stays on, with one page
- **AND** an extension told about the listed profiles after the query, and
  the list template, receive a demand without a letter
