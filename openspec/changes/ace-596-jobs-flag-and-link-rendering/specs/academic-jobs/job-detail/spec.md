## Purpose

Defines how the job detail view and the job list present the flags and the
link of a job to a site visitor.

## ADDED Requirements

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
