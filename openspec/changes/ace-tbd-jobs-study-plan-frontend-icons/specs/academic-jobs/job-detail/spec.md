## ADDED Requirements

### Requirement: Job property icons come from the frontend icon registration

The job list and the job detail view SHALL render the icon in front of each
job property they show from the frontend icon registration, under the
identifier `academic_jobs-<property>`, for the twelve properties they show:
`employmentStartDate`, `companyName`, `sector`, `type`, `requiredDegree`,
`contractualRelationship`, `employmentType`, `workLocation`,
`internationalsWelcome`, `alumniRecommend`, `link` and `endtime`.
The extension SHALL also register `academic_jobs-starttime`,
`academic_jobs-contactName` and `academic_jobs-contactAdditionalInformation`
for the frontend, so that a template override that shows one of those
properties the same way gets an icon.
A site package that registers one of these identifiers in its frontend icon
registration SHALL see its own icon in both views. A registration of the same
identifier in the backend icon registration SHALL NOT change what the job
views render, and the backend icon registry SHALL NOT know the identifiers.
The icons SHALL render with the same markup, size and colour as before this
change. The job record icon and the plugin icon SHALL stay in the backend
icon registry. This applies on TYPO3 v13 and v14 alike.

#### Scenario: Every shown property has its icon

- **WHEN** a visitor opens the list and the detail view of a job that carries
  all twelve properties
- **THEN** each property row shows the icon of `academic_jobs-<property>`
- **AND** no row shows the "icon not found" placeholder

#### Scenario: A site package replaces a property icon

- **WHEN** a site package registers its own drawing for
  `academic_jobs-companyName` in its frontend icon registration
- **THEN** the job list and the job detail view show that drawing in front of
  the company name

#### Scenario: A replacement in the backend registration

- **WHEN** a site package registers its own drawing for
  `academic_jobs-companyName` in its backend icon registration only
- **THEN** the job views show the drawing the extension ships

#### Scenario: An override extends the property list

- **WHEN** a template override of the job detail view adds `starttime` to the
  properties it shows
- **THEN** the start time row shows the icon of `academic_jobs-starttime`

#### Scenario: An override still asks the backend registry

- **WHEN** a template override renders `academic_jobs-companyName` through the
  backend icon registry
- **THEN** it shows the "icon not found" placeholder

#### Scenario: The backend icons stay

- **WHEN** an editor opens the record list of a folder with jobs and the new
  content element wizard
- **THEN** the job records and the three job plugins show their icons as
  before
