## Purpose

Defines how the persons settings files that several active packages ship are
combined into the effective settings, and how an integrator sees the result.

## ADDED Requirements

### Requirement: A package changes a single entry without copying its map
The system SHALL merge the persons settings files of all active packages per
entry, in package loading order. An entry a later package does not mention
MUST keep the value of the earlier package. This applies on TYPO3 v13 and v14.

#### Scenario: Only the gender validators change
- **WHEN** a site package's settings file contains only the `gender` field of
  `profile` with an empty `validators` list
- **THEN** the gender field is no longer required, and every other profile
  field, the section layout and the detail layout keep their upstream values

#### Scenario: A contract field is added
- **WHEN** a site package adds one field below `contracts.fields`
- **THEN** the upstream contract fields remain, and the new field follows them

### Requirement: Lists are replaced as a whole
A list in a later package, such as a field's `validators`, a structure column,
a detail list, `rowFields` or `actions`, SHALL replace the earlier list
instead of being appended to it.

#### Scenario: Validators replaced
- **WHEN** upstream declares `validators: [required]` for a field and a site
  package declares `validators: [readonly]` for it
- **THEN** the field is read-only and not required

### Requirement: A null value removes an upstream entry
An entry set to `~` (YAML null) in a later package SHALL remove the entry of
the earlier package, whether it is a field, a section or a layout entry.

#### Scenario: Field removed
- **WHEN** a site package sets `middleName: ~` below `profile`
- **THEN** the middle name field is not offered and not rendered

### Requirement: Entry order follows a complete restatement
A merged map SHALL keep the earlier package's order of entries and append
new entries after them. When a later package restates every entry of a map,
the merged map SHALL take the later package's order. An empty map in a later
package SHALL replace the entry it stands for with an empty value.

#### Scenario: Complete restatement in another order
- **WHEN** a site package restates every field of `contracts.fields` in a
  different order
- **THEN** the effective contract fields follow the site package's order

#### Scenario: Empty map
- **WHEN** a site package sets a field below `profile` to `{}`
- **THEN** the effective settings carry that field with an empty definition
  instead of the upstream one

### Requirement: Omitted upstream entries come back
A package that ships a copy of a map without some upstream entries SHALL see
those entries in the effective settings. This is a **breaking** change from
the replacement of whole top-level maps.

#### Scenario: Copy without a field
- **WHEN** a site package ships a copied `contracts` map without the
  upstream field `streetNumber`
- **THEN** the effective settings contain `streetNumber` until the package
  sets it to `~`

### Requirement: Pre-3.0 keys follow the same merge
The pre-3.0 top-level keys `validations` and `profileInformationsTypes` SHALL
be merged per entry like every other map, and SHALL still reach the effective
settings through the legacy handling until 4.0.

#### Scenario: Two packages ship legacy validations
- **WHEN** two packages ship a `validations` map, and the later one names only
  one field
- **THEN** the earlier package's entries for the other fields still apply, and
  the named field uses the later package's value

### Requirement: Integrators can see and minimise their overrides
The Reports module SHALL list, per package, the entries it removes and the
upstream entries its maps omit. The settings migration command SHALL print,
on request, for every package the minimal settings file that reproduces the
package's current effective settings.

#### Scenario: Status of a copied map
- **WHEN** a package ships a copied map that omits upstream entries
- **THEN** the persons settings status entry names the package and each
  omitted entry

#### Scenario: Minimal file
- **WHEN** an integrator runs the migration command in delta mode
- **THEN** it prints, for each package, only the entries that differ from the
  earlier packages, with `~` for each entry the package omitted
