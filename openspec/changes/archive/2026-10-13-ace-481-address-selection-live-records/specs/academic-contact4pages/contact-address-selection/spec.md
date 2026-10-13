## Purpose

Defines what the e-mail address, phone number and postal address selects of a
page contact offer an editor, what they store, and how a selection behaves
when records are published, copied or translated.

## ADDED Requirements

### Requirement: The selects offer the addresses of the contract as the editor's workspace shows them
Each of the three address selects of a page contact SHALL offer "display all",
"display none" and every address of that kind of the contact's contract in the
default language or in all languages, each address once, as the workspace the
editor works in shows it, on TYPO3 v13 and v14. An address created in another
workspace, or deleted in the editor's workspace, SHALL NOT be offered.

#### Scenario: Address changed in the editor's workspace
- **WHEN** an editor in a workspace opens a page contact whose contract has an
  e-mail address that was changed in that workspace
- **THEN** the e-mail select offers that address once, with the changed value
  as its label

#### Scenario: Address changed in another workspace
- **WHEN** an editor in the live workspace opens a page contact whose contract
  has an e-mail address that was changed in a workspace
- **THEN** the e-mail select offers that address once, with its live value as
  its label

#### Scenario: Address created in another workspace
- **WHEN** an editor opens a page contact whose contract has a phone number
  that was created in a workspace other than the editor's
- **THEN** the phone number select does not offer it

#### Scenario: Editor without access to the folder of the person records
- **WHEN** an editor who may not see the page holding the person records opens
  a page contact
- **THEN** the selects offer the addresses of the contract as they do for an
  administrator

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
- **THEN** the published page contact still selects that e-mail address

### Requirement: A copied contract keeps its contacts' selections on its own addresses
When a contract is copied together with its addresses and its page contacts,
directly or as part of a copied profile, each copied page contact SHALL select
the copy of the address the original selected, on TYPO3 v13 and v14. "Display
all" and "display none" SHALL be copied unchanged. A translation of the
contract SHALL keep its page contacts on the default language addresses.

#### Scenario: Contract copied
- **WHEN** an editor copies a contract with an e-mail address and a page
  contact that selects it
- **THEN** the copied page contact selects the copied e-mail address, and the
  original keeps the original one

#### Scenario: Profile copied
- **WHEN** an editor copies a profile whose contract has a page contact
  selecting a postal address
- **THEN** the page contact of the copied contract selects the copied postal
  address

#### Scenario: Contract translated
- **WHEN** an editor translates a contract with its addresses and a page
  contact that selects one of them
- **THEN** the translated page contact selects the same default language
  address as the page contact it translates

### Requirement: A selected address is known as referenced
A selected address SHALL be listed as referenced by the page contact that
selects it, in the backend information on that address, on TYPO3 v13 and v14.

#### Scenario: Editor looks up the references of an address
- **WHEN** an editor opens the record information of an e-mail address that a
  page contact selects
- **THEN** the page contact is listed among the records referencing it

### Requirement: Display all and display none stay what they are
Saving, copying or translating a page contact SHALL keep "display all" and
"display none" as they were stored, and both choices SHALL remain selectable
when the contract has no address of that kind, on TYPO3 v13 and v14 alike.

#### Scenario: Display none saved
- **WHEN** an editor saves a page contact with "display none" for its phone
  number
- **THEN** the page contact still shows no phone number to visitors

#### Scenario: Contract without phone numbers
- **WHEN** an editor opens a page contact whose contract has no phone number
- **THEN** the phone number select is shown with "display all" and "display
  none", on TYPO3 v14 as on v13

### Requirement: A selection that matches no offered address is kept and marked
A stored selection that matches none of the offered addresses SHALL be shown
to the editor as an invalid value and SHALL stay stored when the page contact
is saved without changing that select, on TYPO3 v13 and v14.

#### Scenario: Selected address belongs to another contract
- **WHEN** an editor opens a page contact whose e-mail selection points at an
  address of another contract, and saves it without changing that select
- **THEN** the form showed the selection as an invalid value, and the stored
  selection is unchanged after the save
