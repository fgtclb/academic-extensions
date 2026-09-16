## Context

See `proposal.md` for the motivation. Verified on `main` at `87b6d54bc`, and on
`origin/2` where it says so:

- The partner field of the partnership record is a single select whose entries
  are produced by an `itemsProcFunc`, which turns every partner page into one
  entry. The field's own configuration declares one empty placeholder entry and
  no order.
- That handler is used by the form engine only — it has no second caller, so
  ordering the rendered select is the whole job.
- `PartnerRepository::findAll()`, the query behind it, **differs between the
  branches**: on `main` it orders by the page tree `sorting` with `uid` as
  tiebreaker (ACE-491, documented in the 3.0 changelog), on `origin/2` it
  declares no ordering at all. The branch `2` test fixture for that method has
  no `sorting` column, which is the same fact from the other side. So the
  select is in page tree order here and in database order there — on
  PostgreSQL not even stably so.
- Core applies the select's declared order **after** the `itemsProcFunc`, on
  every version both branches support: on TYPO3 v12 as the last step of
  `TcaSelectItems::addData()`, and on v13 and v14 through the separate select
  item processor invoked at that same point. The feature exists since TYPO3
  10.4.
- Core orders with an ICU collator bound to the backend language. `ext-intl` is
  a hard requirement of `typo3/cms-core`, so that cannot be unavailable.
- The two sibling selects that already sort do it in PHP: the contract select
  of `academic_persons` with a spaceship comparison of last and first name, the
  country select of `academic_partners` with `asort(SORT_LOCALE_STRING)`.

## Goals / Non-Goals

**Goals:**

- One declarative ordering, identical on all four supported core versions.
- A test that fails without the change, through the path the editor uses.

**Non-Goals:**

- Touching the query, the handler, or what either returns.
- Ordering any other select (see the proposal's non-goals).

## Decisions

### Declare the order on the field, not in the item handler

The field declares its sort order, and core applies it after the handler has
produced the entries. One declaration, no PHP, and the ordering is the one core
uses everywhere else.

Rejected: sorting inside the item handler, the way the contract and country
selects do. It would work, but it hard-codes a comparison in PHP where a
declaration exists, and the two existing PHP comparisons are exactly the reason
not to copy them: a spaceship comparison of strings and `SORT_LOCALE_STRING`
both order `Ö` after `Z`, while the collator core applies orders it next to
`O`. For German partner titles that is the difference between a findable list
and a puzzling one.

### Test the compiled form, not the item handler

The test compiles the backend form of a partnership record the way the form
engine does when an editor opens it, and asserts the order of the entries it
produced. `academic-base` has this harness already, and it exists on both
branches.

Rejected: a test that calls the item handler directly, which is the shape the
existing item handler tests have. **It would pass without the change and prove
nothing**, because core applies the order after the handler returns — the
handler's own output is unordered before and after. This is the trap this
design exists to name.

### Fixture titles are anti-correlated with both orders

The partner pages of the fixture get titles whose alphabetical order matches
neither their `uid` order nor their page tree `sorting` order. Without that, a
test can pass for the wrong reason on one branch and not the other, since the
two branches start from those two different orders.

### The changelog entry goes to the 2.4 folder on both branches

The feature ships first in 2.4.0, and `Documentation/Changelog/2.4/` exists on
both branches with a `Feature-*` glob in its index. Consequence, accepted
deliberately: on `main` the entry is not listed under 3.0, and a 3.x reader
finds it under the v2 changelog — which is accurate, because 2.4.0 is the
version that introduces it.

### The backport is a change of its own on branch `2`

Specs are branch scoped. Branch `2` gets its own change, re-derived from a file
level diff rather than a cherry-pick. The measurement is already done and the
adaptation is expected to be none for the configuration itself: the
configuration file and the item handler are byte-identical across the branches,
and the ordering mechanism predates every supported core version. What does
differ is the starting order described in Context, and therefore the changelog
wording.

## Risks / Trade-offs

- [The placeholder entry is sorted along with the partners] → An empty label
  collates before any non-empty one, so it stays first. The spec requires it
  and a scenario of the test pins it, rather than leaving it to that argument.
- [The order depends on the backend language] → The test sets a known language
  so the expectation is stable; the diacritic scenario is chosen to hold for
  both the default and a German backend.
- [The branch `2` starting order is database dependent] → The test asserts the
  complete expected order rather than a relative position, so it cannot pass by
  coincidence on SQLite and fail on PostgreSQL.
- [An editor relied on the page tree order of the select] → Named in the
  changelog entry. The page tree order was never an intended order of this
  select; it was the order of the query behind it.

## Migration Plan

None. No stored data and no configuration of an installation is affected; the
new order applies the next time an editor opens a partnership record.

## Open Questions

None.
