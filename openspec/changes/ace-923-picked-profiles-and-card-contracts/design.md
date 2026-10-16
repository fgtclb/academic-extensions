## Context

`ProfileController::adoptSettings()` copies `tt_content.pages` into the demand
of the list and list-and-detail actions. `ProfileRepository::applyDemandSettings()`
turns those storage pages into the storage page ids of the query, and
`resolveDemandForQuery()` replaces only the constraint for a demand with a
profile list (`uid IN (...)`), so the storage pages stay. The card builds a
fresh `ProfileDemand` without storage pages, which `applyDemandSettings()`
answers with `setRespectStoragePage(false)`, and the selected profiles action
reads through `findByUids()`, which switches the storage page off itself.

The repository files touched by the `main` change differ between the branches,
but the selection branch of `resolveDemandForQuery()` and the order in which
`findByDemand()` calls `applyDemandSettings()` and `resolveDemandForQuery()`
are the same on both.

## Goals / Non-Goals

**Goals:**

- One rule for every element that picks profiles by hand: the pick is not
  filtered by storage pages.

**Non-Goals:**

- No change to the selection by uid itself, its ordering in PHP, its
  pagination or the events around the query.
- The card part of the `main` change. The card of this branch never reads its
  unit and function type fields and has no contract restriction option.

## Decisions

**The profile list branch switches the storage page off.** In
`resolveDemandForQuery()`, the branch for a profile list calls
`setRespectStoragePage(false)` next to the language handling it already sets
up. It runs after `applyDemandSettings()`, so it wins over the storage pages of
the demand for exactly this query, while enable fields, hidden records, the
language aspect and the workspace stay as `applyDemandSettings()` and Extbase
set them. The query settings API is the same on TYPO3 v12 and v13. This branch
has no letter query and no cache lifetime query built from the demand, so the
list query is the only one affected. Rejected: clearing the storage pages of
the demand in the controller, which would hand listeners of the demand and list
events a demand that no longer says what the element is configured with, and
would need the same in every caller of `findByDemand()`.

## Risks / Trade-offs

- [A list relied on its Startingpoint to hide picked profiles] → The 2.4
  `Important` entry and the upgrade guide say so, and the fix is to remove the
  profile from the selection.
- [A listener of `ModifyProfileQueryEvent` read the storage page ids of a
  selection query] → They are still set on the query settings, only no longer
  respected. The event documentation lists the storage folders among the
  conditions the extension builds, which stays true for a list without a
  selection.
