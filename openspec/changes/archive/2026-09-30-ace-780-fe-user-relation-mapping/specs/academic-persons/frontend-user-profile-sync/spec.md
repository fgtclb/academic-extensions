## ADDED Requirements

### Requirement: Integrators map contract relations from frontend-user data
The system SHALL let an integrator assign an `fe_users` column to the
contract's organisational unit and function type, each with the field it is
matched against. On TYPO3 v13 and v14, a matching record SHALL be assigned
to the synchronised contract.

#### Scenario: Organisational unit found by unique name
- **WHEN** the organisational unit is mapped from `company` and matched by
  unique name, and an organisational unit with the unique name "X" exists
- **THEN** a frontend user with `company = "X"` gets a contract assigned to
  that unit

#### Scenario: Several records match
- **WHEN** two function types carry the same function name
- **THEN** the one with the lower uid is assigned, on every database

#### Scenario: Only an exact value matches
- **WHEN** the function type is mapped and a function type differs from the
  frontend user's value in case only
- **THEN** it is not assigned, on every database

#### Scenario: A hidden record matches
- **WHEN** the organisational unit is mapped and the only unit carrying the
  frontend user's value is hidden
- **THEN** that unit is assigned and no other one is created

#### Scenario: Deleted records never match
- **WHEN** creation is enabled and only a deleted unit carries the frontend
  user's value
- **THEN** a live unit is created and assigned

#### Scenario: Workspace records never match
- **WHEN** a unit of a workspace and a live unit carry the frontend user's
  value, and the unit of the workspace has the lower uid
- **THEN** the live unit is assigned

#### Scenario: Only a translation carries the value
- **WHEN** creation is enabled and only the translation of a unit carries the
  frontend user's value
- **THEN** a unit of the default language is created and assigned

### Requirement: Missing organisational units and function types can be created
The system SHALL create a missing organisational unit or function type on the
configured storage page only when creation is enabled for that relation. A
second synchronisation SHALL reuse the created record.

#### Scenario: Creation enabled
- **WHEN** creation is enabled with storage page 7 and no unit "X" exists
- **THEN** a unit "X" is created on page 7 and assigned, and the next run
  assigns the same unit without creating another one

#### Scenario: Creation disabled
- **WHEN** creation is disabled and no matching record exists
- **THEN** no record is created and the contract has no record of that kind
  afterwards, one assigned before included

#### Scenario: Creation without a storage page
- **WHEN** a site package enables creation without naming a storage page
- **THEN** the synchronisation refuses to run and names the mistake, before
  anything is written

### Requirement: Empty sources clear only mapped relations
The system SHALL clear a mapped relation when its source value is empty. It
MUST NOT change a relation that is not mapped.

#### Scenario: Function type removed in the source
- **WHEN** the function type is mapped and the frontend user's source column
  becomes empty
- **THEN** the contract no longer has a function type

#### Scenario: Relation not mapped
- **WHEN** the organisational unit is not mapped and an editor assigned one
- **THEN** synchronisation keeps the editor's organisational unit

### Requirement: The synchronisation leaves the employee type alone
The system SHALL NOT offer an employee type mapping. On TYPO3 v13 and v14, a
synchronisation SHALL keep the contract's employee type unless a project
listener sets it after the profile was mapped.

#### Scenario: Editor-assigned employee type
- **WHEN** an editor assigned an employee type to a synchronised contract
  and no project listener changes it
- **THEN** synchronisation keeps the editor's employee type
