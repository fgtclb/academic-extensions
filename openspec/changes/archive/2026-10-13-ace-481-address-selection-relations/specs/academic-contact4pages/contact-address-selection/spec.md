## Purpose

Defines what the e-mail address, phone number and postal address selects of a
page contact offer an editor, what they store, and how a selection behaves
when records are copied or translated.

## ADDED Requirements

### Requirement: The selects offer the addresses of the contract
Each of the three address selects of a page contact SHALL offer "display all",
"display none" and every address of that kind of the contact's contract in the
default language or in all languages, each address once, on TYPO3 v12 and
v13, whether or not the editor may see the page holding the person records.

#### Scenario: Contract with addresses
- **WHEN** an editor opens a page contact whose contract has two e-mail
  addresses
- **THEN** the e-mail select offers "display all", "display none" and both
  addresses, each once

#### Scenario: Editor without access to the folder of the person records
- **WHEN** an editor who may not see the page holding the person records opens
  a page contact
- **THEN** the selects offer the addresses of the contract as they do for an
  administrator

### Requirement: A copied contract keeps its contacts' selections on its own addresses
When a contract is copied together with its addresses and its page contacts,
directly or as part of a copied profile, each copied page contact SHALL select
the copy of the address the original selected, on TYPO3 v12 and v13. "Display
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
selects it, in the backend information on that address, on TYPO3 v12 and v13.

#### Scenario: Editor looks up the references of an address
- **WHEN** an editor opens the record information of an e-mail address that a
  page contact selects
- **THEN** the page contact is listed among the records referencing it

### Requirement: Display all and display none stay what they are
Saving, copying or translating a page contact SHALL keep "display all" and
"display none" as they were stored, on TYPO3 v12 and v13.

#### Scenario: Display none saved
- **WHEN** an editor saves a page contact with "display none" for its phone
  number
- **THEN** the page contact still shows no phone number to visitors

### Requirement: A selection that matches no offered address is kept and marked
A stored selection that matches none of the offered addresses SHALL be shown
to the editor as an invalid value and SHALL stay stored when the page contact
is saved without changing that select, on TYPO3 v12 and v13.

#### Scenario: Selected address belongs to another contract
- **WHEN** an editor opens a page contact whose e-mail selection points at an
  address of another contract, and saves it without changing that select
- **THEN** the form showed the selection as an invalid value, and the stored
  selection is unchanged after the save
