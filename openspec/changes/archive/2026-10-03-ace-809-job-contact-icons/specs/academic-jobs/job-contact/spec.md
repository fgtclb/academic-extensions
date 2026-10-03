## ADDED Requirements

### Requirement: The contact rows show the icons the extension ships

The job detail view MUST show the phone icon shipped with the extension in
front of the contact phone number, and the e-mail icon shipped with the
extension in front of the contact e-mail address, on an installation that
registers no icons of its own. It MUST NOT show TYPO3's "icon not found"
placeholder in the contact block. The two icons SHALL be shown at the size of
the property icons of the same detail view. An integrator SHALL be able to
replace either icon by registering their own file under the identifier the
extension uses for it, `academic_jobs-contactPhone` or
`academic_jobs-contactEmail`. This applies on TYPO3 v13 and v14 alike.

#### Scenario: Job with a contact phone number and e-mail address

- **WHEN** a visitor opens the detail view of a job with the contact phone
  number `+49 89 1234` and the contact e-mail address `ada@example.org` on an
  installation without icons of its own
- **THEN** the phone row shows the shipped phone icon
- **AND** the e-mail row shows the shipped e-mail icon
- **AND** the contact block shows no "icon not found" placeholder

#### Scenario: Job with a contact e-mail address only

- **WHEN** a job carries a contact e-mail address and no contact phone number
- **THEN** the contact block shows the shipped e-mail icon
- **AND** it shows no phone icon

#### Scenario: Same size as the property icons

- **WHEN** a visitor opens the detail view of a job with a contact phone
  number, a contact e-mail address and a work location
- **THEN** the phone and e-mail icons are shown at the size of the work
  location icon

#### Scenario: A site package replaces the phone icon

- **WHEN** a site package that depends on the extension registers its own file
  under `academic_jobs-contactPhone`
- **THEN** the phone row of the contact block shows that file
- **AND** the e-mail row still shows the shipped e-mail icon
