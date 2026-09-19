## ADDED Requirements

### Requirement: A profile keeps the contract order arranged on it

The contracts of a profile SHALL render in the order an editor arranged on
that profile. Saving any other record, an organisational unit that lists the
same contracts included, MUST NOT change that order. This applies to TYPO3 v13
and v14 alike.

#### Scenario: Saving an organisational unit

- **WHEN** an editor arranges the contracts of a profile, and then saves an
  organisational unit that lists some of them in a different order
- **THEN** the profile still renders its contracts in the order arranged on
  the profile

#### Scenario: An organisational unit keeps its own arrangement

- **WHEN** an editor rearranges the contracts listed in an organisational unit
  and saves it
- **THEN** the unit form shows them in that order the next time it is opened

#### Scenario: Updating an existing installation

- **WHEN** an installation updates and runs the upgrade wizards
- **THEN** profiles and organisational unit forms show their contracts in the
  order they showed before the update
