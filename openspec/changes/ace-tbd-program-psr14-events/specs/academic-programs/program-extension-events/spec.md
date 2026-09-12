## Purpose

Defines what an integrator can change in the program list and on program
pages through event listeners, without replacing classes of the extension.

## ADDED Requirements

### Requirement: The program selection of a list can be changed
The program list SHALL let an event listener change the selection it built
from the element settings and the submitted filter before the programs are
queried. The list SHALL show the programs matching the changed selection.

#### Scenario: Listener restricts the list to one category
- **WHEN** a listener restricts the selection of a program list to the degree
  "Master"
- **THEN** the list shows only programs with the degree "Master"

### Requirement: The listed programs and template variables can be changed
The program list SHALL let an event listener replace or reorder the programs
and the offered filter categories before they reach the template, and add
variables the template can render.

#### Scenario: Listener reorders the programs
- **WHEN** a listener reverses the order of the queried programs
- **THEN** the list renders them in the reversed order

#### Scenario: Listener adds a template variable
- **WHEN** a listener adds a variable and a template override of the list
  renders it
- **THEN** the rendered list contains the value of that variable

### Requirement: The data of a program page can be changed
A program page SHALL let an event listener change its program data before
the page template renders it.

#### Scenario: Listener changes the subtitle
- **WHEN** a listener replaces the subtitle of a program page's data
- **THEN** the page shows the replaced subtitle

### Requirement: Output without listeners is unchanged
Without registered listeners the program list and program pages SHALL render
exactly as before this change, on TYPO3 v13 and v14.

#### Scenario: No listener registered
- **WHEN** an installation registers no listener for these events
- **THEN** program lists and program pages render the same output as before
