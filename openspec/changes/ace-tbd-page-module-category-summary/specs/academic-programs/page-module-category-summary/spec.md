## Purpose

Shows editors the categories assigned to a program page directly in the page
module, grouped by category type, without opening the page properties.

## ADDED Requirements

### Requirement: The page module summarises the categories of a program page
The page module SHALL show, above the content grid of a program page, one
entry per category type of the `programs` group with the titles of the
categories assigned to that page. This SHALL hold on TYPO3 v13 and v14.

#### Scenario: Program page with categories
- **WHEN** an editor opens a program page carrying the degree "Bachelor of
  Science" and the location "Campus A" in the page module
- **THEN** the summary lists the degree type with "Bachelor of Science" and
  the location type with "Campus A"

#### Scenario: Type without an assigned category
- **WHEN** a program page carries no category of the teaching language type
- **THEN** the summary lists the teaching language type with a "not set" note

#### Scenario: Hidden category
- **WHEN** a program page carries a hidden category
- **THEN** the summary lists that category marked as hidden

### Requirement: Category types are labelled with their registered title
Each entry of the summary MUST carry the title and icon the category type was
registered with, including types an integrator adds or overrides.

#### Scenario: Shipped type
- **WHEN** the summary shows the degree type
- **THEN** its label is the translated title of the degree type, never an
  empty string or a raw label key

#### Scenario: Type added by an integrator
- **WHEN** an integrator registers an additional type in the `programs` group
  and a program page carries a category of it
- **THEN** the summary lists that type with the title the integrator gave it

### Requirement: The summary appears on program pages only
The page module MUST NOT show the program category summary on a page of any
other type.

#### Scenario: Standard page
- **WHEN** an editor opens a standard page carrying categories
- **THEN** no program category summary is shown

### Requirement: Integrators can override the summary template
The summary template SHALL be replaceable through page TSconfig without
replacing any core backend template.

#### Scenario: Template override
- **WHEN** an integrator registers a template override for the summary in
  page TSconfig
- **THEN** the page module renders the summary with the integrator's template
