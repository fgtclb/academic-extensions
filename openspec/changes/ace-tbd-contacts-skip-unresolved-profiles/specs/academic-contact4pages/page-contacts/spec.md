## Purpose

Defines which contacts of a page a visitor sees, through the contacts content
element and through the page contacts data processor.

## ADDED Requirements

### Requirement: Contacts without a visible contract or profile are not shown

The contacts content element and the page contacts data processor SHALL leave
out every contact whose contract is hidden, missing or deleted, and every
contact whose profile is hidden, outside its start and end time, restricted
to a frontend user group the visitor does not belong to, missing or deleted.
This applies to TYPO3 v13 and v14 alike.

#### Scenario: Profile of a contact is hidden

- **WHEN** a page has two contacts and the profile of one of them is hidden
- **THEN** the contacts element renders only the contact with the visible
  profile

#### Scenario: Contract of a contact is hidden

- **WHEN** a page has two contacts and the contract of one of them is hidden
- **THEN** the contacts element renders only the contact with the visible
  contract

#### Scenario: Profile has expired

- **WHEN** the end time of the profile of a contact lies in the past
- **THEN** that contact is not rendered

#### Scenario: Page contacts data processor

- **WHEN** a page template receives the contacts of its page through the page
  contacts data processor and one contact points at a hidden profile
- **THEN** the processed contacts and roles do not contain that contact

### Requirement: Roles are built from the shown contacts only

A role heading SHALL only be rendered when at least one of the shown contacts
carries that role, and the group of contacts without a role SHALL only
contain shown contacts.

#### Scenario: All contacts of a role are hidden

- **WHEN** the only contact with the role "Dean" points at a hidden profile
- **THEN** no "Dean" heading is rendered

### Requirement: Showing hidden records does not reveal hidden profiles

With the option to show hidden records enabled, the contacts element SHALL
show hidden contact records, and SHALL still leave out contacts whose
contract or profile is not visible.

#### Scenario: Hidden contact row with a visible profile

- **WHEN** the option is enabled and a hidden contact points at a visible
  profile
- **THEN** the contact is rendered

#### Scenario: Visible contact row with a hidden profile

- **WHEN** the option is enabled and a visible contact points at a hidden
  profile
- **THEN** the contact is not rendered
