## ADDED Requirements

### Requirement: Project pages show the project of their own page

A project page SHALL show the project of the page it is on. A variable `data`
that a `PAGEVIEW` site package assigns for its own purposes SHALL NOT change
which project the page shows and SHALL NOT make the page fail. `PAGEVIEW`
exists from TYPO3 v13 on. On TYPO3 v12 and v13 a `FLUIDTEMPLATE` page object
keeps showing the project of the page.

#### Scenario: PAGEVIEW site package with a text as `data`

- **WHEN** a `PAGEVIEW` site package on TYPO3 v13 assigns a text as `data` and
  a visitor opens a project page
- **THEN** the page shows the title of the project, not an error

#### Scenario: PAGEVIEW site package with a list of records as `data`

- **WHEN** a `PAGEVIEW` site package on TYPO3 v13 assigns the records of a
  query as `data` and a visitor opens a project page
- **THEN** the page shows the title of its own project

#### Scenario: FLUIDTEMPLATE site package with a variable `page` of its own

- **WHEN** a `FLUIDTEMPLATE` site package assigns a variable `page` of its own
  and a visitor opens a project page
- **THEN** the page shows the title of the project

### Requirement: The project heading falls back to the page title

A project page SHALL show the project title as its heading, and the title of
the page when the project has none, on a `FLUIDTEMPLATE` page object and, on
TYPO3 v13, on a `PAGEVIEW` page object.

#### Scenario: Project without a project title on PAGEVIEW

- **WHEN** a visitor opens a project page without a project title on a
  `PAGEVIEW` site on TYPO3 v13
- **THEN** the heading shows the title of the page

#### Scenario: Project without a project title on FLUIDTEMPLATE

- **WHEN** a visitor opens a project page without a project title on a
  `FLUIDTEMPLATE` site
- **THEN** the heading shows the title of the page, as before
