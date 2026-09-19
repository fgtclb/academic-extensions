## ADDED Requirements

### Requirement: A partner page keeps the partnership order arranged on it

The partnerships of a partner page SHALL render in the order an editor
arranged on that partner page. Saving any other record, a role that lists the
same partnerships included, MUST NOT change that order. This applies to TYPO3
v13 and v14 alike.

#### Scenario: Saving a role

- **WHEN** an editor arranges the partnerships of a partner page, and then
  saves a role that lists some of them in a different order
- **THEN** the partner page still renders its partnerships in the order
  arranged on the partner page

#### Scenario: A role keeps its own arrangement

- **WHEN** an editor rearranges the partnerships listed in a role and saves it
- **THEN** the role form shows them in that order the next time it is opened

#### Scenario: Updating an existing installation

- **WHEN** an installation updates and runs the upgrade wizards
- **THEN** partner pages and role forms show their partnerships in the order
  they showed before the update
