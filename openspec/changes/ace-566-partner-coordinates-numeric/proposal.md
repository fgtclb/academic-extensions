## Why

A partner page stores its latitude and longitude as text, while the partner
object types them as numbers that default to `0`. Since ACE-562 three rules
decide whether a partner can be drawn, and they disagree: the map query, the
partner object and the map script. A hand-entered `0.0` pair reaches the page
and leaves an empty map instead of the empty state, a pair with one coordinate
missing counts as drawable for a single partner, and a decimal comma such as
`52,52` is stored as entered, dropped by the map script and read as `52` by the
partner object. The backend field accepts any text.

## What Changes

- `academic_partners` (`packages/fgtclb/academic-partners`): both coordinates of
  a partner page are stored as numbers, and "no coordinate" is stored as empty,
  never as `0`. **BREAKING**: the database columns and their names change, see
  `design.md`.
- **BREAKING**: the partner object reports a missing coordinate as `null`
  instead of `0`.
- One rule decides whether a partner can be drawn: both coordinates present,
  within the valid range, and not the pair `0/0`. The partner map content
  element, the map partial for a single partner and the map script apply the
  same rule.
- The backend field validates what an editor enters: a coordinate out of range
  or not a number is not saved, an emptied field stores "no coordinate", and the
  value is rounded to the stored precision on every database.
- An upgrade wizard copies the stored coordinates into the new columns and
  reports every value it had to convert or could not read.
- Behaviour is identical on TYPO3 v13 and v14.

The column type, the precision, the handling of a decimal comma and the
migration route are decisions for the maintainer, see `design.md`.

## Capabilities

### New Capabilities

- `academic-partners/partner-coordinates`: what a partner coordinate is, how an
  editor enters it, which partners can be drawn on a map, and what the update
  does with stored values.

### Modified Capabilities

None. Existing requirements that name partners "without coordinates" keep
their wording, the new capability defines the term.

## Impact

- `academic_partners`: schema, TCA, model, repository, geocoding command,
  both upgrade wizards, map script, manual, a `Breaking` changelog entry.
- 28 functional test fixtures store an empty string as a coordinate, which a
  numeric column refuses on PostgreSQL and on MariaDB/MySQL in strict mode.
- `packages-dev/dev-site`: both seed manifests and both database templates.
- `docs/`: frontend assets, list plugin events, database queries.
- Projects that read the columns by name or call the coordinate getters.

## Non-goals

- The empty `geocode_status` of ACE-437, and retrying failed geocoding. A change
  of its own, see `design.md`.
- The precision of the map centre in the site settings editor (ACE-769).
- A backport to branch `2`. Its partner map stays as ACE-709 left it.

## Source

ACE-566, split out of ACE-562, which fixed the visible marker at 0/0 without
the schema change. Relates to ACE-437, ACE-562 and ACE-709.
