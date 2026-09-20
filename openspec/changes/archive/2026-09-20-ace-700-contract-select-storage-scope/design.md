## Context

Re-measured on 2026-09-20 against `main` `e89a3ab21` and `origin/2` `6af098433`,
with the core sources of TYPO3 v14.3.7 (`.Build/vendor`) and v13.4.34
(`core-13/vendor`). Two of the four statements the propose phase recorded were
wrong; what follows is what the code does.

- `ContractItems::itemsProcFunc()` is configured on
  `tx_academiccontacts4pages_domain_model_contact.contract` (TCA, `selectSingle`)
  and on `settings.selectedContracts` of
  `Configuration/FlexForms/SelectedContracts.xml` (`selectMultipleSideBySide`).
  Confirmed.
- `ContractRepository::getContractItemsForTcaItemsProcFunc(array $parameters)`
  returns `$this->findAll()` and ignores `$parameters`; `findAll()` calls
  `setRespectStoragePage(false)`. Confirmed. Its docblock states the parameters
  are passed through "so the query can be narrowed by context ... later on
  without touching the calling handler again" - this change is that seam being
  used.
- **`$parameters['TSconfig']` is the content of `itemsProcFunc.`, not its
  parent.** The propose phase assumed `$parameters['TSconfig']['itemsProcFunc.']`.
  On v13 `AbstractItemProvider::resolveItemProcessorFunction()` assigns
  `'TSconfig' => $result['pageTsConfig']['TCEFORM.'][$table.'.'][$field.'.']['itemsProcFunc.']`
  and on v14 `ItemProcessingService::processItems()` assigns
  `$fieldTSconfig['itemsProcFunc.'] ?? null`. The handler therefore reads
  `$parameters['TSconfig']['storagePids']`, and must tolerate `null`.
- **The FlexForm field does receive the TSconfig**, by the flex path rather than
  the plain one. `TcaFlexProcess::modifyDataStructureAndDataValuesByFlexFormSegmentGroup()`
  compiles every sheet through a nested `FormDataCompiler` and hands it
  `pageTsConfig['TCEFORM.'][<parent table>.]` = the sheet's subtree of
  `TCEFORM.tt_content.pi_flexform.<identifier>.`, so the inner provider finds
  the field under the flex element's own name. The identifier is
  `academicpersons_selectedcontracts` on **both** core versions, by two
  different routes: on v13 `addPiFlexFormValue('*', …, $cType)` produces the
  `dataStructureKey` `*,academicpersons_selectedcontracts`, of which
  `getSimplifiedDataStructureIdentifier()` takes the part after the comma; on
  v14 the `columnsOverrides` registration produces the record type itself, with
  no comma to cut - and v14 deprecates the comma form, which `failOnDeprecation`
  would turn into a failing test. The sheet is `sDEF`; the element name contains
  a dot and is escaped in TSconfig.
- `ContractItems.php` is byte-identical on `main` and `origin/2`, and so are
  `getContractItemsForTcaItemsProcFunc()` and `findAll()`. Confirmed.

Two further facts decide the shape and were measured rather than assumed:

- **A value that is not among the items is lost, not merely flagged.**
  `AbstractItemProvider::processSelectFieldValue()` keeps only values found in
  the item list for a select without `foreign_table`, which both fields are.
  For `selectSingle` core then adds an `[ INVALID VALUE ]` *item*, but it does
  not put the value back into `databaseRow`, so the form renders nothing
  selected and the next save drops the relation; for
  `selectMultipleSideBySide` that step is skipped entirely. Keeping the
  referenced uids in the items is therefore a data-loss guard, not cosmetics.
- **DataHandler does not validate a `select` value against the items.** Only
  `checkValueForCheck()` and `checkValueForRadio()` run the itemsProcFunc, so a
  restriction cannot make a save fail. The guard above is needed for what
  FormEngine renders, not for what DataHandler accepts.

## Goals / Non-Goals

Beyond the proposal's scope:

- **Goal**: the restriction is resolved in one place that both fields and every
  caller of the repository method reach, so a project calling the repository
  directly gets the same items as FormEngine.
- **Non-goal**: reading the backend user's `webmounts` or `db_mountpoints`. The
  setting is an integrator's declaration of where contracts live, not a
  permission check.

## Decisions

### Opt-in page TSconfig, not a default

The setting lives where TYPO3 puts options of an `itemsProcFunc`: in the
field's `TCEFORM` page TSconfig. Page TSconfig is per page tree, so each site
configures its own folders. Without the setting nothing changes, which is the
only default that cannot empty a select. Rejected:

- **Respect the storage page**: empties the select wherever no storage page is
  configured (ACE-431).
- **Scope to the site of the edited record automatically**: breaks the common
  layout of one shared persons folder outside every site.
- **A site setting**: the select is a backend form concern, and page TSconfig
  already scopes per site without new infrastructure.

### The repository method keeps its signature; a value object does the reading

`getContractItemsForTcaItemsProcFunc(array $parameters)` stays as it is and
resolves the scope itself, because that is what its docblock promises and
because it is the seam a project already calls. The parsing does not live in
the repository: a `final readonly` value object
`Backend\FormEngine\ContractSelectScope` turns the itemsProcFunc parameters
into the two domain level inputs - the page ids to restrict to and the uids to
keep - and the repository passes them to a new
`findForBackendSelect(array $storagePageIds, array $alwaysIncludeUids)`.

Rejected: resolving in `ContractItems` and widening the repository signature.
It would have left the documented seam behaving differently from the handler,
and a subclass overriding the old signature would fatal.

### The page list is expanded with `PageRepository`, enable fields bypassed

`getDescendantPageIdsRecursive($pageId, $depth, 0, [], true)` with
`$bypassEnableFieldsCheck = true`: a storage folder is regularly hidden, and a
backend select that silently skips a hidden folder is the same defect as the
one this change fixes. Rejected: `getPageIdsRecursive()`, which has no bypass
and would need a `Context` with a lifted `VisibilityAspect`, and
`PagesUtility::getPagesRecursively()` of `academic_programs`, which is another
extension's class and goes through `getMenu()`.

### Keep the current value

The currently referenced uid(s) are read from
`$parameters['row'][$parameters['field']]`, which holds the raw database value
for the TCA field and the `vDEF` value for the FlexForm element, because the
itemsProcFunc runs before `TcaSelectItems` processes the row value. They are
added to the query as an `OR`, not merged into the items afterwards, so their
label and position come from the same query as every other item.

### The event runs after the restriction

`ModifyTcaSelectFieldItemsEvent` receives the restricted items, so a project
listener keeps the last word.

## Risks / Trade-offs

- [An integrator lists the wrong pages] → The select shows fewer contracts and
  existing values stay; the setting is documented with an example for both
  fields, including the flex path.
- [The flex path differs on branch `2`] → v12 registers the data structure with
  `addPiFlexFormValue()` as v13 does, but its FormEngine is a different
  vintage; the backport measures the path again instead of copying this one.
- [`storagePids` lists only deleted or non-existent pages] → The query would
  restrict to pages that match nothing and the select would be empty but for
  the referenced value. Accepted: it is the integrator's list, and an empty
  result is visible rather than silent.
- [A listed page is a mount point, or a Backend User Section] →
  `getDescendantPageIdsRecursive()` walks the mounted subtree rather than the
  listed one, and skips a `doktype` 6 page with its whole subtree. Accepted and
  documented on the `docs/` page rather than worked around: both are the page
  tree semantics every other recursive page list in TYPO3 follows.
- [The page list is expanded on every invocation, uncached] → With a depth set,
  a backend list module walks the tree once per rendered value, because the
  label resolvers call the handler per value rather than per field. Accepted for
  now and named in the documentation, with the advice to keep the depth small. A
  runtime cache would be the fix, has no precedent in this repository, and is a
  change of its own if the cost is ever measured to matter.

## Backport

Recommended. The code is identical on branch `2`, and multi-site installations
there are affected in the same way.
