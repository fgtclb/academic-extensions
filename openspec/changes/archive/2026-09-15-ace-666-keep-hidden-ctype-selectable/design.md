## Context

See `proposal.md` for the defect and the change on `main` it backports. What
the backport analysis measured on this branch's versions (12.4.45 read from the
composer cache, 13.4.35 installed):

- The same nine extensions hide their types with `removeItems := addToList(...)`
  in their always-loaded `Configuration/page.tsconfig`, every one of those
  items carries the group `academic`, and academic_base registers the group.
  academic_base has no `ext_localconf.php` on this branch either.
- `TcaSelectItems` takes the `$removedItems` snapshot before `keepItems` and
  `removeItems` and subtracts it in `addInvalidItemsFromDatabase()` on 12.4 as on
  13.4 (the code differs only in closure type hints); the keepItems/removeItems
  methods and `DatabaseRecordTypeValue` are byte-identical. A core-only
  reproduction shows the same result on 12.4.45 and 13.4.35: no option for the
  stored value, first option preselected.
- DataHandler `checkValueForGroupFolderSelect()` checks a static select value
  against neither the items nor page TSconfig on 12.4, as on 13.4.
- In the `tcaDatabaseRecord` group the only direct dependent of `TcaSelectItems`
  is `TcaSelectTreeItems`, on 12.4 and 13.4 alike. The backend layout
  restriction `main` orders against exists from v14 on only.

## Goals / Non-Goals

**Goals:**

- The same behaviour as on `main`, on v12 and v13, with one code path.

**Non-Goals:**

- Types hidden by user permissions: TYPO3 already refuses to open a record whose
  content type the user may not edit.

## Decisions

### The provider of `main`, adapted to this branch

`FGTCLB\AcademicBase\Backend\FormDataProvider\KeepCurrentContentTypeSelectable`
implements `FormDataProviderInterface`, is registered in a new
`packages/fgtclb/academic-base/ext_localconf.php` for the `tcaDatabaseRecord`
group with `depends` on `TcaSelectItems` and `before` on `TcaSelectTreeItems`,
and acts under the same four conditions as on `main`: table `tt_content` and
command `edit`; `recordTypeValue` missing from the processed items; the TCA item
of that value in the group `academic`; page TSconfig hiding it (`removeItems`
lists it, or `keepItems` is set and does not). It then puts the item first, in
the ungrouped `none` group where core puts its "invalid value" option, with its
translated label (or `altLabels`), the translated suffix and its icon (or
`altIcons`), and sets `databaseRow['CType']` to the stored value.

Adaptations, all from this branch's versions and none a version switch:

- `final class` instead of `final readonly class`: the branch compiles against
  PHP 8.1. The class has no properties, so nothing is lost.
- No `before` entry for `TcaTtContentCtypeItemsRestrictionByBackendLayout`, and
  no test for it: neither v12 nor v13 has that provider.
- The functional test takes `TcaDatabaseRecord` from
  `GeneralUtility::makeInstance()`: on v12 it is a plain class, not a public
  container service. `FormDataCompiler::compile()` accepts the group as second
  argument on both versions.
- The label is added with tab indentation, as every XLF file on this branch is.

Rejected on this branch as on `main`: rewriting `removeItems` in the form's page
TSconfig before `TcaSelectItems` runs, an `altLabels` entry for the suffix, and
covering every hidden type instead of the `academic` group. A folder split was
not considered: nothing differs between v12 and v13.

## Risks / Trade-offs

- [The v12 form engine was not the version `main` was measured on] → the tests
  run red first and green after on v12 with PHP 8.1 and on v13 with PHP 8.2, and
  the DataHandler cases pin the save on both.
- [MySQL on v12 behaves differently from SQLite, see ACE-358] → the new test
  class also runs on MySQL and PostgreSQL on both versions.

## Migration Plan

Nothing to migrate. Content elements that were already rewritten are not
detectable and stay as they are; the Important changelog says so.

## Open Questions

None.
