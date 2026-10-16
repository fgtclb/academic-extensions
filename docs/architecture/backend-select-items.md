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

## Outside FormEngine the row is empty

`GetSelectItemsForTcaManagedTableFieldMethodTrait` of `academic_base` calls the
`itemsProcFunc` of a field without FormEngine, for a frontend form. The job form
of `academic_jobs` is the one caller, for `type` and `employment_type`. It hands
over the keys FormEngine passes on every record, in the same order: `items`,
`config`, `TSconfig`, `table`, `row`, `field`, `effectivePid` and `site`. The
order is the one of `AbstractItemProvider::resolveItemProcessorFunction()` on
TYPO3 v12 and v13 alike.

There is no record, so `row` is an empty array. It used to be missing, and a
listener of `ModifyTcaSelectFieldItemsEvent` that read `$parameters['row']`
without a fallback raised `Undefined array key "row"`, which the debug preset
turns into an exception (ACE-922). The empty array saves a listener that reads
the row itself, not one that reads a field of it: `$parameters['row']['pid']`
still warns, about `pid` now. The event and the two job item providers
therefore declare `row` optional, `row?:`, so PHPStan reports a listener that
reads it without a fallback.

The inline keys and `flexParentDatabaseRow` are left out by the trait, because
nothing outside FormEngine has them, and the shapes declare them optional
already. `effectivePid` is the page of the form, or the root page of the site
when the request carries no page. The three controllers of
`academic-persons-edit` that call a handler outside FormEngine as well, see
[below](#five-of-the-ten-dispatch-an-event-five-do-not), pass no `row` either,
but the handler they call, `RecordTypes`, dispatches no event.

`GetSelectItemsForTcaManagedTableFieldMethodTraitTest` of `academic_base` pins
the keys, `AcademicJobsNewJobFormSelectItemsListenerTest` of `academic_jobs`
runs a listener that reads the row through the job form.

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
`foreign_table`, which is every select these handlers serve except the three
address selects of a page contact, see
[below](#the-address-selects-of-a-page-contact-are-relations). What happens
next depends on the render type:

| Render type                | Value not among the items                                                 |
|----------------------------|---------------------------------------------------------------------------|
| `selectSingle`             | An `[ MISSING LABEL ]` *item* is added, but the row value is not restored |
| `selectMultipleSideBySide` | Nothing — the value is dropped silently                                   |

In both cases the rendered form no longer has the value selected, so the next
save writes the relation away. A handler that narrows its items — by page, by
type, by anything — therefore has to put the currently referenced values back
into the items, or the narrowing is a data loss.

`ContractSelectScopeResolver` of `academic_persons` is the worked example: it
reads the referenced uids out of the row and the repository returns them
alongside the restricted ones, so their label comes from the same query.

## A handler querying through Extbase states its enable fields

`Typo3QuerySettings::__construct()` switches `ignoreEnableFields` on for a
backend request on TYPO3 v13, and on TYPO3 v12 only when the deprecated Extbase
feature toggle `ignoreAllEnableFieldsInBe` is enabled, which it is not by
default. Outside a frontend request, `Typo3DbQueryParser` then skips
`BackendUtility::BEenableFields()` entirely, and only the soft delete clause
remains. Its list `enableFieldsToBeIgnored` is not read on that path, so the
flag is all or nothing there. A handler that inherits the default therefore
offers hidden and timed records in the form on v13 and not on v12, and on
neither when it runs without a request, from a command or a test.

A handler sets the flag on the settings it builds, so its items depend neither
on the request nor on the core version. `PartnerItems` of `academic_partners`
offers every partner page that is not deleted since ACE-672. The record
selects of the core apply no enable fields either, only a `DeletedRestriction`
and a `WorkspaceRestriction`, and for `pages` the page permissions. That keeps
a stored partner among the items, which the section above requires. Before, a
partnership whose partner page was hidden, started in the future or had
expired lost it on the next save on v12. Set it in the handler, not in the
repository method it calls: in a frontend request an ignore flag with an empty
list drops every enable field, so a shared repository method would hand hidden
records to a frontend caller. `PartnerItemsTest` calls the handler without a
request, where the core default stays off and only the handler's own flag
shows.

## A handler appends to the declared items

`$parameters['items']` arrives holding the items the field declares itself,
such as the empty item of value `0` of the contract select of a page contact.
A handler adds its own items after them. Merging with
`ArrayUtility::mergeRecursiveWithOverrule()` is wrong for a list: both lists
are indexed from 0, so the handler's first item replaces the declared one.
`ContractItems` did that until ACE-842, and a new page contact, whose default
`0` then had no item, showed `[ MISSING LABEL ("0") ]`. That is the label
`TcaSelectItems` gives a `selectSingle` value it finds no item for, the item it
puts back in the list so the value survives the render. `EmploymentTypeItems`,
`TypeItems` and `ProfileShowFieldsItems` still merge and are deliberately left
as they are: their fields declare no items, so there is nothing to replace.

A handler that reads the items it is handed gets each one as a `SelectItem`,
which implements `ArrayAccess`, on v12 and v13 alike
(`AbstractItemProvider::resolveItemProcessorFunction()`). `$item['value']`
works, a parameter typed `array` fails.

## An appended item must not repeat the declared empty value

The declared empty item of a select has a value too, usually `''` or `0`, and
a handler that builds its items from a configured string can produce the same
value. `DemandValues` and `RecordTypes` of `academic_persons` read comma
separated `value=label` lists from the extension configuration. Until
ACE-438, an emptied option, a trailing comma or two commas in a row added an
item `''` without a label beside the declared "none" or "undefined" item. For
the sort and group fields of the persons list the parsed list is also the
allow list of `ProfileRepository`, so "none" passed it and the list ordered by
a column without a name, which SQLite, MariaDB and PostgreSQL reject.

`AbstractDemandValues` and `AbstractTypes` split the list with
`GeneralUtility::trimExplode(',', $value, true)` now and skip an entry whose
value is empty. The split of an entry by `=` deliberately keeps empty values:
with `removeEmptyValues`, `=Label` comes back as the single element `Label`,
which lands in the value position and leaves the label undefined.

## A `###REC_FIELD_…###` marker needs a value in the row

A `foreign_table_where` can read the record being edited through
`###REC_FIELD_<column>###`, which `AbstractItemProvider` replaces by the quoted
value of that column. A column without a value becomes `''`, and a comparison
of an integer column with `''` is rejected by PostgreSQL, while the default
SQLite run accepts it without an error. That happens to every column of a new
record that has no TCA `default`. The profile select of a profile information
reads `###REC_FIELD_sys_language_uid###` since ACE-842, which is why its
language column declares `'default' => 0`.

## The address selects of a page contact are relations

The e-mail address, phone number and postal address selects of a page contact
in `academic_contacts4pages` are served by `AddressRecordItems` and declare the
address table of `academic_persons` as their `foreign_table` (ACE-481). The
handler builds the list, the declaration makes TYPO3 treat the stored value as
a relation, and that changes three things:

- **A value that is not among the items stays in the row.**
  `processSelectFieldValue()` keeps a relation id even when no item matches,
  and `addInvalidItemsFromDatabase()` shows it with the "missing label" marker,
  so a selection whose address belongs to another contract survives a save
  instead of being written away.
- **TYPO3 adds items of its own before the handler runs.**
  `AbstractItemProvider::addItemsFromForeignTable()` queries the table with
  the `foreign_table_where` and the page permission clause of the editor, which
  joins `pages`. Every column of the clause therefore names its table, since
  `pages` has `sys_language_uid`, `sorting` and `uid` as well, and the
  `ORDER BY` part names them without `{#…}`, because core quotes that part
  itself: `{#table}.{#sorting}` there becomes one quoted name, the query fails,
  and the editor gets an error flash message on every form open. The clause
  compares `CAST(contract AS CHAR(11))` with `###REC_FIELD_contract###`,
  because the marker becomes `''` when the contract select drops the value, a
  contract that is gone or one not saved yet, and PostgreSQL rejects `''` for
  an integer column. The handler drops every record item core added and
  appends its own list, which is not narrowed by the editor's access to the
  folder of the person records, just as the contract select is not.
- **A copy would be remapped by TYPO3, and so would a translation.**
  `DataHandler::remapListedDBRecords()` rewrites a relation through the copy
  mapping of the whole command. Translating a contract translates its
  addresses and its page contacts in one command, and the mapping then holds
  default language uid to translated uid, so the contact translation would
  select the translated address. The fields name their table in
  `dontRemapTablesOnCopy`, and `AddressSelectionCopyHook` remaps a copied
  contact to a copied address only when that address is no translation of the
  selected one.

The reference index writes a row for each selected address, and one with the
`ref_uid` `-1` for "display none", as it does for the `-1` and `-2` of
`fe_group`.

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

Six test classes do this today:
`academic-base/Tests/Functional/Backend/FormDataProvider/KeepCurrentContentTypeSelectableTest.php`,
`academic-partners/Tests/Functional/Backend/FormEngine/PartnerSelectOrderTest.php`,
`CardFlexFormFieldsTest` and `ProfileInformationProfileSelectTest` of
`academic-persons`, and the two `ContractSelectStorageScopeTest` of
`academic-persons` and `academic-contact4pages`. All six give the request
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
`academic-persons-edit`. The trait passes the field's `itemsProcFunc.` subtree
and an empty `row`, see
[above](#outside-formengine-the-row-is-empty). The three controllers pass no
`TSconfig` key at all, which a handler has to read as "no setting" rather than
as an error.

## A category tree offers the categories of one group

A field of `type` `category` has no `itemsProcFunc`: `TcaCategory` builds the
tree from `foreign_table` and `foreign_table_where` alone, on v12 and v13 alike.
The category fields of the list plugins of `academic_partners`,
`academic_programs` and `academic_projects` therefore name the category type
group of their plugin with a marker in that clause:

```xml
<foreign_table_where>AND {#sys_category}.{#uid} IN (###CATEGORY_TYPE_GROUP:partners###) AND {#sys_category}.{#sys_language_uid} IN (-1, 0)</foreign_table_where>
```

`ResolveCategoryTypeGroupMarker` of `category_types`, a listener on
`AfterFlexFormDataStructureParsedEvent`, replaces it with a subselect of the
categories that carry a type of the group, and of their ancestors (ACE-852).
The ancestors are needed because the tree shows a category only together with
every parent above it, and a category of the group below a parent of another
type is a structure the filters support with `groupByParent`. Such a parent is
selectable in the tree, and the plugin ignores it, as before.

The listener must not query the database. TYPO3 v13 parses every data
structure while `TcaSchemaFactory` builds the schema during bootstrap, before a
table may exist, and caches the result: a first version that looked the uids up
broke the bootstrap of every functional test on v13. The ancestors are
therefore a recursive common table expression the tree query evaluates,
verified on SQLite, MariaDB 10.4, MySQL 8.0 and PostgreSQL 10.

A literal list of types in the FlexForm file is not an option: the types of a
group come from the `CategoryTypes.yaml` of every installed extension, and an
integrator adds and removes them. The demand reads the selection with
`CategoryRepository::getByDatabaseFields(<group>, …)`, which drops every
category of another type, so a tree that offers them lets an editor pick a
filter the frontend never applies. That was the state before, with
`{#type} != ''`.

The `CategoryTreeTypeGroupTest` of each of the three extensions fetches the
tree through `FormSelectTreeAjaxController::fetchDataAction()`, the request the
backend sends when the field is opened.

## See also

- [Database queries](database-queries.md) — quoting a uid list, and ordering
  what a select renders.
- [Core version aware code](core-version-aware-code.md) — where a v12/v13
  difference belongs.
- [Functional tests](../testing/functional-tests.md) — running the suite that
  compiles these forms.
- [Configuration of `academic_persons`](../../packages/fgtclb/academic-persons/Documentation/Configuration/Index.rst)
  — the integrator-facing side of the contract select restriction.
