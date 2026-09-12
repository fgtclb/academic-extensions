## Purpose

Lets integrators show only the most specific category of a hierarchy in the
facts of a program, so a parent category assigned for filtering is not
printed next to its child.

## ADDED Requirements

### Requirement: Integrators can show only the most specific category
The system SHALL offer a site setting, off by default, that leaves a category
out of a program's facts when another category of the same type assigned to
that program is its descendant. It SHALL apply to the program page, the
program details content element and the program card, on TYPO3 v13 and v14.

#### Scenario: Parent and child assigned
- **WHEN** the setting is on and a program carries the degrees "Bachelor" and
  its child "Bachelor of Science"
- **THEN** the degree fact shows "Bachelor of Science" only

#### Scenario: Unrelated categories of one type
- **WHEN** the setting is on and a program carries the locations "Campus A"
  and "Campus B", neither the ancestor of the other
- **THEN** the location fact shows both

#### Scenario: Chain of three assigned levels
- **WHEN** the setting is on and a program carries a category, its child and
  its grandchild of the same type
- **THEN** the fact shows the grandchild only

#### Scenario: Ancestor of another type
- **WHEN** the setting is on and a program carries a category whose parent
  belongs to another category type
- **THEN** both are shown, each under its own type

### Requirement: Showing all assigned categories stays the default
With the setting off, the facts SHALL show every category assigned to the
program, as before.

#### Scenario: Setting off
- **WHEN** the setting is not changed and a program carries "Bachelor" and
  "Bachelor of Science"
- **THEN** the degree fact shows both

### Requirement: Filtering is not affected
The setting MUST NOT change which programs a list filter finds or which
filter options are offered.

#### Scenario: Filter on the parent
- **WHEN** the setting is on and a visitor filters the list by "Bachelor"
- **THEN** the same programs are listed as with the setting off
