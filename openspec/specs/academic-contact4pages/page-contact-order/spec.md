# academic-contact4pages/page-contact-order Specification

## Purpose
TBD - created by archiving change ace-699-inline-sort-column-per-parent. Update Purpose after archive.

## Requirements

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

#### Scenario: A contract and a contacts role keep their own arrangements

- **WHEN** an editor rearranges the contacts listed in a contract, and
  rearranges the contacts listed in a contacts role differently
- **THEN** each of the two forms shows its own order the next time it is
  opened, and every later save of one of those contacts leaves both alone

#### Scenario: A contact joins a contract or a contacts role from somewhere else

- **WHEN** a contact is given a contract or a contacts role in its own form on
  a page, or is copied or localized
- **THEN** it appears at the end of that record's list rather than in front of
  the contacts the editor arranged

#### Scenario: A contact changes its contract or its contacts role

- **WHEN** an editor gives a contact a different contract or a different
  contacts role
- **THEN** it appears at the end of the new record's list and no longer in the
  old one, and the other of the two relations keeps its order

#### Scenario: Updating an existing installation

- **WHEN** an installation updates and runs the upgrade wizards
- **THEN** pages, contracts and contacts roles show their contacts in the
  order they showed before the update
