## Purpose

Defines the application link an editor maintains on a program page, and how
visitors and template overrides get it.

## ADDED Requirements

### Requirement: Editors maintain an application link on program pages
The page properties of a program page SHALL offer an application link, which
accepts a page or an external URL, and an optional label of at most 60
characters, on the program tab. Other page types SHALL NOT offer these
fields.

#### Scenario: Editor opens a program page
- **WHEN** an editor opens the page properties of a program page
- **THEN** the program tab offers the application link and its label

#### Scenario: Editor opens a standard page
- **WHEN** an editor opens the page properties of a standard page
- **THEN** neither field is offered

### Requirement: Visitors see the application link on the program page
A program page with an application link SHALL render a link to its target,
labelled with the label the editor entered.

#### Scenario: Link and label set
- **WHEN** the application link points to page 5 and the label is
  "Apply online"
- **THEN** the program page contains a link to page 5 reading "Apply online"

#### Scenario: Label left empty
- **WHEN** the application link is set and the label is empty
- **THEN** the link reads "Apply now" in the language of the page

#### Scenario: Link left empty
- **WHEN** the application link is empty
- **THEN** the program page renders no application link, whatever the label

### Requirement: Templates can render the application link
The application link and its label SHALL be available to template overrides
of the program page and of program list items.

#### Scenario: List item override renders the link
- **WHEN** an integrator's list item override renders the application link
  of each program
- **THEN** each program with a link shows it, and programs without one show
  nothing
