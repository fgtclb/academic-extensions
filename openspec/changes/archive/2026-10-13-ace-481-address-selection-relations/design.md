## Context

Read on `origin/2` `6b4905c69` against TYPO3 12.4.45 and 13.4.36.

- `academic-contact4pages/Configuration/TCA/tx_academiccontacts4pages_domain_model_contact.php`:
  `email_address` (line 178), `phone_number` and `physical_address` are
  `select`/`selectSingle` with the static items `0` ("display all") and `-1`
  ("display none"), an `itemsProcFunc` of `AddressRecordItems`, `l10n_mode`
  `exclude` and no `foreign_table`, as on `main` before ACE-481.
- `Classes/Backend/FormEngine/AddressRecordItems.php` is byte-identical to
  `main` before ACE-481: a query with a `DeletedRestriction`, the contract
  from the row or the inline parent, labels with the type and a hidden marker,
  `ModifyTcaSelectFieldItemsEvent` dispatched last.
- The address tables, the contract and the profile of `academic_persons` are
  not workspace aware on this branch. The contact table is.
- The contract's inline relation to its page contacts (`foreign_field`
  `contract`, `foreign_sortby` `contract_sorting`) is the same as on `main`.
- Core, identical on v12 and v13 for what this change relies on:
  `DataHandler::isReferenceField()` treats a `select` as a relation only with
  `foreign_table`, `remapListedDBRecords_procDBRefs()` honours
  `dontRemapTablesOnCopy`, `processCmdmap_afterFinish` runs before the
  outermost `DataHandler` updates the reference index, and
  `AbstractItemProvider` hands the items to an `itemsProcFunc` as
  `SelectItem` objects on both versions.

## Goals / Non-Goals

**Goals:**

- Copies select the copied addresses, translations the default language ones.
- The reference index and the relation aware form handling from core.
- The same behaviour as `main` where the branches share it.

**Non-Goals:**

- Workspace handling, `showIfEmpty`, the contract select, ACE-907.

## Decisions

### 1. The same declaration as `main`, without `showIfEmpty`

`foreign_table`, the scoped `foreign_table_where` (every column qualified,
the `ORDER BY` without `{#…}`, the contract compared as text so an empty
marker does not break PostgreSQL) and `dontRemapTablesOnCopy`. `showIfEmpty`
is left out, v12 and v13 never remove an empty relation select.

### 2. `dontRemapTablesOnCopy` and the copy hook travel

The versioning remap that made `main` need them cannot happen here, because
the address tables are not versioned. The translation remap can: localizing a
contract translates its addresses and its page contacts in one command, and
without `dontRemapTablesOnCopy` core rewrites the contact translation's
selection to the translated address (measured on `main`). The hook is the one
of `main`, in this branch's style: a `final class` with a `private readonly`
constructor property, public in `Services.yaml`. It checks for a translation
only: the address tables have no `t3ver_oid` column on this branch, and their
copy mapping holds no versions.

### 3. The items handler drops core's record items

With `foreign_table` set, core adds its own items, limited to the pages the
editor may see. The handler drops every item with a positive value and appends
its own list as before, as on `main`, where the maintainer chose the handler
over core as the source of the list on 2026-10-13. No workspace query, nothing
to overlay on this branch.

### 4. No upgrade wizard

Stored values keep their meaning. Existing contacts get reference index rows
on their next save or with `referenceindex:update`, which the `Important-`
entry says.

## Risks / Trade-offs

- [`-1` gets a reference index row pointing at no record] → as on `main`, and
  as core's `fe_group`. The test asserts it.
- [A copy made before this change keeps the original's addresses] → no
  release ships the dedicated selection yet, so only development
  installations can hold one. The form shows such a selection as invalid.

## Migration Plan

Nothing to run for correctness. `referenceindex:update` once, for the
reference index rows of existing contacts.

## Open Questions

None.
