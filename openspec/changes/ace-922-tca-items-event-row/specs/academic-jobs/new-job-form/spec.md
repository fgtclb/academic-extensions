## ADDED Requirements

### Requirement: Select item listeners find an empty record in the job form
When the job form builds the choices of its fields "Job Type" and
"Employment Type", it SHALL hand every listener of the select items event an
empty record, because the job does not exist yet, so that the form renders
when a listener reads the record, instead of the listener raising a warning.
The record of an edited job in the backend SHALL stay what the backend form passes. This
SHALL apply on TYPO3 v12 and v13 alike.

#### Scenario: A listener reads the record in the job form
- **WHEN** a site has a listener of the select items event that reads the record without a fallback and adds a choice for the page of that record, falling back to the page of the form for a record without a page
- **THEN** the job form renders, and both selects offer the choice of the listener for the page of the form

#### Scenario: The job form calls no listener with a record of its own
- **WHEN** the job form builds the choices of its two selects
- **THEN** the record a listener receives holds no field
