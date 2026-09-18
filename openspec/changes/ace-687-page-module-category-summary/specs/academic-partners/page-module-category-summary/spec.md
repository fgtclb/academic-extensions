## Purpose

Shows editors the categories assigned to a partner page directly in the page
module, grouped by category type, without opening the page properties.

## ADDED Requirements

### Requirement: The page module summarises the categories of a partner page
The page module SHALL show, above the content grid of a partner page, one
entry per category type of the `partners` group with the titles of the
categories assigned to that page. This SHALL hold on TYPO3 v13 and v14.

#### Scenario: Partner page with categories
- **WHEN** an editor opens a partner page carrying the region "Europe" in the
  page module
- **THEN** the summary lists the region type with "Europe"

#### Scenario: Type without an assigned category
- **WHEN** a partner page carries no category of the partner type
- **THEN** the summary lists the partner type with a "not set" note

### Requirement: Partner category types are labelled with their registered title
Each entry of the summary MUST carry the title and icon the category type was
registered with, including types an integrator adds or overrides.

#### Scenario: Shipped type
- **WHEN** the summary shows the region type
- **THEN** its label is the translated title of the region type, never an
  empty string or a raw label key

### Requirement: The summary appears on partner pages only
The page module MUST NOT show the partner category summary on a page of any
other type.

#### Scenario: Program page
- **WHEN** an editor opens a program page
- **THEN** no partner category summary is shown
