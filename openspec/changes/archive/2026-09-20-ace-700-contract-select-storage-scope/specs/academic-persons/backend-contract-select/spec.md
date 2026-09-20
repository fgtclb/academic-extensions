## Purpose

Defines which contracts the backend contract selects offer to an editor, and
how an integrator restricts them to the storage folders of a site.

## ADDED Requirements

### Requirement: Integrators can restrict the offered contracts by page

The backend contract selects SHALL offer only contracts stored on the pages an
integrator lists for the field in page TSconfig, including their subpages to
the configured depth. Without that setting they MUST offer every contract, as
before. This applies to TYPO3 v13 and v14 alike.

#### Scenario: Restricted to one site's folder

- **WHEN** page TSconfig of a site lists that site's persons folder for the
  contract field of a contact record
- **THEN** an editor of a page in that site is offered only contracts stored
  in that folder

#### Scenario: No setting

- **WHEN** no page TSconfig setting exists for the field
- **THEN** the select offers every contract of the installation, as before

#### Scenario: Subfolders

- **WHEN** the setting lists a folder and a depth of one
- **THEN** contracts stored in direct subfolders of that folder are offered too

### Requirement: A referenced contract is never dropped

A contract the edited record already references SHALL stay selectable, even
when the restriction would exclude it, so that saving the record keeps the
relation.

This covers the page restriction only. A contract that the select does not
offer for another reason - because it is hidden, or deleted - is not offered
before this change either, and this change does not alter that.

#### Scenario: Existing relation outside the restriction

- **WHEN** a contact record references a contract stored outside the listed
  pages and an editor opens and saves it
- **THEN** the contract is shown as selected and the record still references
  it after saving
