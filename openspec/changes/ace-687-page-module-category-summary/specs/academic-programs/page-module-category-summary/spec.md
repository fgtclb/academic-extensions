## Purpose

Shows editors the categories assigned to a program page directly in the page
module, grouped by category type, without opening the page properties.

## ADDED Requirements

### Requirement: The page module summarises the categories of a program page

The page module SHALL show, above the content grid of a program page, one entry
per category type of the `programs` group with the titles of the categories
assigned to that page. This SHALL hold on TYPO3 v12 and v13.

#### Scenario: Program page with categories

- **WHEN** an editor opens a program page carrying the degree "Bachelor of Science" and the location "Campus A" in the page module
- **THEN** the summary lists the degree type with "Bachelor of Science"

#### Scenario: Type without an assigned category

- **WHEN** a program page carries no category of the teaching language type
- **THEN** the summary lists the teaching language type with a "not set" note

### Requirement: Program category types are labelled with their registered title

Each entry of the summary MUST carry the title and icon the category type was
registered with, including types an integrator adds or overrides.

#### Scenario: Shipped type

- **WHEN** the summary shows the degree type
- **THEN** its label is the translated title of that type, never an empty
  string or a raw label key

### Requirement: The summary appears on program pages only

The page module MUST NOT show the program category summary on a page of any
other type.

#### Scenario: Standard page

- **WHEN** an editor opens a standard page carrying categories of the
  `programs` group
- **THEN** no program category summary is shown
