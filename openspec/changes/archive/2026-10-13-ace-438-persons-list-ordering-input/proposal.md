## Why

The persons list of `academic_persons` (`packages/fgtclb/academic-persons`)
mishandles two kinds of ordering input on branch `2` exactly as it did on
`main` before #959. An empty entry in the configured sort or group values,
from an emptied setting, a trailing comma or a doubled comma, makes "none" an
allowed ordering, and every list left on "none" then orders by a column
without a name, which the database rejects (ACE-438). A sort direction other
than exactly `asc` or `desc`, such as `DESC` from a site setting or an event
listener, drops the requested ordering and the list comes back in creation
order (ACE-439). Both issues are filed for 2.4.0.

## What Changes

- Empty entries of the configured sort, group and contact type values are
  ignored, so neither the list nor a backend select offers an item without a
  value.
- An entry without a label is labelled with its value, and an entry without a
  value is ignored, instead of raising a PHP warning.
- When the sort or group configuration is not declared or holds no text, the
  list still renders and the backend offers only "none".
- A sort direction is accepted in any letter case, and an unknown direction
  keeps the previous one, `asc` by default, so the field ordering stays.

The behaviour is the same on TYPO3 v12 and v13.

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
  reading of the sort and group configuration, and the sort direction setter
  of the list demand.
- No schema, TCA, template or dependency change, no upgrade wizard.
- Unit tests that pin the empty item today change with the fix.
- Two `Important` entries in `Changelog/2.4/`, and the same correction of the
  manual as on `main`.
- Two commits, one per issue, each resolving its key.

## Non-goals

- A fallback for an unknown sort field. It keeps the uid order, as decided
  for `main`.
- Turning the free text sort direction site setting into a choice.
- Catching `SettingsWriteException` of an unwritable settings file.

## Source

Backport of the change of the same name on `main` (#950, #959), re-derived
from what branch `2` holds, see `design.md`, *Context*. Implements ACE-438 and
ACE-439 for 2.4.0, and with ACE-438 item 3 of ACE-434.
