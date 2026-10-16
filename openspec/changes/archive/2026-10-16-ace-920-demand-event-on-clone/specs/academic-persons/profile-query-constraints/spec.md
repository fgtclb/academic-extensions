## ADDED Requirements

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
