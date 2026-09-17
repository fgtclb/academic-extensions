## Purpose

Defines how the rich text fields of a project - short description and
funders - reach the visitor on the project page and in the project list.

## ADDED Requirements

### Requirement: Project rich text is rendered like other rich text

The project page SHALL render the short description and the funders, and the
project list SHALL render the short description, through the site's
standard rich text processing. This applies to TYPO3 v13 and v14 alike.

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
processing configuration. Where the site defines none of its own, the TYPO3
default applies, so links resolve without any additional extension or
TypoScript. This applies to TYPO3 v13 and v14 alike.

#### Scenario: Site without fluid_styled_content

- **WHEN** a site does not include fluid_styled_content and the short
  description of a project links to a page
- **THEN** the project page renders that link with the page's URL
