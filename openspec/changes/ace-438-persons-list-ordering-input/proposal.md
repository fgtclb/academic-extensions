## Why

The persons list of `academic_persons` (`packages/fgtclb/academic-persons`)
mishandles two kinds of ordering input. An empty entry in the configured sort
or group values, from an emptied setting, a trailing comma or a doubled comma,
makes "none" an allowed ordering, and every list left on "none" then orders by
a column without a name (ACE-438). A sort direction other than exactly `asc`
or `desc`, such as `DESC` from a site setting or an event listener, drops the
requested ordering and the list comes back in creation order (ACE-439). The
program list of `academic_programs` keeps its previous valid ordering on
unknown input (ACE-625), so the two lists disagree.

## What Changes

- Empty entries of the configured sort, group and contact type values are
  ignored, so neither the list nor a backend select offers an item without a
  value.
- An entry without a label is labelled with its value, and an entry without a
  value is ignored, instead of raising a PHP warning.
- When the sort or group configuration cannot be read, the list still renders
  and the backend offers only "none".
- A sort direction is accepted in any letter case, and an unknown direction
  keeps the previous one, `asc` by default, so the field ordering stays.

The behaviour is the same on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-persons/profile-list-ordering`: the ordering the persons list
  applies for the configured sort field, group field and direction, and how
  it treats empty and unknown values.
- `academic-persons/contact-type-options`: the items the type selects of
  email addresses, phone numbers and addresses offer from the extension
  configuration.

### Modified Capabilities

None.

## Impact

- `academic_persons` only: the parser of the configured value lists, the
  reading of the sort and group configuration, and the sort direction of the
  list demand, which is marked `@api`.
- No schema, TCA, template or dependency change, no upgrade wizard.
- Unit tests that pin the empty item today change with the fix.
- Two `Important` entries in `Changelog/3.0/`, and a correction of the manual,
  which shows the group values as the default of the sort values.
- Two commits, one per issue, each resolving its key (see `design.md`).
- Backport to branch `2` as a change of its own. The parsers and the setter
  are identical there, the demand is not `@api`, and the v12 configuration
  read has to be checked there.

## Non-goals

- A fallback for an unknown sort field, such as a renamed allow list entry.
  It keeps falling back to creation order (decision for the maintainer in
  `design.md`).
- Turning the free text sort direction site setting into a choice.
- The early return of the parser once a list is loaded, and the last label
  winning for a repeated value. Both are pinned and unrelated.
- A sort order chosen by the visitor.

## Source

Board triage of 2026-10-12. Implements ACE-438 and ACE-439, and with them
item 3 of ACE-434. Relates to ACE-625, ACE-365 and ACE-482.
