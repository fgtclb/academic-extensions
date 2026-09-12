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

### Requirement: The rich text processing configuration is required

A site that renders project pages or project lists SHALL provide the standard
rich text processing configuration, as fluid_styled_content and common site
packages do. Without it, rendering a project with rich text MUST fail
visibly instead of emitting unprocessed HTML.

#### Scenario: Site without rich text configuration

- **WHEN** a site has no rich text processing configuration and a visitor
  opens a project page
- **THEN** rendering fails with the core error for a missing rich text
  configuration
