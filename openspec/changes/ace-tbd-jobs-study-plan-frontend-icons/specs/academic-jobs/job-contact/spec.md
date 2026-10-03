## ADDED Requirements

### Requirement: Contact icons come from the frontend icon registration

The contact block of the job detail view SHALL render the icon in front of
the phone number from the frontend icon registration under
`academic_jobs-contactPhone`, and the icon in front of the e-mail address
under `academic_jobs-contactEmail`, with the same markup and size as the
property icons of the same view, as before this change.
A site package that registers one of the two identifiers in its frontend icon
registration SHALL see its own icon in the contact block. A registration of
the same identifier in the backend icon registration SHALL NOT change what
the contact block renders. This applies on TYPO3 v13 and v14 alike.

#### Scenario: Stock installation

- **WHEN** a visitor opens the detail view of a job with a contact phone
  number and a contact e-mail address
- **THEN** the phone row shows the icon of `academic_jobs-contactPhone` and
  the e-mail row the icon of `academic_jobs-contactEmail`
- **AND** neither row shows the "icon not found" placeholder

#### Scenario: A site package replaces the phone icon

- **WHEN** a site package registers its own drawing for
  `academic_jobs-contactPhone` in its frontend icon registration
- **THEN** the phone row of the contact block shows that drawing

#### Scenario: A replacement in the backend registration

- **WHEN** a site package registers its own drawing for
  `academic_jobs-contactPhone` in its backend icon registration only
- **THEN** the phone row shows the drawing the extension ships

#### Scenario: An override still asks the backend registry

- **WHEN** a template override of the contact block renders
  `academic_jobs-contactPhone` through the backend icon registry
- **THEN** it shows the "icon not found" placeholder
