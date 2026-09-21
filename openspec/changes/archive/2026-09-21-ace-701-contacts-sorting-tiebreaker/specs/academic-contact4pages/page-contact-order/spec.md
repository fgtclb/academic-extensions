## ADDED Requirements

### Requirement: Contacts equal in the arrangement keep a stable order

The contacts content element and the page contacts data processor SHALL show
contacts that are equal in the arrangement of a page in the same relative
order on every request, on every supported database. This applies to TYPO3
v12 and v13 alike.

#### Scenario: Two contacts share a position

- **WHEN** two contacts of a page have the same position in the arrangement of
  that page
- **THEN** they are shown in the same relative order on every request
