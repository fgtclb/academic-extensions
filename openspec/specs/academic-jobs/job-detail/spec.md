# academic-jobs/job-detail Specification

## Purpose
Defines how the job detail view and the job list present the flags and the
link of a job to a site visitor.

## Requirements

### Requirement: Job flags are shown as labels

The job detail view SHALL show a translated label for "internationals
welcome" and for "recommended by alumni" when the flag is set on the job, and
SHALL show nothing for a flag that is not set. The stored value MUST NOT be
printed. The labels read "International applicants welcome" and "Recommended
by alumni" in English, "Internationale Bewerbungen willkommen" and "Von
Alumni empfohlen" in German. This applies to TYPO3 v13 and v14 alike.

#### Scenario: Both flags set

- **WHEN** a visitor opens the detail view of a job with both flags set
- **THEN** the labels "International applicants welcome" and "Recommended by
  alumni" are shown and the value `1` is not printed next to them

#### Scenario: Flag not set

- **WHEN** a visitor opens the detail view of a job without the flags
- **THEN** neither label is shown

#### Scenario: German frontend

- **WHEN** a visitor opens the detail view in German for a job with both
  flags set
- **THEN** the labels "Internationale Bewerbungen willkommen" and "Von Alumni
  empfohlen" are shown

### Requirement: The job link is a working link

The job detail view SHALL render the job's link as an anchor with the link
text "To the job posting" in English and "Zur Stellenausschreibung" in
German, resolving links to pages, files and records as well as external
URLs. The row SHALL keep its label "Link" in front of the anchor.

#### Scenario: External URL

- **WHEN** the link of a job is an external URL
- **THEN** the detail view renders the row label "Link" followed by an
  anchor to that URL with the link text

#### Scenario: Link to a page

- **WHEN** the link of a job points to a page of the site
- **THEN** the detail view renders an anchor to the page's URL and no
  `t3://` reference

### Requirement: The job list presents flags and link the same way

Each job of the job list SHALL show the same flag labels as the detail view,
only for flags that are set and without the stored value, and SHALL render
the job's link as an anchor with the same link text. This applies to TYPO3
v13 and v14 alike.

#### Scenario: Job with flags and a page link in the list

- **WHEN** a visitor opens the job list and a job has both flags set and a
  link to a page of the site
- **THEN** the job's entry shows both flag labels without the value `1`, and
  an anchor to the page's URL with the link text and no `t3://` reference

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

### Requirement: The job detail names the job in the head of the page

The page that shows a job in the job detail view SHALL carry the title of the
job as the title of the page, with the site title around it the way the site
configures it. It SHALL describe the job with the text of its description,
without markup, with entities decoded and white space reduced to single
spaces. A job without a description text SHALL leave the description of the
page as it is, without an empty description. A page that shows no job SHALL
keep its own title. This applies to TYPO3 v13 and v14 alike.

#### Scenario: Job with a description

- **WHEN** a visitor opens the detail view of the job "International Research
  Fellowship" with the description "Join our quantum optics group."
- **THEN** the title of the page names the job
- **AND** the page is described as "Join our quantum optics group."

#### Scenario: Job without a description

- **WHEN** a visitor opens the detail view of a job without a description, or
  with an empty paragraph only
- **THEN** the title of the page names the job
- **AND** the page carries no empty description

### Requirement: A detail page without a job answers not found

The job detail view SHALL end a request without a job it can show, because no
job was requested, the job does not exist or the job is hidden, with the
"page not found" handling of the site and the status 404, instead of the page.
A job list that shows hidden jobs SHALL list a job the detail view cannot show
without a link to the detail view. This applies to TYPO3 v13 and v14 alike.

#### Scenario: Detail page without a job

- **WHEN** a visitor opens the job detail page without a job
- **THEN** the answer has the status 404 and is the error page of the site

#### Scenario: Job hidden after its link was published

- **WHEN** a visitor follows the detail link of a job that has been hidden since
- **THEN** the answer has the status 404 and does not show the job

#### Scenario: Hidden job in a list with "Show hidden records"

- **WHEN** a job list with "Show hidden records" lists a hidden job
- **THEN** the job is listed with its title and without a link to the detail
  view

### Requirement: A cached job detail ends with the job

The page cache entry of a page that shows a job in the job detail view SHALL
end no later than the next start or end time of that job or of a translation
of it, whether or not the installation enables the automatic frontend cache
tagging of the core. A job without a start or end time in the future SHALL
leave the cache lifetime of the page as it is. This applies to TYPO3 v13 and
v14 alike.

#### Scenario: Job that ends in two hours

- **WHEN** a visitor opens the detail page of a job that ends in two hours
- **THEN** the page is cached for no longer than two hours

#### Scenario: Job without start and end time

- **WHEN** a visitor opens the detail page of a job without a start or end
  time
- **THEN** the page is cached for the lifetime the page has
