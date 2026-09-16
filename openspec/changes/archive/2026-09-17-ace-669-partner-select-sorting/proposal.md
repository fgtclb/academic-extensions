## Why

A partnership record names one partner, chosen from a select that offers every
partner page of the installation. That select declares no order, so an editor
gets whatever order the query returned. In an installation with more than a
handful of partners the entry to pick is found by scanning, not by reading.

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
- The behaviour is identical on TYPO3 v13 and v14, which this branch supports,
  and identical again on v12 and v13 on branch `2`. The ordering mechanism is
  core functionality available since TYPO3 10.4, so no version switch is
  needed.
- The starting point does differ per branch, which matters for the changelog
  wording rather than for the result: on this branch the select currently
  follows the page tree order of the partner pages, on branch `2` it follows
  whatever the database returns.
- A functional test of the compiled backend form, and a `Feature-` changelog
  entry.

## Non-goals

- Sorting the other selects of the mono repository. The contract select of
  `academic_persons` and the country select of `academic_partners` already
  sort; the remaining ones are curated static lists whose order is deliberate.
- Changing the order of the partnership records themselves. That order is
  arranged by hand and is a different control.
- Making the order configurable for integrators. A second way to order one
  select is not worth its configuration surface.
- Changing how any query orders its result. The frontend ordering of partners
  is ACE-491 and is neither part of nor affected by this change.
- Changing the role select of the same record, which already orders by name.

## Source

Requested by one project running 2.3.x, which cannot work around it locally
because the order is defined by shipped configuration. Tracked as ACE-669 and
delivered on both maintained branches, so the request is answered for an
upgrade to 2.4.0 as well as to 3.0.0.
