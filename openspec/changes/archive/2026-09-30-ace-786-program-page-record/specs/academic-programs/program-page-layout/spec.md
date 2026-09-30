## ADDED Requirements

### Requirement: Program pages read the page from the page object

A program page SHALL show the program of the page it is on, on a
`FLUIDTEMPLATE` and on a `PAGEVIEW` page object. A variable `data` that a
`PAGEVIEW` site package assigns for its own purposes SHALL NOT change which
program the page shows and SHALL NOT make the page fail. This applies to
TYPO3 v13 and v14 alike.

#### Scenario: PAGEVIEW site package with a variable `data` of its own

- **WHEN** a `PAGEVIEW` site package assigns a variable `data` of its own and a
  visitor opens a program page with a subtitle
- **THEN** the page shows the title and the subtitle of the program, not an
  error

#### Scenario: PAGEVIEW site package with a list of records as `data`

- **WHEN** a `PAGEVIEW` site package assigns the records of a query as `data`
  and a visitor opens a program page
- **THEN** the page shows the title and the subtitle of its own program

#### Scenario: FLUIDTEMPLATE page object

- **WHEN** a visitor opens a program page of a site whose page object is a
  `FLUIDTEMPLATE`
- **THEN** the page shows the title of the program, as before
