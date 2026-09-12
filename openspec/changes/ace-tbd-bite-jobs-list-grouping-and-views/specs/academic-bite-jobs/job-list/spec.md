## Purpose

Defines how the B-ITE job list content element chooses its view and whether
it groups the jobs it receives.

## ADDED Requirements

### Requirement: Jobs are grouped only on request

The job list SHALL render the jobs ungrouped unless an integrator names a
field of the job postings as the grouping field in TypoScript. When a
grouping field is named, the list SHALL render one group per value of that
field. This applies to TYPO3 v13 and v14 alike.

#### Scenario: No grouping configured

- **WHEN** a job list content element with header layout 1 and no subheader
  is rendered on a site that names no grouping field
- **THEN** every job title is rendered as a second-level heading and no group
  heading is rendered

#### Scenario: Grouping field configured

- **WHEN** an integrator names a grouping field and two jobs carry different
  values in it
- **THEN** the list renders two groups, each headed by its value

### Requirement: Pre-2.1 view values keep working

The job list SHALL render the list, card or table view for the stored values
`List`, `Card` and `Table`, and SHALL treat the pre-2.1 values `ListView`,
`CardView` and `TableView` as the corresponding view. An empty or unknown
value SHALL render the list view.

#### Scenario: Content element saved before 2.1

- **WHEN** a job list content element stores the view value `CardView`
- **THEN** the jobs are rendered in the card view

#### Scenario: Unknown view value

- **WHEN** a job list content element stores no view value or a value that
  names no view
- **THEN** the jobs are rendered in the list view

### Requirement: An upgrade wizard migrates stored view values

The upgrade wizards of the installation SHALL offer a wizard that rewrites
the stored view value of every job list content element to `List`, `Card` or
`Table`, by the same rule the list applies when rendering. It SHALL leave
content elements that already store one of these values, other content
types and every other field of the element unchanged, and SHALL report
nothing to do once no element needs migrating. This applies to TYPO3 v13 and
v14 alike.

#### Scenario: Old and invalid values are migrated

- **WHEN** an administrator runs the wizard and job list content elements
  store `CardView`, an empty value and an unknown value
- **THEN** they store `Card`, `List` and `List` afterwards, and the wizard
  reports nothing left to do

#### Scenario: Current values and other content are untouched

- **WHEN** a job list content element stores `Table` and another content
  type stores `ListView` in a field of the same name
- **THEN** both are unchanged after the wizard ran
