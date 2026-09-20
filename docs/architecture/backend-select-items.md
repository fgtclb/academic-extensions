# Backend select items

Ten classes across five extensions build the items of a backend select with an
`itemsProcFunc`, and 24 fields are configured with one:

```bash
grep -rh 'itemsProcFunc' packages/*/*/Configuration | wc -l
```

`AddressRecordItems` (contact4pages), `EmploymentTypeItems` and `TypeItems`
(jobs), `CountryItems` and `PartnerItems` (partners), `ContractItems`,
`ProfileShowFieldsItems`, `DemandValues` and `RecordTypes` (persons) and
`SortingItemsProcFunc` (projects). Eight live in a `Classes/Backend/FormEngine/`
directory; `DemandValues` and `RecordTypes` are in `Classes/Tca/`. They are
configured on TCA columns and on FlexForm elements alike.

This page is about what such a handler is handed, what it may do with it, and
the one trap that costs data.

## `TSconfig` is the option subtree, not the tree above it

The handler receives `&$parameters`, and `$parameters['TSconfig']` is the
**content** of the field's `itemsProcFunc.` page TSconfig, with that key already
stripped — not the subtree containing it. On both supported core versions it is
assembled in `AbstractItemProvider::resolveItemProcessorFunction()`, which
initialises it to `null` and only fills it when the field has such page
TSconfig. So a handler reads `$parameters['TSconfig']['myOption']` and checks
`is_array()` first.

`$parameters['row'][$parameters['field']]` is the **raw** database value at that
moment: `TcaSelectItems::addData()` resolves the items before it processes the
row value, so a select field still holds the stored string rather than the
array a later provider turns it into. For a FlexForm element it is the `vDEF`
value of that element, because `TcaFlexProcess` compiles the sheet with a row
assembled from the flex values.

## Page TSconfig reaches a FlexForm field by a longer path

For a TCA column the path is the familiar one:

```typoscript
TCEFORM.<table>.<field>.itemsProcFunc.myOption = …
```

For a FlexForm element the path carries the data structure identifier and the
sheet, and the dot in the element's name is escaped:

```typoscript
TCEFORM.tt_content.pi_flexform.<identifier>.<sheet>.<element>.itemsProcFunc.myOption = …
```

`TcaFlexProcess::modifyDataStructureAndDataValuesByFlexFormSegmentGroup()`
compiles every sheet through a nested `FormDataCompiler` and passes it that
sheet's subtree as `pageTsConfig['TCEFORM.'][<parent table>.]`, which is why the
inner provider finds the element under its own name.

Every plugin of these extensions registers its data structure with
`ExtensionManagementUtility::addPiFlexFormValue('*', …, $cType)`, which produces
the `dataStructureKey` `*,<cType>`; `getSimplifiedDataStructureIdentifier()`
takes the part after the comma, so the `<identifier>` is the CType and the sheet
is `sDEF`. That holds for TYPO3 v12 and v13 alike, although the data structure
files themselves are split per core version in
`academic-persons/Configuration/FlexForms/Core12|Core13/`.

## A value that is not among the items is lost

This is the trap. `AbstractItemProvider::processSelectFieldValue()` keeps only
the values that are present in the item list when the field has no
`foreign_table` — which is every select these handlers serve. What happens next
depends on the render type:

| Render type                | Value not among the items                                                 |
|----------------------------|---------------------------------------------------------------------------|
| `selectSingle`             | An `[ INVALID VALUE ]` *item* is added, but the row value is not restored |
| `selectMultipleSideBySide` | Nothing — the value is dropped silently                                   |

In both cases the rendered form no longer has the value selected, so the next
save writes the relation away. A handler that narrows its items — by page, by
type, by anything — therefore has to put the currently referenced values back
into the items, or the narrowing is a data loss.

`ContractSelectScopeResolver` of `academic_persons` is the worked example: it
reads the referenced uids out of the row and the repository returns them
alongside the restricted ones, so their label comes from the same query.

## What the page list expansion does besides expanding

`PageRepository::getDescendantPageIdsRecursive()` is what expands a configured
page list below its depth, and three of its behaviours are worth knowing before
such a list is configured:

- A **mount point** in the list is swapped for its `mount_pid`, so the subtree
  that is walked is the mounted one. The listed page itself stays in the list, and
  with a depth of `0` it is a page that holds no records of its own.
- A page of type **Backend User Section** (`doktype` 6), and a version
  placeholder, is skipped together with its whole subtree, and
  `$bypassEnableFieldsCheck` does not change that. On **TYPO3 v12** a
  **Recycler** (`doktype` 255) is skipped as well; v13 dropped that clause, so a
  storage folder below a recycler resolves there and not here.
- The walk applies a `WorkspaceRestriction` from the global `Context`, so a
  storage folder created inside a workspace resolves differently from one in live.

The bypass flag itself only lifts the two `RecordAccessVoter` checks and relaxes
`versionOL()`; the start page has to be non-deleted and nothing more, because
`getRawRecord()` applies a `DeletedRestriction` only. That is what makes a hidden
storage folder work.

The expansion runs on **every** invocation of the handler and is not cached.
FormEngine calls a handler once per select, but `BackendUtility::getLabelFromItemlist()`
calls it once per rendered value, so a list module row or an inline child costs
one walk each. Configure the smallest depth that covers the storage layout, and
leave `recursive` unset where the folders are listed explicitly.

## A restriction cannot make a save fail

Only `checkValueForCheck()` and `checkValueForRadio()` run an `itemsProcFunc`
when the DataHandler validates a submitted value; a `select` value is never
checked against the items, so a restriction cannot make a save fail.

## Test it by compiling the form

A test that calls the handler with a hand-built parameter array asserts the
parameter shape the test believes in. Compile the real form instead — it is the
only way to prove a page TSconfig path, and the only way to see the row value
being dropped:

```php
$result = GeneralUtility::makeInstance(FormDataCompiler::class)->compile(
    [
        'request' => $request,
        'tableName' => 'tt_content',
        'vanillaUid' => $contentUid,
        'command' => 'edit',
    ],
    // Not `$this->get()`: on TYPO3 v12 `TcaDatabaseRecord` is not a public service.
    GeneralUtility::makeInstance(TcaDatabaseRecord::class),
);
```

The items of a TCA column are then at
`$result['processedTca']['columns'][$field]['config']['items']`, and those of a
FlexForm element at
`$result['processedTca']['columns']['pi_flexform']['config']['ds']['sheets'][$sheet]['ROOT']['el'][$element]['config']['items']`.

Four test classes do this today:
`academic-base/Tests/Functional/Backend/FormDataProvider/KeepCurrentContentTypeSelectableTest.php`,
`academic-partners/Tests/Functional/Backend/FormEngine/PartnerSelectOrderTest.php`
and the two `ContractSelectStorageScopeTest` of `academic-persons` and
`academic-contact4pages`. All four give the request
`SystemEnvironmentBuilder::REQUESTTYPE_BE` and a `normalizedParams` attribute,
and set `$GLOBALS['LANG']`, because labels are resolved during the compile.

## Five of the ten dispatch an event, five do not

`FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent` carries the whole
parameter array and is dispatched **after** the handler built its items, so a
project listener has the last word — including over a restriction. A handler
that narrows its items narrows what the listener receives, not what it may
return.

Only five handlers dispatch it, so "can my listener reach this select?" has two
answers:

| Dispatches the event                                | Builds its items and returns              |
|-----------------------------------------------------|-------------------------------------------|
| `AddressRecordItems` (contact4pages)                | `CountryItems`, `PartnerItems` (partners) |
| `EmploymentTypeItems`, `TypeItems` (jobs)           | `DemandValues`, `RecordTypes` (persons)   |
| `ContractItems`, `ProfileShowFieldsItems` (persons) | `SortingItemsProcFunc` (projects)         |

```bash
grep -rln ModifyTcaSelectFieldItemsEvent packages/*/*/Classes
```

Two places in this repository call a handler outside FormEngine and assemble the
parameters themselves:
`academic-base`'s `GetSelectItemsForTcaManagedTableFieldMethodTrait`, which is
`@api`, and the three address, e-mail and phone controllers of
`academic-persons-edit`. The trait passes the field's `itemsProcFunc.` subtree;
the three controllers pass no `TSconfig` key at all, which a handler has to read
as "no setting" rather than as an error.

## See also

- [Database queries](database-queries.md) — quoting a uid list, and ordering
  what a select renders.
- [Core version aware code](core-version-aware-code.md) — where a v12/v13
  difference belongs.
- [Functional tests](../testing/functional-tests.md) — running the suite that
  compiles these forms.
- [Configuration of `academic_persons`](../../packages/fgtclb/academic-persons/Documentation/Configuration/Index.rst)
  — the integrator-facing side of the contract select restriction.
