## Purpose

Defines how a contract of `academic_persons` is shown or hidden on the public
site: through its visibility alone, with no separate publish flag, and how a
project carries a publish flag of its own into that visibility.

## ADDED Requirements

### Requirement: The visibility is the only switch of a contract

The system SHALL decide whether a contract is shown on the public site by its
visibility alone. A contract SHALL have no separate publish flag, neither in
the backend form nor in the data an integrator or importer can write. This
applies on TYPO3 v13 and v14.

#### Scenario: Backend form of a contract

- **WHEN** an editor opens a contract in the backend
- **THEN** the form shows the visibility toggle and no "Show this contract
  online?" toggle

#### Scenario: Hidden contract

- **WHEN** a contract is hidden and a visitor opens the list, the detail view
  or a selected-contracts element that shows it
- **THEN** the contract and its contact data are left out, unless the
  element is set to show hidden records

### Requirement: The update does not change the rendered output

The system MUST render the same contracts after the update as before it, on an
installation without code of its own for the publish flag. Removing the flag
SHALL NOT hide or show any contract.

#### Scenario: Contract that was never published

- **WHEN** an installation is updated that has a visible contract whose
  publish flag was never set
- **THEN** the contract is still shown on the public site

### Requirement: The migration is only offered when a project asks for it

The system SHALL ship a migration that hides every contract whose publish flag
was not set. It MUST NOT offer it in the upgrade wizards of an installation
unless the installation registers it itself.

#### Scenario: Installation without a registration

- **WHEN** an integrator opens the list of upgrade wizards after the update
- **THEN** the contract publish migration is not listed

#### Scenario: Project registers the migration

- **WHEN** a project registered the migration in its own site package
- **THEN** the upgrade wizards list it, and running it again after it
  finished changes nothing

### Requirement: The migration carries the publish flag into the visibility

When it runs, the migration SHALL hide every contract whose publish flag was
not set and SHALL keep the visibility of every other contract. It SHALL decide
by the default language record and apply the result to its translations. It
SHALL treat live records and workspace versions alike. It SHALL work whether
the database still holds the old column under its name or as a column renamed
for removal.

#### Scenario: Unpublished contract

- **WHEN** the migration runs and a visible contract was not published
- **THEN** the contract is hidden in the default language and in every
  translation

#### Scenario: Published contract

- **WHEN** the migration runs and a contract was published
- **THEN** its visibility is what it was before, hidden or not

#### Scenario: Translation with a different flag

- **WHEN** the default language contract was published and its translation
  was not
- **THEN** neither is hidden by the migration

#### Scenario: Workspace version of a translation

- **WHEN** the migration runs and a workspace holds a version of a contract and
  of its translation
- **THEN** the translation version is hidden exactly when the contract version
  of the same workspace was not published, whatever the live contract says

#### Scenario: Workspace without a version of the contract

- **WHEN** the migration runs and a workspace holds a version of a translation
  but no version of its contract
- **THEN** the translation version is hidden exactly when the live contract was
  not published

#### Scenario: Translation without a default language contract

- **WHEN** the migration runs and a translation has no default language
  contract
- **THEN** it is hidden exactly when it was not published itself

#### Scenario: Column already renamed for removal

- **WHEN** the database analyser renamed the old column for removal before the
  migration ran
- **THEN** the migration reads the renamed column and hides the same
  contracts
