## Why

The partner select of a partnership record offers hidden partner pages and
partner pages outside their start or end time on TYPO3 v13, and on TYPO3 v12
it does not. Nobody decided either: the item handler inherits a core default
that is on for a backend request on v13 and only behind a deprecated feature
toggle on v12. The spec of this branch pins the split as "the visibility they
had, per core version".

The split costs data on v12. A partnership whose partner page is hidden,
starts in the future or has expired compiles with no partner chosen, because
the partner field has no `foreign_table` and core does not restore a value
that is missing from the items. The browser submits the empty entry, and an
unchanged save writes the partner away (ACE-672). The test of the query behind
the select asserts the opposite of what v13 does, measured without any request
(ACE-671).

## What Changes

- The select offers every partner page that is not deleted, hidden ones and
  those outside their start or end time included, on TYPO3 v12 and v13 alike,
  because the item handler says so itself. v13 does not change.
- On v12, hidden and timed partners appear in the select for the first time,
  and a partnership keeps such a partner on save.
- The two `@todo` lines of the item handler go.
- The test of ACE-671 states the context it measures, and the guarantee of the
  select is tested where the select is built.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-partners/partner-selection`: which partners the select offers is a
  requirement of its own, the same on v12 and v13, instead of a note that it
  stays as it was per core version.

## Impact

- `academic_partners` (`packages/fgtclb/academic-partners`): the item handler
  of the partner field, its functional tests, a `Documentation/Changelog/2.4/`
  `Important` entry.
- `docs/architecture/backend-select-items.md`: which records a handler offers
  is decided by the handler, and the label of the item core adds for a value
  missing from the items.
- No stored value changes, so no migration.

## Non-goals

- Marking hidden or timed partners in the item label.
- The empty frontend item of a partnership whose partner is not visible
  (ACE-904).
- The order of the select, which ACE-669 settled.
- Partner pages restricted to frontend user groups.

## Source

Backport of the change of the same name on `main` (#950, #961), re-derived
from what branch `2` holds, see `design.md`, *Context*. The maintainer chose to
align v12 on 2026-10-13. Implements ACE-672 and ACE-671 for 2.4.0.
