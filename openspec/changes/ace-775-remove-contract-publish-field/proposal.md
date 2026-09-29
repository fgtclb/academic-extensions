## Why

Every contract of `academic_persons` (`packages/fgtclb/academic-persons`)
carries a `publish` toggle labelled "Show this contract online?", and no public
view has ever read it. Contracts meanwhile have a working visibility switch:
`hidden` is their enable column, every public view honours it, the list
plugins can still show hidden records for an internal directory, and the
frontend editor toggles it with the eye action of each contract. The `publish`
field is a second switch that promises what `hidden` already does, and
projects wrote code of their own around it.

## What Changes

- **BREAKING** The `publish` field is removed from contracts: the database
  column, the TCA column, the `Contract` model property and its accessors, the
  contract field of the persons settings and its labels.
- **BREAKING** The frontend editor of `academic_persons_edit`
  (`packages/fgtclb/academic-persons-edit`) no longer shows the "Publish"
  switch in the contract form, and its form data object loses the property.
  The eye action stays the one way to show or hide a contract.
- An upgrade wizard carries the meaning of `publish` into `hidden`: a contract
  that was not published is hidden. It is shipped **unregistered**, since on an
  installation that never gave `publish` a meaning every contract carries the
  default `0`, and running it there would hide all of them. A project that
  wants it registers it in the `Services.yaml` of its own site package.
- An installation without code of its own renders exactly what it rendered
  before: `publish` had no effect, and `hidden` is untouched.

The behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-persons/contract-visibility`: `hidden` is the only visibility of a
  contract, and a project migrates a `publish` flag of its own into it.
- `academic-persons-edit/contract-visibility-editing`: the contract form offers
  no publish switch, the eye action is the one visibility control.

### Modified Capabilities

None.

## Impact

- `academic_persons`: `ext_tables.sql`, TCA, model, `Settings.yaml`, labels,
  one new upgrade wizard, the development seed, tests and fixtures, a
  `Breaking-*.rst` and an `Important-*.rst`.
- `academic_persons_edit`: form data object, contract factory, controller,
  labels, tests and fixtures, a `Breaking-*.rst`.
- Project code that reads or writes the flag through the model, a raw query or
  a settings override breaks and has to move to `hidden`. A `DataHandler`
  write of `publish` is dropped without an error, since the column is gone from
  the TCA.
- The database compare offers the column for removal. The wizard reads it
  under either name, `publish` or `zzz_deleted_publish`.
- One more call site of the v15 blocking upgrade wizard API (ACE-294).

## Non-goals

- Registering the wizard by default, or asking for confirmation instead. Both
  offer it to every installation.
- A replacement setting to show hidden contracts in more places than the
  existing "show hidden records" option reaches.
- Partners: `PartnerItems.php` carries a comparable `@todo` about a publish
  property, which stays.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`persons-display-04`) and redefined by the maintainer on 2026-09-29: remove
the field in favour of `hidden` rather than honour it. Implements ACE-775,
filed for this change.

Relates to ACE-524.
