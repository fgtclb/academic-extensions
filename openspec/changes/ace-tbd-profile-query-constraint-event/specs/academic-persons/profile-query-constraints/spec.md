## Purpose

Defines how an installed extension narrows the profiles and contracts the
`academic_persons` plugins show, without replacing the extension's own query
logic.

## ADDED Requirements

### Requirement: Extensions can narrow the profiles of the profile plugins

The system SHALL let an event listener of an installed extension add
conditions to the profile query of the list, list-and-detail, card and
selected-profiles plugins. A profile that does not satisfy an added condition
MUST NOT be shown. This applies on TYPO3 v13 and v14.

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
be shown.

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

### Requirement: Listeners know which plugin asked

The system SHALL give a listener the plugin, the content element's settings
and the current request of the query it narrows. For a lookup of selected uids
it SHALL state that no list demand applies.

#### Scenario: Listener acts only for one plugin

- **WHEN** a listener adds its condition only for the list plugin
- **THEN** the card plugin on the same page shows its profiles unrestricted
