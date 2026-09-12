## Purpose

Defines how a page of the program page type renders the content elements of
its main column, and which site sets it needs for that.

## ADDED Requirements

### Requirement: Program pages render without the content-load set

A program page SHALL render successfully on a site that includes the program
list or program details site set without the content-load site set. This
applies to TYPO3 v13 and v14 alike.

#### Scenario: Site without the content-load set

- **WHEN** a visitor requests a program page of a site that includes only the
  program details site set
- **THEN** the page is delivered with HTTP status 200
- **AND** it contains the program title

#### Scenario: Site with the aggregate set

- **WHEN** a visitor requests a program page of a site that includes the
  aggregate site set `fgtclb/academic-programs`
- **THEN** the page renders the same content as without the content-load set

### Requirement: Program pages render their main column content

A program page SHALL render the visible content elements of its main column
in their manual order, in the language of the requested page, below the
program data. Content elements of other columns MUST NOT be rendered by the
page template.

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

### Requirement: The content load override is no longer shipped

The programs extension SHALL NOT offer a site set or a static template that
redefines `styles.content.getContent`, and its aggregate site set SHALL NOT
define that object. This applies to TYPO3 v13 and v14 alike.

#### Scenario: Site with the aggregate set

- **WHEN** a site includes the aggregate site set `fgtclb/academic-programs`
  and nothing else defines `styles.content.getContent`
- **THEN** that object is undefined for the pages of the site
- **AND** program pages still render their main column content

#### Scenario: Choosing a set or a static template

- **WHEN** an integrator lists the site sets or the static templates of the
  programs extension
- **THEN** no content-load set and no content load override template is
  offered
