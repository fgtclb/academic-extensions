## Purpose

Defines what a site visitor sees on a page of the program page type, starting
with the program of the page on both page object types.

## ADDED Requirements

### Requirement: Program pages show the program of their own page

A program page SHALL show the program of the page it is on. A variable `data`
that a `PAGEVIEW` site package assigns for its own purposes SHALL NOT change
which program the page shows and SHALL NOT make the page fail. `PAGEVIEW`
exists from TYPO3 v13 on. On TYPO3 v12 and v13 a `FLUIDTEMPLATE` page object
keeps showing the program of the page.

#### Scenario: PAGEVIEW site package with a text as `data`

- **WHEN** a `PAGEVIEW` site package on TYPO3 v13 assigns a text as `data` and
  a visitor opens a program page
- **THEN** the page shows the title of the program, not an error

#### Scenario: PAGEVIEW site package with a list of records as `data`

- **WHEN** a `PAGEVIEW` site package on TYPO3 v13 assigns the records of a
  query as `data` and a visitor opens a program page
- **THEN** the page shows the title of its own program

#### Scenario: FLUIDTEMPLATE site package with a variable `page` of its own

- **WHEN** a `FLUIDTEMPLATE` site package assigns a variable `page` of its own
  and a visitor opens a program page
- **THEN** the page shows the title of the program
