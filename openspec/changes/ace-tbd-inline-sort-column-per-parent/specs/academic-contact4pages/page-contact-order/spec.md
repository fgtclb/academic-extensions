## ADDED Requirements

### Requirement: A page keeps the contact order arranged on it

The contacts of a page SHALL render in the order an editor arranged on that
page. Saving any other record, a contract or a contacts role that lists the
same contacts included, MUST NOT change that order. This applies to TYPO3 v13
and v14 alike.

#### Scenario: Saving a contract

- **WHEN** an editor arranges the contacts of a page, and then saves a contract
  whose contacts tab lists contacts of several pages in a different order
- **THEN** the page still renders its contacts in the order arranged on the
  page

#### Scenario: Saving a contacts role

- **WHEN** an editor saves a contacts role that lists contacts of several pages
- **THEN** every page still renders its contacts in the order arranged on it

#### Scenario: Updating an existing installation

- **WHEN** an installation updates and runs the upgrade wizards
- **THEN** pages, contracts and contacts roles show their contacts in the
  order they showed before the update
