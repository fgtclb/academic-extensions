## Context

See `proposal.md` for the motivation. This is the branch `2` side of ACE-669;
the `main` side is the change of the same name there. The full file level
analysis is `.agent/reports/project-differences-2026-09/BACKPORT-669-partner-select.md`.
What it measured, with `origin/2` against `origin/main`:

| File                                           | Result                                                   |
|------------------------------------------------|----------------------------------------------------------|
| the partnership record's TCA                   | **byte-identical**                                       |
| the item handler behind the partner field      | **byte-identical**                                       |
| `PartnerRepository`                            | differs — the ordering work of ACE-491 is on `main` only |
| the partners test base class                   | differs — `main` additionally loads EXT:install          |
| the form data provider test of `academic_base` | differs, same shape                                      |

So the defect and the fix are the same, and the change is re-derived rather
than cherry-picked for three reasons only: the starting order, the test, and
the changelog wording.

Verified on this branch:

- The partner field is a single select whose entries are produced by an item
  handler, one per partner page, with one empty placeholder entry and no order.
  The handler has no caller besides the form engine.
- The query behind it declares **no ordering at all** here, so the select is in
  whatever order the database returns. On PostgreSQL that is not stably the
  same order twice. On `main` the same query orders by the page tree sorting
  (ACE-491), which is why the two branches start from different orders.
- Core applies a select's declared order **after** the item handler has
  produced the entries, on TYPO3 v12 as the last step of
  `TcaSelectItems::addData()` and on v13 through the select item processor
  invoked at that same point. The mechanism exists since TYPO3 10.4, so both
  supported versions have it and no `Core12`/`Core13` split arises.
- Core orders with an ICU collator bound to the backend language; `ext-intl` is
  a hard requirement of `typo3/cms-core`.
- Declaring this order is the first use of that TCA key in the mono repository
  on either branch. A grep appears to find prior art, but those hits are a PHP
  method of the same name in `academic_persons_edit` — not the configuration
  key.

## Goals / Non-Goals

**Goals:**

- The same observable behaviour as on `main`, from one declaration.
- A test that was shown to fail **on this branch**, not one whose red proof was
  inherited from `main`.

**Non-Goals:**

- Bringing the ACE-491 query ordering to this branch.
- Touching the item handler or what it returns.

## Decisions

### Declare the order on the field, not in the item handler

The field declares its sort order and core applies it after the handler has
produced the entries. One declaration, no PHP, identical on v12 and v13.

Rejected: sorting inside the item handler, as the contract and country selects
of this branch do. It hard-codes a comparison in PHP where a declaration
exists, and those two comparisons are the reason not to copy them: a spaceship
comparison and `asort(SORT_LOCALE_STRING)` both order `Ö` after `Z`, while the
collator core applies orders it next to `O`.

### Test the compiled form, not the item handler

The test compiles the backend form of a partnership record the way the form
engine does when an editor opens it, and asserts the order of the entries.

Rejected: a test calling the item handler directly, which is the shape the item
handler tests of this branch have. **It would pass without the change and prove
nothing**, because the order is applied after the handler returns.

### Model the test on this branch's harness, not on the one from `main`

`academic_base` has the form data compiler harness here too, but its copy
differs from the one on `main`. This branch's copy is the model. Two further
branch facts: `academic_partners` has no test directory for form engine tests
on either branch, so the test creates it; and the partners test base class here
does not load EXT:install, so a test needing more says so on the test class
rather than on the base.

### Fixture titles are anti-correlated with the stored order

The fixture's partner titles are in a different order than their record order,
so the assertion cannot be satisfied by the order the database happens to
return. The existing fixture of the query on this branch has no sorting column
at all — a reminder that the page tree order which `main` starts from does not
exist here.

### The changelog entry names this branch's previous order

`Documentation/Changelog/2.4/`, which is this branch's current version folder.
The text says the previous order was the one the database returned, **not** the
page tree order — that sentence is true only on `main`, and copying it would
make the entry wrong.

## Risks / Trade-offs

- [The placeholder entry is sorted along with the partners] → An empty label
  collates before any non-empty one, so it stays first. A scenario pins it
  rather than leaving it to that argument.
- [The previous order was database dependent] → The test asserts the complete
  expected order rather than a relative position, so it cannot pass by
  coincidence on SQLite and fail on PostgreSQL. The functional suite runs on
  PostgreSQL for this extension.
- [The red proof is taken on `main` and assumed here] → Explicitly a task. The
  proof is taken on this branch by mutation, with the file backed up and
  compared by checksum; `git checkout --` cannot restore an uncommitted change.
- [An editor relied on the previous order] → There was no reliable previous
  order to rely on; the changelog entry says so.

## Migration Plan

None. No stored data and no configuration of an installation is affected.

## Open Questions

None.
