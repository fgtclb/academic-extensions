## Why

Two backend fields let an editor pick contracts: the contract of an
`academic_contacts4pages` contact record, and the "selected contracts" of the
`academic_persons` plugin. Both take their items from
`ContractItems::itemsProcFunc()`, which calls
`ContractRepository::getContractItemsForTcaItemsProcFunc()`. That method
ignores the form context it receives and returns `findAll()`, which lifts the
storage page restriction.

In an installation with several sites, an editor of one site is therefore
offered every contract of every site, labelled with the person's name,
without regard to which pages that editor may read. The only way to
narrow it today is a listener on `ModifyTcaSelectFieldItemsEvent`, which each
project would have to write itself.

Simply respecting the storage page does not work. Without an explicitly
configured storage page, Extbase falls back to page `0` and the select goes
empty without an error, which ACE-431 already recorded.

## What Changes

- `academic_persons` (`packages/fgtclb/academic-persons`): the contract select
  items honour an optional page TSconfig setting on the field,
  `TCEFORM.<table>.<field>.itemsProcFunc.storagePids`, a comma-separated list
  of pages, and `itemsProcFunc.recursive`, the depth below them. When set, only
  contracts stored on those pages are offered. When not set, the select offers
  what it offers today.
- A contract that the record already references is always offered, even
  outside the configured pages, so that saving the record never drops an
  existing relation.
- Documentation of the setting in the extension's `Documentation/`, and a
  changelog entry.
- TYPO3 v13 and v14 alike.

## Capabilities

### New Capabilities

- `academic-persons/backend-contract-select`: which contracts the backend
  contract selects offer, and how an integrator restricts them.

### Modified Capabilities

None.

## Impact

`ContractItems`, the repository method behind it, their tests, the
extension's `Documentation/` and one changelog entry. No schema or TCA
change. `ModifyTcaSelectFieldItemsEvent` keeps working unchanged, after the
new restriction.

## Non-goals

- Restricting the select by default. A default would have to guess the
  storage layout, and a wrong guess empties the select.
- Applying the backend user's page permissions to the items. That is a wider
  question than this select.
- Hiding old or unpublished contracts in the select (ACE-51, and
  `ace-tbd-honour-contract-publish-flag` / `ace-tbd-contract-display-policy`,
  which name the backend selectors as out of their scope too).

## Source

Named in ACE-431 ("worth deciding together") and left out of it on 2026-09-19,
because it decides which records a select offers, not their order. Not from
the project differences analysis; adopted into the same pipeline. No YouTrack
issue is filed yet; the change is renamed to
`ace-<NNN>-contract-select-storage-scope` when the issue is filed after
implementation. Relates to ACE-431 and ACE-51.
