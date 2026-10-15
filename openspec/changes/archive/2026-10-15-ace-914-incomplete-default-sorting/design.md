## Context

Verified on this branch (`2`, `b62f1d18e`) against the change made on
`main` in pull request #985, file by file:

- `academic-partners/Classes/Factory/DemandFactory.php`: guarded on `main` by
  ACE-914, unguarded here at `:35-39`.
- `academic-programs/Classes/Factory/DemandFactory.php`: guarded on `main` by
  ACE-91, together with its form fallback, unguarded here at `:38-42`.
- `PartnerDemand` and `ProgramDemand`: the sorting setters are the same on
  both branches. A field or a direction is applied only when the pair it
  forms is one of the offered orderings, otherwise the previous pair stays.
- `SortingOptions` of both extensions: a plain class on `main`, the core
  `Enumeration` here, with the same six values and the default `title asc`.

The unguarded code is
`[$field, $direction] = GeneralUtility::trimExplode(' ', $settings['sorting'])`.
A value of one part raises `Undefined array key 1` and then a `TypeError`
from `setSortingDirection(null)` under `strict_types`. A value that is not a
string raises a `TypeError` in `trimExplode()` on TYPO3 v13. On v12
`trimExplode()` casts it to a string, an array becomes `Array` with a
warning, and the value then fails like a field alone. All of it reproduces
on TYPO3 v12 and v13 with the unit tests copied from `main`.

## Goals / Non-Goals

**Goals:**

- The same guard in both factories, with the same shape as on `main`, so a
  later change to either factory diffs cleanly between the branches.

**Non-Goals:**

- The form fallback of ACE-91, see proposal.md.

## Decisions

### Port only the settings guard of ACE-91

Both factories get the private method `applySortingFromSettings()` of
`main`: it reads `settings.sorting` only when it is a string, splits it with
`GeneralUtility::trimExplode(' ', $sorting, true)` and applies field and
direction only when there are exactly two parts. Otherwise the demand keeps
the default it was built with. The method is byte-identical to the one on
`main` in both extensions.

The alternative, porting the whole ACE-91 change of the programs factory,
was rejected: its second half makes a submitted form without a sorting fall
back to the configured default, which is new behaviour of 3.0, documented as
such on `main`, and not needed to stop the error.

Field and direction stay two calls, so an unknown part is dropped by the
demand while the known one applies (`uid desc` sorts by title, descending).
That matches how the demand treats a sorting of the visitor, and changing it
would be a behaviour change beyond the defect.

### Unit tests rather than plugin tests

The two test classes of `main` are unit tests of the factories with stubbed
repositories, and they need nothing this branch lacks: `UnitTestCase`,
`createStub()` and the PHPUnit attributes all exist here. The partners class
is copied byte for byte. The programs class drops one docblock sentence that
points at a functional `DemandFactorySortingTest`, which ACE-91 added on
`main` and which does not exist here.

## Risks / Trade-offs

- [The tests do not render a content element] → The error and the fix are
  both inside the factory, whose result is what the controller hands to the
  repository unchanged. The functional suite still renders both plugins with
  valid settings on both core versions.
