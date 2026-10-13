## MODIFIED Requirements

### Requirement: A selection survives publishing
An address selected in a workspace SHALL still be the selected address after
the workspace is published, and the contact SHALL show it to visitors, on
TYPO3 v13 and v14.

#### Scenario: Selection of a changed address published
- **WHEN** an editor in a workspace changes an e-mail address of a contract,
  selects it on a page contact of that contract, and publishes the workspace
- **THEN** the published page contact selects that e-mail address, and the
  page shows the contact with the changed address

#### Scenario: Selection of a new address published
- **WHEN** an editor in a workspace creates a phone number on a contract,
  selects it on a page contact, and publishes the workspace
- **THEN** the published page contact selects that phone number, and the page
  shows it

#### Scenario: Contract of a selection edited in a workspace
- **WHEN** an editor in a workspace edits a contract whose page contact
  selects one of its e-mail addresses, and publishes the workspace
- **THEN** the published page contact still selects that e-mail address, and
  the page shows the contact with it
