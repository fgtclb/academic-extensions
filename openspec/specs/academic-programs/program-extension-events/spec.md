# academic-programs/program-extension-events Specification

## Purpose

Defines what an integrator can change in the program list, the program
finder and on program pages through event listeners, without replacing
classes of the extension.

## Requirements

### Requirement: The program selection of a list can be changed
The program list SHALL let an event listener change the selection it built
from the element settings and the submitted filter before the programs are
queried. The list SHALL show the programs matching the selection the
listener hands back.

#### Scenario: Listener restricts the list to one category
- **WHEN** a listener restricts the selection of a program list to the degree
  "Master"
- **THEN** the list shows only programs with the degree "Master"

#### Scenario: Listener hands back a selection of its own
- **WHEN** a listener replaces the selection of a program list with one it
  built itself, restricted to one storage page
- **THEN** the list shows only the programs on that page

### Requirement: The program finder follows a changed selection
The program finder SHALL let the same event listener change its selection
before it looks up the programs in its storage, and SHALL offer only the
categories of the programs that selection finds.

#### Scenario: Listener restricts the finder to one category
- **WHEN** a listener restricts the selection of a program finder to the
  degree "Master"
- **THEN** the finder offers the categories of the "Master" programs and
  offers the categories no "Master" program carries as disabled options

### Requirement: The listed programs, categories and template variables can be changed
The program list SHALL let an event listener replace or reorder the programs
and replace the offered filter categories before they reach the template,
and assign variables the template can render. The program finder SHALL let
the same listener replace the categories it offers.

#### Scenario: Listener reorders the programs
- **WHEN** a listener reverses the order of the queried programs
- **THEN** the list renders them in the reversed order

#### Scenario: Listener replaces the offered categories
- **WHEN** a listener replaces the categories of a program list with a single
  category
- **THEN** the filter offers only that category and the listed programs stay
  the same

#### Scenario: Listener replaces the categories of the finder
- **WHEN** a listener replaces the categories of a program finder with a
  single category
- **THEN** the finder offers only that category

#### Scenario: Listener adds a template variable
- **WHEN** a listener adds a variable and a template override of the list
  renders it
- **THEN** the rendered list contains the value of that variable

### Requirement: The data of a program page can be changed
A program page SHALL let an event listener change its program data before
the page template renders it, and SHALL build the facts of the page from the
changed data.

#### Scenario: Listener changes the subtitle
- **WHEN** a listener replaces the subtitle of a program page's data
- **THEN** the page shows the replaced subtitle

#### Scenario: Listener changes a fact
- **WHEN** a listener changes the credit points of a program page's data
- **THEN** the facts of the page show the changed credit points

### Requirement: Output without listeners is unchanged
Without registered listeners the program list, the program finder and
program pages SHALL render exactly as before this change, on TYPO3 v13 and
v14.

#### Scenario: No listener registered
- **WHEN** an installation registers no listener for these events
- **THEN** program lists, program finders and program pages render the same
  output as before
