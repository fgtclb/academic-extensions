## Why

Two backend fields let an editor pick contracts: the contract of an
`academic_contacts4pages` contact record, and the "selected contracts" of the
`academic_persons` plugin. Both take their items from
`ContractItems::itemsProcFunc()`, which calls
`ContractRepository::getContractItemsForTcaItemsProcFunc()`. That method
ignores the form context it receives and returns `findAll()`, which lifts the
storage page restriction.

In an installation with several sites, an editor of one site is therefore
offered every contract of every site, labelled with the person's name, without
regard to which pages that editor may read. The only way to narrow it today is
a listener on `ModifyTcaSelectFieldItemsEvent`, which each project would have
to write itself.

Simply respecting the storage page does not work. Without an explicitly
configured storage page, Extbase falls back to page `0` and the select goes
empty without an error, which ACE-431 already recorded.

This is the backport of the `main` change. Multi-site installations on this
branch carry the same defect, and the code it touches is identical here.

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
- Documentation of the setting in `academic_persons`' `Documentation/`, with a
  changelog entry there and a shorter one in `academic_contacts4pages`, whose
  field the same handler serves.
- A `docs/` page on what an `itemsProcFunc` handler is handed and on the
  narrowing that drops a stored value, because that applies to all ten handlers
  of this repository rather than to this one.
- TYPO3 v12 and v13 alike.

## Capabilities

### New Capabilities

- `academic-persons/backend-contract-select`: which contracts the backend
  contract selects offer, and how an integrator restricts them.

### Modified Capabilities

None.

## Impact

`ContractItems`, the repository method behind it, their tests, the
`Documentation/` of `academic_persons` and a changelog entry in it and in
`academic_contacts4pages`. Functional tests are added in both of those
extensions, one per field, plus a unit test for the parsing. No schema or TCA
change. `ModifyTcaSelectFieldItemsEvent` keeps working unchanged, after the
new restriction.

One line outside all of that:
`academic_base`'s `GetSelectItemsForTcaManagedTableFieldMethodTrait` built
`$parameters['TSconfig']` as the whole page TSconfig tree, which is not the
shape FormEngine passes. It is an `@api` helper, so a project routing a
contract field through it would silently lose the new setting.

## Non-goals

- Restricting the select by default. A default would have to guess the
  storage layout, and a wrong guess empties the select.
- Applying the backend user's page permissions to the items. That is a wider
  question than this select.
- Hiding old or unpublished contracts in the select (ACE-51).

## Source

The backport of the `main` change of the same name, which was adopted from the
ACE-431 follow-ups rather than from the project differences analysis. Filed as
ACE-700 for both branches. Relates to ACE-431 and ACE-51.
