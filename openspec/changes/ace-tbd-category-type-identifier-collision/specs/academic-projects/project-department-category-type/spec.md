## Purpose

Gives the projects department its own category type, so it is selected, stored
and filtered apart from the study programs department, and moves existing
project departments to it.

## ADDED Requirements

### Requirement: The projects department is a type of its own
The projects extension SHALL provide the department category type under the
identifier `project_department`, so that with the programs extension installed
the type select lists each department exactly once, on TYPO3 v13 and v14.

#### Scenario: Both extensions installed
- **WHEN** an editor opens the type select of a category with programs and
  projects installed
- **THEN** it lists the programs department and the projects department as two
  entries with different values

#### Scenario: Saving a project department category
- **WHEN** an editor sets a category to the projects department type and saves
- **THEN** the category reopens with the projects department type selected

### Requirement: The migration command moves unambiguous project departments
A console command of the projects extension SHALL change the type of stored
`department` categories to `project_department` where the category is
unambiguously a project department, and SHALL leave every other category
unchanged. The projects extension SHALL NOT offer this migration as an
upgrade wizard.

#### Scenario: Category used on project pages only
- **WHEN** a `department` category is assigned only to project pages and the
  integrator runs the migration command
- **THEN** the command changes it and its translations to
  `project_department`

#### Scenario: Category used on program pages only
- **WHEN** a `department` category is assigned only to program pages and the
  integrator runs the migration command
- **THEN** the command leaves it unchanged

#### Scenario: Category used on both or on no page
- **WHEN** a `department` category is assigned to program and project pages,
  or to no page, while the programs extension is installed
- **THEN** the command leaves it unchanged and lists it by uid and title

#### Scenario: Programs extension not installed
- **WHEN** the programs extension is not installed and the integrator runs
  the migration command
- **THEN** the command changes every `department` category to
  `project_department`

### Requirement: The migration command can be run again
Running the migration command again SHALL change only categories that are
still unambiguous project departments, SHALL list the remaining ambiguous
categories again, and SHALL finish successfully when nothing is left to move.

#### Scenario: Only ambiguous categories remain
- **WHEN** every remaining `department` category is ambiguous and the
  integrator runs the command again
- **THEN** no category changes, the ambiguous categories are listed, and the
  command finishes successfully
