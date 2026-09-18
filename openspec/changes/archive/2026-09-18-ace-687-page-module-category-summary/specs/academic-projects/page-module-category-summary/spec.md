## Purpose

Shows editors the categories assigned to a project page directly in the page
module, grouped by category type, without opening the page properties.

## ADDED Requirements

### Requirement: The page module summarises the categories of a project page
The page module SHALL show, above the content grid of a project page, one
entry per category type of the `projects` group with the titles of the
categories assigned to that page. This SHALL hold on TYPO3 v13 and v14.

#### Scenario: Project page with categories
- **WHEN** an editor opens a project page carrying the competence field
  "Energy" in the page module
- **THEN** the summary lists the competence field type with "Energy"

#### Scenario: Type without an assigned category
- **WHEN** a project page carries no category of the funding partner type
- **THEN** the summary lists the funding partner type with a "not set" note

### Requirement: Project category types are labelled with their registered title
Each entry of the summary MUST carry the title and icon the category type was
registered with, including types an integrator adds or overrides.

#### Scenario: Shipped type
- **WHEN** the summary shows the competence field type
- **THEN** its label is the translated title of that type, never an empty
  string or a raw label key

### Requirement: The summary appears on project pages only
The page module MUST NOT show the project category summary on a page of any
other type.

#### Scenario: Partner page
- **WHEN** an editor opens a partner page
- **THEN** no project category summary is shown
