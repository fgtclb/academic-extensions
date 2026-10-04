## MODIFIED Requirements

### Requirement: Job property icons come from the frontend icon registration

The job list and the job detail view SHALL render the icon in front of each
job property they show from the frontend icon registration, under an
identifier of its own for every property, `tx-academicjobs-info-` followed by
the property name in kebab case, for the twelve properties they show:
`tx-academicjobs-info-employment-start-date`,
`tx-academicjobs-info-company-name`, `tx-academicjobs-info-sector`,
`tx-academicjobs-info-type`, `tx-academicjobs-info-required-degree`,
`tx-academicjobs-info-contractual-relationship`,
`tx-academicjobs-info-employment-type`,
`tx-academicjobs-info-work-location`,
`tx-academicjobs-info-internationals-welcome`,
`tx-academicjobs-info-alumni-recommend`, `tx-academicjobs-info-link` and
`tx-academicjobs-info-endtime`.
The extension SHALL also register `tx-academicjobs-info-starttime`,
`tx-academicjobs-info-contact-name` and
`tx-academicjobs-info-contact-additional-information` for the frontend, so
that a template override that shows one of those properties the same way gets
an icon.
The icons SHALL show the shared Font Awesome Free glyphs of the base
extension, inlined into the page and drawn in the colour of the surrounding
text at the size of its font, not as an image.
A site package that registers one of these identifiers in its frontend icon
registration SHALL see its own icon in both views, for that property only. A
registration of the same identifier in the backend icon registration SHALL
NOT change what the job views render, and the backend icon registry SHALL NOT
know the identifiers. The identifiers of 2.x, `academic_jobs-<property>`,
SHALL NOT be registered any more.
The job record icon `tx-academicjobs-record-job` and the plugin icon
`tx-academicjobs-plugin-jobs` SHALL stay in the backend icon registry. This
applies on TYPO3 v13 and v14 alike.

#### Scenario: Every shown property has its icon

- **WHEN** a visitor opens the list and the detail view of a job that carries
  all twelve properties
- **THEN** each property row shows the icon of its own identifier, for
  example `tx-academicjobs-info-work-location` for the work location
- **AND** every icon is an inlined drawing in the colour of the text
- **AND** no row shows the "icon not found" placeholder

#### Scenario: A site package replaces a property icon

- **WHEN** a site package registers its own drawing for
  `tx-academicjobs-info-company-name` in its frontend icon registration
- **THEN** the job list and the job detail view show that drawing in front of
  the company name
- **AND** the other properties keep the shipped glyphs

#### Scenario: A replacement in the backend registration

- **WHEN** a site package registers its own drawing for
  `tx-academicjobs-info-work-location` in its backend icon registration only
- **THEN** the job views show the glyph the extension ships in front of the
  work location

#### Scenario: An override extends the property list

- **WHEN** a template override of the job detail view adds `starttime` to the
  properties it shows, with the icon name `starttime`
- **THEN** the start time row shows the icon of
  `tx-academicjobs-info-starttime`

#### Scenario: An override still asks the backend registry

- **WHEN** a template override renders `tx-academicjobs-info-company-name`
  through the backend icon registry
- **THEN** it shows the "icon not found" placeholder

#### Scenario: An override still names a 2.x identifier

- **WHEN** a template override renders `academic_jobs-companyName`
- **THEN** it shows the "icon not found" placeholder

#### Scenario: The backend icons stay

- **WHEN** an editor opens the record list of a folder with jobs, the page
  module and the new content element wizard
- **THEN** the job records show the icon of `tx-academicjobs-record-job`
- **AND** the three job content elements show the icon of
  `tx-academicjobs-plugin-jobs` in the page module and in the wizard
- **AND** both icons take the text colour of the backend colour scheme
