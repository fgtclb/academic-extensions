## Context

This is the backport of the `main` change. Everything it decides holds here as
well; what follows is the measurement that says so, and the six places the
patch had to be adapted.

Measured on 2026-09-20 on this branch at `caeef5dc2` against the `main` change,
with TYPO3 v12.4.45 installed rather than recalled:

| Fact the change relies on                                                                          | v12.4.45                                                          |
|----------------------------------------------------------------------------------------------------|-------------------------------------------------------------------|
| `$parameters['TSconfig']` is the content of `itemsProcFunc.`, `null` when the field has none       | same - `AbstractItemProvider::resolveItemProcessorFunction()`     |
| `TcaFlexProcess` hands the sheet's subtree to the nested compiler as the parent table's `TCEFORM.` | same - `modifyDataStructureAndDataValuesByFlexFormSegmentGroup()` |
| `getSimplifiedDataStructureIdentifier()` reduces `*,<cType>` to `<cType>`                          | same, with the same `!== 'list' && !== '*'` guard                 |
| `processSelectFieldValue()` keeps only values present in the items without a `foreign_table`       | same                                                              |
| `addInvalidItemsFromDatabase()` acts on `selectSingle` only                                        | same                                                              |
| DataHandler runs an `itemsProcFunc` for `check` and `radio` only, never for `select`               | same, two call sites                                              |
| `PageRepository::getDescendantPageIdsRecursive(int, int, int, array, bool)`                        | same signature                                                    |
| Extbase `in()` rejects an empty list with `BadConstraintException` 1484828466                      | same, so both guards stay necessary                               |

`ContractItems.php` is byte-identical between the branches, and so are
`getContractItemsForTcaItemsProcFunc()`, `findAll()` and `ContractItemsTest.php`.

One v12/v13 difference is **not** introduced by this change but decides what the
always-include rule can promise: an Extbase backend query returns hidden records
on v13 and not on v12, because v12 adds `BackendUtility::BEenableFields()` in
`Typo3DbQueryParser::getBackendConstraintStatement()` and v13 does not (ACE-672).
A referenced contract that is hidden is therefore offered on v13 and dropped on
v12 - before this change as well as after it, since `findForBackendSelect()`
leaves `ignoreEnableFields` exactly where `findAll()` had it. The delta spec says
so rather than promising more than the code does; changing it is the open
question `ContractItems` carries a `@todo` for, and is not this change's to
answer.

What had to be adapted:

1. **PHP 8.1.** `ContractSelectScope` and `ContractSelectScopeResolver` are
   `final class` with promoted `public readonly` properties rather than
   `final readonly class`. This branch has no `readonly class` declaration at
   all.
2. **`TcaDatabaseRecord` is not a public service on TYPO3 v12**, so the two
   functional tests reach it through `GeneralUtility::makeInstance()` - the
   shape `academic-partners`' `PartnerSelectOrderTest` already uses.
3. **The data structures are split per core version** here,
   `Configuration/FlexForms/Core12|Core13/SelectedContracts.xml`, and are
   registered with `ExtensionManagementUtility::addPiFlexFormValue()` directly
   rather than through `TcaManipulator`. Both files carry the same element and
   one `ROOT` sheet, so the TSconfig path is the same on both versions - which
   the tests prove by running on both rather than by argument.
4. **The changelog entries go to `Documentation/Changelog/2.4/`.**
5. **`docs/architecture/backend-select-items.md` is written for this branch**,
   not copied: `ItemProcessingService::processItems()` is a TYPO3 v14 path that
   does not exist here, the identifier is derived the same way on both versions
   here, the inventory counts 24 configured fields rather than 21, and the
   three frontend edit controllers of `academic-persons-edit` that assemble
   handler parameters exist only on this branch.
6. **`class-design.md` needed its readonly counts re-measured** (191/190 became
   207/206), because they were stale before this change and it adds to them.

## Goals / Non-Goals

Beyond the proposal's scope:

- **Goal**: the restriction is resolved in one place that both fields and every
  caller of the repository method reach, so a project calling the repository
  directly gets the same items as FormEngine.
- **Non-goal**: reading the backend user's `webmounts` or `db_mountpoints`. The
  setting is an integrator's declaration of where contracts live, not a
  permission check.

## Decisions

The decisions are the `main` change's and are not re-argued here; the three
that shape the code:

### Opt-in page TSconfig, not a default

Without the setting nothing changes, which is the only default that cannot
empty a select. Respecting the storage page empties it wherever none is
configured (ACE-431); scoping to the site of the edited record breaks the
common layout of one shared persons folder outside every site; a site setting
would be new infrastructure for something page TSconfig already scopes per
site.

### The repository method keeps its signature; a value object does the reading

`getContractItemsForTcaItemsProcFunc(array $parameters)` stays as it is and
resolves the scope itself, because that is what its docblock promises and
because it is the seam a project already calls. The parsing lives in
`Backend\FormEngine\ContractSelectScope` and its resolver, and the repository
passes the two domain level inputs to a new `findForBackendSelect()`.

### The page list is expanded with `PageRepository`, enable fields bypassed

`getDescendantPageIdsRecursive($pageId, $depth, 0, [], true)`: a storage folder
is regularly hidden, and a backend select that silently skips a hidden folder
is the same defect as the one this change fixes.

## Risks / Trade-offs

- [An integrator lists the wrong pages] → The select shows fewer contracts and
  existing values stay; the setting is documented with an example for both
  fields, including the flex path.
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
  a backend list module walks the tree once per rendered value, because
  `BackendUtility::getLabelFromItemlist()` calls the handler per value rather
  than per field. Accepted and named in the documentation, with the advice to
  keep the depth small. A runtime cache would be the fix, has no precedent in
  this repository, and is a change of its own if the cost is ever measured to
  matter.
