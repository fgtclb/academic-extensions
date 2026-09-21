## Why

The partner map module of `academic_partners` carries two defects, both of
which the JavaScript test suite of ACE-706 can observe and both of which the
3.x line has already fixed.

**The map does not draw on a late module load.** The module binds its whole
body to `document.addEventListener('DOMContentLoaded', ...)` without asking
whether that event is still ahead of it. `f:asset.module` renders every module
with `async`, so it is not ordered against document parsing and regularly runs
after `DOMContentLoaded` has already fired — the listener is then registered on
an event that never comes again. The container stays empty, and nothing is
reported in the browser console or the TYPO3 log.

**A partner without coordinates is pinned at 0/0.** `Number('')` is `0` and not
`NaN`, so an absent coordinate walks straight through the `Number.isNaN()`
guard and is drawn in the open ocean off Africa.

## What Changes

- The map draws whether the module is evaluated before or after the document
  finished parsing.
- A partner whose coordinates are absent, unparseable or the pair 0/0 is
  skipped and reported on the console, as an unusable record already was. A
  single zero is kept — longitude 0 runs through four countries and latitude 0
  is the equator.
- Nothing else.

Behaviour is identical on TYPO3 v12 and v13: the module is version independent
and no core API is involved.

## Capabilities

### New Capabilities

- `academic-partners/partner-map`: which partners the map draws, and when it
  draws at all.

### Modified Capabilities

None.

## Impact

- One TypeScript source and its committed build artifact.
- Two `Documentation/Changelog/2.4/Important-*.rst` entries.
- The first behavioural test of `academic_partners`.
- No PHP, TCA, TypoScript or database change.

## Non-goals

- **The server half of the 3.x fix.** On `main`, ACE-562 is 42 files: besides
  this guard it adds a "drawable only" repository query, a
  `SynchronizePartnerCoordinatesUpgradeWizard`, a reworked `GeocodeCommand`
  with a write context, a TCA override, language labels, a `Map.html` change
  and a stub extension for a geocoding service. None of that is backported
  here. After this change a partner without coordinates is still *delivered* to
  the page and still counted in the list; only the map no longer draws it. The
  changelog entry says so, and the omission has no issue of its own yet.

## Source

Backport of the client half of two `main` fixes, re-derived here: ACE-546
("Draw the map on a late module load") and ACE-562 ("Only draw located
partners"). The file level diff found `map.ts` identical between the branches
apart from exactly these two places, so the diff applied is the same diff. The
behavioural test is the one `main` carries, with the comment about its
repository filter replaced: on this branch the module is the only guard there
is.
