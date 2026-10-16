## Context

`ProfileController::adoptSettings()` copies `tt_content.pages` into the demand
of the list and list-and-detail actions. `ProfileRepository::applyDemandSettings()`
turns those storage pages into the storage page ids of every query, and
`resolveDemandForQuery()` replaces only the constraint for a demand with a
profile list (`uid IN (...)`), so the storage pages stay. The card builds a
fresh `ProfileDemand` without storage pages, and the selected-profiles action
reads through `findByUidsWithContext()`, which switches the storage page off.

The contracts a profile item shows are chosen by `ContractsViewHelper` from
`ContractSelection::fromPluginSettings()`, which applies
`settings.organisationalUnits` and `settings.functionTypes` whenever
`settings.contracts.matchFilter` is set. The card shares the FlexForm of the
list, so a list switched to a card keeps those values.

## Goals / Non-Goals

**Goals:**

- One rule for every element that picks profiles by hand: the pick is not
  filtered by storage pages.
- The card applies no unit or function type restriction to contracts,
  whatever its FlexForm still stores.

**Non-Goals:**

- No change to the selection by uid itself, its ordering in PHP, its
  pagination or the events around the query.

## Decisions

**The profile list branch switches the storage page off.** In
`resolveDemandForQuery()`, the branch for a profile list calls
`setRespectStoragePage(false)` next to the language handling it already sets
up. It runs after `applyDemandSettings()`, so it wins over the storage pages of
the demand for exactly this query, while enable fields, hidden records, the
language aspect and the workspace stay as `applyDemandSettings()` and Extbase
set them. `findAlphabetFilterLetters()` returns before it queries for a profile
list, and `findNextContractValidityChange()` returns early for one as well, so
neither needs a change. Rejected: clearing the storage pages of the demand in
the controller, which would hand listeners of the demand and view events a
demand that no longer says what the element is configured with, and would
need the same in every caller of `findByDemand()`.

**The card drops `contracts.matchFilter` in `initializeCardAction()`.** The
initialize method runs before `resolveView()` assigns the settings to the
view, on TYPO3 v13 and v14 alike, so the template and `persons:contracts` never
see the value. The stored units and function types stay in the settings and
select nothing without it. Rejected: deciding in `ContractsViewHelper` by
`data.CType`, which would put knowledge of one content type into a ViewHelper
that partials of other extensions call. Also rejected: removing the values
from the FlexForm on save, which would lose them for an element switched back
to a list.

## Risks / Trade-offs

- [A list relied on its Startingpoint to hide picked profiles] → The 2.4
  `Important` entry and the manual say so, and the fix is to remove the profile
  from the selection.
- [A listener of `ModifyProfileQueryEvent` read the storage page ids of a
  selection query] → They are still set on the query settings, only no longer
  respected. The event documentation lists the storage folders among the
  conditions the extension builds, which stays true for a list without a
  selection.
