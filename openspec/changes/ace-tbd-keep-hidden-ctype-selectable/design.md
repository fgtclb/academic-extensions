## Context

See `proposal.md` for the defect. What causes it, verified in
`cms-backend/Classes/Form/FormDataProvider/TcaSelectItems.php` of 13.4.35 and
14.3.6 (the relevant code is identical):

- The provider snapshots the items before `keepItems` and `removeItems` are
  applied, then keeps the difference as `$removedItems`.
- `processSelectFieldValue()` drops a stored value that is no longer among the
  items, so `databaseRow['CType']` becomes empty.
- `addInvalidItemsFromDatabase()` builds the "invalid value" option from the
  unmatched stored values minus `$removedItems`. A value hidden by TSconfig is
  therefore never shown, and the single select renders its first option
  selected.
- `DatabaseRecordTypeValue` runs before `TcaSelectItems` and keeps the stored
  type in `$result['recordTypeValue']`, so the value survives the
  sanitising.

Nine extensions hide their types in their always-loaded
`Configuration/page.tsconfig` with `removeItems := addToList(...)` (ACE-458).
Every academic content type carries the item group `academic`, registered by
academic_base (`Configuration/TCA/Overrides/tt_content.php`). academic_base has
no `ext_localconf.php` yet.

## Goals / Non-Goals

**Goals:**

- The stored academic type survives an unchanged save on any page.
- Nothing becomes selectable that was not stored on the record.

**Non-Goals:**

- Types hidden by user permissions: TYPO3 already refuses to open a record
  whose content type the user may not edit, and that stays untouched.

## Decisions

### A form data provider after `TcaSelectItems`

`FGTCLB\AcademicBase\Backend\FormDataProvider\KeepCurrentContentTypeSelectable`,
final and stateless, implements `FormDataProviderInterface`. It is registered
in `packages/fgtclb/academic-base/ext_localconf.php` for the
`tcaDatabaseRecord` group with `depends` on `TcaSelectItems`. Form data
providers have no attribute registration, so this array entry is the only
configuration it needs. It acts when all of these hold:

- the table is `tt_content` and the command is `edit`;
- `recordTypeValue` is not among the processed `CType` items;
- the TCA item with that value has the group `academic`;
- the page TSconfig of the record hides it (`removeItems` lists it, or
  `keepItems` is set and does not).

It then appends that item, with its translated label, the translated suffix
and its icon, and sets `databaseRow['CType']` to the stored value.

Rejected: rewriting `TCEFORM.tt_content.CType.removeItems` in `pageTsConfig`
before `TcaSelectItems` runs. It changes what TSconfig means for the whole
form compile, and an `altLabels` entry for the suffix would overwrite a
project's own relabelling.

### Decided: only the `academic` group, and a forge report for the rest

The provider keeps only types of the `academic` item group selectable, and
the general behaviour is reported to TYPO3 on forge. The silent rewrite comes
from core `TcaSelectItems::addInvalidItemsFromDatabase()` excluding the
removed items, and that code is identical in 13.4.35 and 14.3.6, so the
general fix belongs in core. A provider in academic_base that alters the
backend form for other extensions would be unexpected in their installations,
and the narrow scope keeps the branch `2` backport of this bugfix small.
Rejected: every type removed by `removeItems`.

### Rejected alternatives from the analysis

Stop hiding types by default, which reverts ACE-458. Documentation only, which
leaves the silent data change. `keepItems`, which cannot say "this record's
value only".

### What the editor sees

Guessed layout — a sketch, not a design:

```text
Type: [ Academic Persons: Profile list (not enabled on this page) v ]
       - Academic Persons: Profile list (not enabled on this page)
       - Regular text element
```

## Risks / Trade-offs

- [DataHandler might refuse the value on a page where TSconfig hides it] →
  Task 1.4 pins the save with a DataHandler test on both versions before the
  provider is written; if it is red, the design is revisited first.
- [A later core version changes the provider order or the result keys] →
  The functional test compiles the real form data group on v13 and v14.

## Migration Plan

Nothing to migrate: no stored data changes. Records that were already
rewritten before this change are not detectable and stay as they are; the
Important changelog says so.

## Open Questions

None.
