# Backend select items

Eleven classes across six extensions build the items of a backend select with an
`itemsProcFunc`, and 23 fields are configured with one:

```bash
grep -rh 'itemsProcFunc' packages/*/*/Configuration | wc -l
```

`AddressRecordItems` (contact4pages), `EmploymentTypeItems` and `TypeItems`
(jobs), `CountryItems` and `PartnerItems` (partners), `ContractItems`,
`ProfileShowFieldsItems`, `DemandValues` and `RecordTypes` (persons),
`SortingItemsProcFunc` (projects) and `CategoryTypeItemsProcFunc`
(category_types). Nine live in a `Classes/Backend/FormEngine/` directory;
`DemandValues` and `RecordTypes` are in `Classes/Tca/`. They are
configured on TCA columns and on FlexForm elements alike.

This page is about what such a handler is handed, what it may do with it, and
the one trap that costs data.

## `TSconfig` is the option subtree, not the tree above it

The handler receives `&$parameters`, and `$parameters['TSconfig']` is the
**content** of the field's `itemsProcFunc.` page TSconfig, with that key already
stripped — not the subtree containing it. Both supported versions agree on the
value and differ in where it is assembled:

| Core version | Assembled in                                              | Absent setting |
|--------------|-----------------------------------------------------------|----------------|
| v13          | `AbstractItemProvider::resolveItemProcessorFunction()`    | `null`         |
| v14          | `Core\DataHandling\ItemProcessingService::processItems()` | `null`         |

So a handler reads `$parameters['TSconfig']['myOption']` and checks
`is_array()` first. Everything else it is given — `items`, `config`, `table`,
`row`, `field`, `effectivePid`, `site` and the `inline*` keys — is the same on
both.

The versions also differ in **how often they look**: when the caller hands over
no field TSconfig, v14 looks it up itself at `TCEFORM.<table>.<field>.` of the
row's page (`ItemProcessingService::processItems()`), and v13 does not. For a
FlexForm element that means the plain path
`TCEFORM.tt_content.settings\.selectedContracts.itemsProcFunc.…`, without the
flex detour below, is honoured on v14 and ignored on v13. Nobody would write it
deliberately, and it fails open — but it is one more reason not to take a
measurement on one version as evidence for the other.

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

The `<identifier>` is the same string on both core versions, but it is derived
by two different routes, so neither is evidence for the other:

| Core version | Registration                                                     | `dataStructureKey` | Reduced by `getSimplifiedDataStructureIdentifier()` |
|--------------|------------------------------------------------------------------|--------------------|-----------------------------------------------------|
| v13          | `ExtensionManagementUtility::addPiFlexFormValue('*', …, $cType)` | `*,<cType>`        | the part after the comma                            |
| v14          | `columnsOverrides` per record type                               | `<cType>`          | the whole string, there is no comma                 |

For the plugins of these extensions the identifier is therefore the CType either
way, and the sheet is `sDEF`. Do not carry the v13 registration to v14 to make
them agree: v14's comma branch calls `trigger_error(…, E_USER_DEPRECATED)`, and
the suites here run with `failOnDeprecation`.

Both registrations are made by
`academic-base/Classes/TcaManipulator.php::addContentElementPluginFlexForm()`,
which is the file to read when that identifier is in doubt.

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

`PageRepository::getDescendantPageIdsRecursive()` is byte-identical on v13.4.34
and v14.3.7, and three of its behaviours are worth knowing before a page list is
configured:

- A **mount point** in the list is swapped for its `mount_pid`, so the subtree
  that is walked is the mounted one. The listed page itself stays in the list, and
  with a depth of `0` it is a page that holds no records of its own.
- A page of type **Backend User Section** (`doktype` 6) is skipped together with
  its whole subtree, and `$bypassEnableFieldsCheck` does not change that.
- The walk applies a `WorkspaceRestriction` from the global `Context`, so a
  storage folder created inside a workspace resolves differently from one in live.

The bypass flag itself only lifts the two `RecordAccessVoter` checks and relaxes
`versionOL()`; the start page has to be non-deleted and nothing more, because
`getRawRecord()` applies a `DeletedRestriction` only. That is what makes a hidden
storage folder work.

The expansion runs on **every** invocation of the handler and is not cached.
FormEngine calls a handler once per select, but the label resolvers —
`BackendUtility::getLabelFromItemlist()` on v13 and
`SchemaLabelResolver::getLabelForFieldValue()` on v14 — call it once per rendered
value, so a list module row or an inline child costs one walk each. Configure the
smallest depth that covers the storage layout, and leave `recursive` unset where
the folders are listed explicitly.

## A restriction cannot make a save fail

Only `checkValueForCheck()` and
`checkValueForRadio()` run an `itemsProcFunc` when validating a submitted
value; a `select` value is never checked against the items, so a restriction
cannot make a save fail.

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
    $this->get(TcaDatabaseRecord::class),
);
```

The items of a TCA column are then at
`$result['processedTca']['columns'][$field]['config']['items']`, and those of a
FlexForm element at
`$result['processedTca']['columns']['pi_flexform']['config']['ds']['sheets'][$sheet]['ROOT']['el'][$element]['config']['items']`.

The test classes that do this are found with

```bash
grep -rl FormDataCompiler packages/*/*/Tests
```

and all of them give the request `SystemEnvironmentBuilder::REQUESTTYPE_BE` and
a `normalizedParams` attribute, and set `$GLOBALS['LANG']`, because labels are
resolved during the compile. The items of a category tree are the exception, see
the next section.

## A category tree that starts at a site setting

TYPO3 resolves `###SITE:<path>###` in `treeConfig.startingPoints` of a category
field from the configuration of the record's site
(`AbstractItemProvider::parseStartingPointsFromSiteConfiguration()`, identical
on v13 and v14). It is the documented way to start a tree per site, and it has
one flaw for a setting that may be empty: an empty value, a text, a missing path
and a record outside of every site all end up as a single starting point `0`.
`TreeDataProviderFactory` makes the first level of the tree selectable whenever
`startingPoints` holds no comma and the field configures no
`appearance.nonSelectableLevels`, so the whole tree comes back with its top node
selectable, which it is not without `startingPoints`.

`academic_programs` therefore keeps the marker in its TCA and FlexForms as the
declaration, and its form data provider `CategoryTreeRoot` replaces it before
`TcaCategory` runs: with the uids of the setting, or by removing
`startingPoints` when the setting names none. It is registered after
`SiteResolving` and `TcaColumnsOverrides`, because the marker of a program page
arrives with the columns overrides of its page type, and before `TcaCategory`,
in three form data groups. `flexFormSegment` runs no `TcaColumnsOverrides`, and
core ignores a dependency that is not part of the group:

| Group                        | Compiles                                              |
|------------------------------|-------------------------------------------------------|
| `tcaDatabaseRecord`          | the record form                                       |
| `flexFormSegment`            | a FlexForm field, in the form and in the tree request |
| `tcaSelectTreeAjaxFieldData` | the tree request of a TCA column                      |

The tree is not part of the form. FormEngine renders the field, and the tree
loads its items afterwards from `FormSelectTreeAjaxController`, which compiles
the field alone through `tcaSelectTreeAjaxFieldData`. A provider registered for
the record form only changes the configuration a test asserts, not the tree an
editor sees.

Test the tree through that controller, as `CategoryTreeRootTest` of
`academic-programs` does. Compiling `tcaDatabaseRecord` with
`selectTreeCompileItems` looks equivalent and is not on v14:
`TcaColumnsRemoveEmptyRelations` (v14.2, core issue #109366) counts the `value`
of the items, tree items carry an `identifier` instead, and the category field
is removed from the result.

## Five of the eleven dispatch an event, six do not

`FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent` carries the whole
parameter array and is dispatched **after** the handler built its items, so a
project listener has the last word — including over a restriction. A handler
that narrows its items narrows what the listener receives, not what it may
return.

Only five handlers dispatch it, so "can my listener reach this select?" has two
answers:

| Dispatches the event                                | Builds its items and returns                 |
|-----------------------------------------------------|----------------------------------------------|
| `AddressRecordItems` (contact4pages)                | `CountryItems`, `PartnerItems` (partners)    |
| `EmploymentTypeItems`, `TypeItems` (jobs)           | `DemandValues`, `RecordTypes` (persons)      |
| `ContractItems`, `ProfileShowFieldsItems` (persons) | `SortingItemsProcFunc` (projects)            |
|                                                     | `CategoryTypeItemsProcFunc` (category_types) |

`CategoryTypeItemsProcFunc` offers the types of a category group, which a
project changes where it registers them, in `CategoryTypes.yaml` — see
[List filter types](list-filter-types.md#the-items-of-the-field).

```bash
grep -rln ModifyTcaSelectFieldItemsEvent packages/*/*/Classes
```

## See also

- [Database queries](database-queries.md) — quoting a uid list, and ordering
  what a select renders.
- [Core version aware code](core-version-aware-code.md) — where a v13/v14
  difference belongs.
- [Functional tests](../testing/functional-tests.md) — running the suite that
  compiles these forms.
- [Configuration of `academic_persons`](../../packages/fgtclb/academic-persons/Documentation/Configuration/Index.rst)
  — the integrator-facing side of the contract select restriction.
