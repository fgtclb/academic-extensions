## Purpose

Defines how an integrator sees what the persons settings file of each active
package changes, and obtains the smallest file that produces the same effect.

## ADDED Requirements

### Requirement: The status report names removed and omitted entries per package
The Reports module SHALL list, for every active package after the first one
that ships a persons settings file, the entries the package removes with `~`
and the upstream entries its copied maps leave out and therefore inherit. A map
counts as copied when the package restates at least two of its entries
unchanged, in any order and also where upstream has added to those entries
since, or when it is part of a copied map. A package that neither removes nor
leaves anything out of a copy SHALL NOT be listed. This applies on TYPO3 v13
and v14.

#### Scenario: Status of a copied map
- **WHEN** a package ships a copy of the `profile` map without the upstream
  field `middleName`
- **THEN** the persons settings status names the package and
  `profile.middleName` as an entry the package inherits
- **AND** the entry asks the integrator to decide on it

#### Scenario: Status of a copy made before an upstream addition
- **WHEN** a package ships a copy of the contract fields without the room,
  made before upstream added a help text to every field
- **THEN** the persons settings status names the room and each help text as
  entries the package inherits

#### Scenario: Status of a removal
- **WHEN** a package sets an upstream field to `~` and copies nothing
- **THEN** the persons settings status names the package and the removed field
  as information, with nothing to decide

#### Scenario: A delta is not reported
- **WHEN** a package names only the `validators` of one profile field, with a
  new value
- **THEN** the persons settings status lists nothing for that package

### Requirement: The migration command prints the smallest file per package
The settings migration command SHALL print, when asked for deltas, for every
active package after the first one that ships a persons settings file, only
the entries that differ from the packages loaded before it, with `~` for every
entry the package removes. Replacing the package's file with the printed
delta SHALL leave the effective settings unchanged, including the order of
their entries. The entries the package's copied maps leave out SHALL follow
as comments. The command SHALL NOT write any file, and SHALL exit successfully
in this mode.

#### Scenario: Copy shrinks to its delta
- **WHEN** a package ships a copy of `profile` that changes only the
  `gender` validators, and an integrator runs the command for deltas
- **THEN** the printed file contains the `gender` validators and nothing else
  of `profile`
- **AND** the upstream entries the copy left out are named as comments

#### Scenario: Reordering copy
- **WHEN** a package restates every contract field in another order
- **THEN** the printed file restates them in that order, so the order is kept

#### Scenario: Nothing to change
- **WHEN** a package's file repeats the upstream values only
- **THEN** the command names the package and states that the file can be
  dropped

### Requirement: The migration mode of the command is unchanged
Without the delta request, the settings migration command SHALL print the
migrated section maps of every package that still ships a pre-3.0 key and exit
with a failure while such a package exists, exactly as before.

#### Scenario: Legacy package without the delta request
- **WHEN** a package ships the pre-3.0 `validations` key and the command runs
  without the delta request
- **THEN** it prints the migrated maps for that package and exits with a
  failure
