## Purpose

Defines how the rich text fields of a project - short description and
funders - reach the visitor on the project page and in the project list.

## ADDED Requirements

### Requirement: Project rich text is rendered like other rich text

The project page SHALL render the short description and the funders, and the
project list SHALL render the short description, through the site's
standard rich text processing. This applies to TYPO3 v12 and v13 alike.

#### Scenario: Link to a page in the short description

- **WHEN** an editor links a word in the short description of a project to a
  page of the site
- **THEN** the project page and the project list render that link with the
  page's URL and no `t3://` reference

#### Scenario: Link in the funders field

- **WHEN** the funders field of a project contains a link to a page
- **THEN** the project page renders the link with the page's URL

### Requirement: The site's rich text configuration applies

The project page and the project list SHALL follow the site's rich text
processing configuration. On TYPO3 v13, where the site defines none of its
own, the TYPO3 default applies, so links resolve without any additional
extension or TypoScript. On TYPO3 v12 the site MUST provide that
configuration itself, as fluid_styled_content and common site packages do;
without it, rendering a project MUST fail visibly instead of emitting
unprocessed HTML.

#### Scenario: Site without fluid_styled_content on TYPO3 v13

- **WHEN** a TYPO3 v13 site does not include fluid_styled_content and the
  short description of a project links to a page
- **THEN** the project page renders that link with the page's URL

#### Scenario: Site without rich text configuration on TYPO3 v12

- **WHEN** a TYPO3 v12 site provides no rich text processing configuration
  and a visitor opens a project page
- **THEN** rendering fails with the core error for a missing rich text
  configuration
