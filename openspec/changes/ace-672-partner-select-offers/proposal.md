## Why

The partner select of a partnership record offers hidden partner pages, and
partner pages outside their start or end time, on TYPO3 v13 and v14. Nobody
decided that. The item handler inherits it from a core default that applies
only when the global request is a backend one, and the handler still carries
two `@todo` lines asking the question. A test of the query behind the select
asserts the opposite, measured in a context the select never runs in
(ACE-671), so a change of the behaviour would not be reported.

## What Changes

- The select offers every partner page that is not deleted, hidden ones and
  those outside their start or end time included, because the item handler
  says so itself rather than inheriting a core default. What an editor sees
  on TYPO3 v13 and v14 does not change.
- The two `@todo` lines of the item handler go. The first is answered by this
  change. The second asks about a publish property that partner pages do not
  have on either branch.
- The test of ACE-671 states the context it measures, and the guarantee the
  select gives is tested where the select is built.

**Decision for the maintainer**: the policy itself. The recommendation is to
keep offering every partner page that is not deleted, as core's own record
selects do. See `design.md`.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-partners/partner-selection`: which partners the select offers is
  now a requirement of its own, covering hidden partners and partners outside
  their start or end time, instead of a note that it stays as it was.

## Impact

- `academic_partners` (`packages/fgtclb/academic-partners`): the item handler
  of the partner field, its functional tests, a `Documentation/Changelog/3.0/`
  `Important` entry.
- `docs/architecture/backend-select-items.md`: which records a handler offers
  is decided by the handler.
- TYPO3 v13 and v14 behave the same before and after. No stored value
  changes, so no migration.

## Backport

ACE-672 and ACE-671 are `[3.x][2.x]`. Branch `2` gets a change of its own,
derived from a backport analysis. The item handler and the test of ACE-671
are the same there apart from the item icon, but the behaviour is not: TYPO3
v12 does not offer hidden partners, because the core default is behind a
feature toggle there, and the branch `2` spec pins that split. Aligning v12
adds hidden and timed partners to its select, which needs an `Important`
entry in `Changelog/2.4/`. The alternative is to close the branch `2` part
as won't fix. Both are a **decision for the maintainer**, see `design.md`.

## Non-goals

- Marking hidden or timed partners in the item label. Worth doing, and a
  small feature of its own.
- Any frontend query of partners or partnerships.
- The order of the select, which ACE-669 settled.
- Partner pages restricted to frontend user groups. The select never limited
  them, on any core version.
