## ADDED Requirements

### Requirement: Contacts keep a stable order

The contacts content element and the page contacts data processor SHALL show
a page's contacts in the order the editor arranged them, and contacts that are
equal in that arrangement MUST appear in the same relative order on every
request, on every supported database. This applies to TYPO3 v13 and v14
alike.

#### Scenario: Two contacts share a position

- **WHEN** two contacts of a page have the same position in the editor's
  arrangement
- **THEN** they are shown in the same relative order on every request

#### Scenario: Arranged contacts

- **WHEN** an editor arranged the contacts of a page
- **THEN** they are shown in that order, as before
