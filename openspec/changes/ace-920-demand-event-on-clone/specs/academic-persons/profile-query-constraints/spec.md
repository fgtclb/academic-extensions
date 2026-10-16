## ADDED Requirements

### Requirement: A demand listener changes the questions of the list, not its request

When an installed extension changes the demand a persons list is queried
with, the system SHALL apply the change to the profiles the list queries. The
order of a selection by hand, the page the pagination shows, the active
letter and the demand the list template receives SHALL follow the demand of
the request. The letters the letter navigation offers and the next day the
list changes through its contracts SHALL be asked for with the demand of the
request, which the extension changes again for each of these questions on a
copy of its own. The same MUST hold whether the extension changes the demand
it receives or hands back another one, and every call for one rendering MUST
receive the demand of the request rather than an earlier change of the
extension. This applies on TYPO3 v13 and v14.

#### Scenario: Listener selects profiles for a list without a selection

- **WHEN** a list shows profiles 1, 2 and 3 without a selection by hand, and
  an installed extension changes the demand it receives to select profiles 3
  and 1
- **THEN** only profiles 1 and 3 are shown, profile 1 first, because the
  request selects nothing by hand
- **AND** the list template receives a demand without a selection

#### Scenario: Listener narrows a selection by hand

- **WHEN** an editor selected profiles 3, 1 and 2 in that order, and an
  installed extension changes the selection of the demand it receives to
  profiles 2 and 3
- **THEN** only profiles 3 and 2 are shown, in the order of the editor's
  selection

#### Scenario: Listener clears a selection by hand

- **WHEN** an editor selected profiles 3 and 1 in that order, and an
  installed extension clears the selection of the demand it receives
- **THEN** only profiles 3 and 1 are shown, in the order of the editor's
  selection

#### Scenario: Listener changes the page

- **WHEN** a list paginates three profiles at one per page, a visitor opens
  the first page, and an installed extension changes the page of the demand
  it receives to the second page
- **THEN** the first page is shown
- **AND** the list template receives the demand of the first page

#### Scenario: Every call of one rendering receives the demand of the request

- **WHEN** a list shows only contracts valid today and the letter navigation,
  and an installed extension changes the page of every demand it receives
- **THEN** the extension is asked three times, for the profiles, for the next
  contract validity change and for the letters
- **AND** each time it receives the page of the request
