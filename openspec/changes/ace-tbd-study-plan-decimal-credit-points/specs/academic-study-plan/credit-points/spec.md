## Purpose

Defines how editors enter and how visitors see the credit points of study plan
semesters and modules.

## ADDED Requirements

### Requirement: Editors can enter decimal credit points
The credit points of a semester and of a module SHALL accept non-negative
numbers with up to two decimals, on TYPO3 v13 and v14, and SHALL store them
without rounding.

#### Scenario: Module worth two and a half credit points
- **WHEN** an editor enters 2.5 as the credit points of a module and saves
- **THEN** the module stores 2.50 credit points

#### Scenario: More than two decimals
- **WHEN** an editor enters 2.555
- **THEN** the value is stored with two decimals, as the backend number field
  does for every decimal field

### Requirement: Visitors see credit points without trailing zeros
The study plan SHALL render credit points without trailing decimal zeros, in
the default template and in template overrides that print the value.

#### Scenario: Fractional value
- **WHEN** a module stores 2.50 credit points
- **THEN** the study plan shows "2.5"

#### Scenario: Whole value
- **WHEN** a semester stores 30.00 credit points
- **THEN** the study plan shows "30"

### Requirement: Existing values survive the update
Credit points stored before the update SHALL keep their value after the
database compare.

#### Scenario: Integer value before the update
- **WHEN** a module stored 5 credit points before the update and the
  database compare has run
- **THEN** the module stores 5.00 and the study plan shows "5"
