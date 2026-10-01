## ADDED Requirements

### Requirement: Partner pages show the partner of their own page

A partner page SHALL show the partner of the page it is on. A variable `data`
that a `PAGEVIEW` site package assigns for its own purposes SHALL NOT change
which partner the page shows and SHALL NOT make the page fail. `PAGEVIEW`
exists from TYPO3 v13 on. On TYPO3 v12 and v13 a `FLUIDTEMPLATE` page object
keeps showing the partner of the page.

#### Scenario: PAGEVIEW site package with a text as `data`

- **WHEN** a `PAGEVIEW` site package on TYPO3 v13 assigns a text as `data` and
  a visitor opens a partner page
- **THEN** the page shows the title of the partner, not an error

#### Scenario: PAGEVIEW site package with a list of records as `data`

- **WHEN** a `PAGEVIEW` site package on TYPO3 v13 assigns the records of a
  query as `data` and a visitor opens a partner page
- **THEN** the page shows the title of its own partner

#### Scenario: FLUIDTEMPLATE site package with a variable `page` of its own

- **WHEN** a `FLUIDTEMPLATE` site package assigns a variable `page` of its own
  and a visitor opens a partner page
- **THEN** the page shows the title of the partner
