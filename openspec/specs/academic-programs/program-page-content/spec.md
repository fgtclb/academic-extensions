# academic-programs/program-page-content Specification

## Purpose
Defines how a page of the program page type renders the content elements of
its main column, independent of the content-load component.

## Requirements

### Requirement: Program pages render without the content-load component

A program page SHALL render successfully on a site that includes the program
list or program details component without the content-load component, through
site sets on TYPO3 v13 and through static templates on TYPO3 v12 and v13.

#### Scenario: Site without the content-load component

- **WHEN** a visitor requests a program page of a site whose TypoScript
  includes the program details component but not the content-load component
- **THEN** the page is delivered with HTTP status 200
- **AND** it contains the program title and the content of its main column

#### Scenario: Site on the aggregate set

- **WHEN** a visitor requests a program page of a TYPO3 v13 site that includes
  the aggregate site set `fgtclb/academic-programs`
- **THEN** the page renders the same content as without the content-load
  component

### Requirement: Program pages render their main column content

A program page SHALL render the visible content elements of its main column
in their manual order, in the language of the requested page, below the
program data. Content elements of other columns MUST NOT be rendered by the
page template. This applies to TYPO3 v12 and v13 alike.

#### Scenario: Content in the main column

- **WHEN** a program page carries two content elements in the main column
- **THEN** both are rendered in their manual order

#### Scenario: Content in another column

- **WHEN** a program page carries a content element in a column other than
  the main column
- **THEN** that content element is not rendered by the program page template

#### Scenario: Translated program page

- **WHEN** a visitor requests the translation of a program page whose content
  elements are translated
- **THEN** the translated content elements are rendered

### Requirement: Integrators can adjust the program page content

The content a program page renders SHALL be configurable in TypoScript for
program pages only, without redefining a site wide object.

#### Scenario: Integrator adjusts the program page content

- **WHEN** an integrator changes the program page content configuration, for
  example to render another column
- **THEN** program pages render the adjusted content
- **AND** pages of other page types are unaffected
