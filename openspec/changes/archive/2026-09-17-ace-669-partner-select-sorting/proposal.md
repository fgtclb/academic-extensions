## Why

A partnership record names one partner, chosen from a select that offers every
partner page of the installation. That select declares no order, and the query
behind it declares none either, so an editor gets whatever order the database
returned — on PostgreSQL not reliably the same order twice. In an installation
with more than a handful of partners the entry to pick is found by scanning.

## What Changes

- The partner select of a partnership record lists its entries alphabetically
  by partner title, ascending, using the collation of the backend language.
- The placeholder entry stays at the top of the list.
- Nothing changes about which partners are offered, what a save stores, or how
  any list renders in the frontend.

## Capabilities

### New Capabilities

- `academic-partners/partner-selection`: what an editor sees when choosing the
  partner of a partnership record.

### Modified Capabilities

None.

## Impact

- `academic_partners` (`packages/fgtclb/academic-partners`): the shipped
  configuration of the partnership record's partner field.
- Editors of partnership records see the same entries in a different, findable
  order. No stored value changes, so no migration and no upgrade wizard.
- The behaviour is identical on TYPO3 v12 and v13, which this branch supports.
  The ordering mechanism is core functionality available since TYPO3 10.4, so
  no version switch is needed and no `Core12`/`Core13` split arises.
- A functional test of the compiled backend form, and a `Feature-` entry in
  `Documentation/Changelog/2.4/`.

## Non-goals

- Ordering the query behind the select. That the result of that query is
  unordered on this branch is a separate concern (ACE-491, which is on `main`
  only); this change orders what the editor sees, not what the query returns,
  and deliberately does not bring that work here.
- Sorting the other selects of the mono repository. The contract select of
  `academic_persons` and the country select of `academic_partners` already
  sort; the remaining ones are curated static lists whose order is deliberate.
- Changing the order of the partnership records themselves, which is arranged
  by hand and is a different control.
- Changing the role select of the same record, which already orders by name.
- Making the order configurable for integrators.

## Source

Backport of the change made on `main` for 3.0.0, re-derived from a file level
analysis rather than cherry-picked, and tracked under the same issue, ACE-669.
The project that asked for it runs 2.3.x, so this branch is where it arrives
first — with 2.4.0.
