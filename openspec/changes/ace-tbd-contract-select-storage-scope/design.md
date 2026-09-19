## Context

Measured on `main` at `f7c4c5bf1`:

- `ContractItems::itemsProcFunc()` is configured on
  `tx_academiccontacts4pages_domain_model_contact.contract` (TCA) and on
  `settings.selectedContracts` of `Configuration/FlexForms/SelectedContracts.xml`.
- `ContractRepository::getContractItemsForTcaItemsProcFunc(array $parameters)`
  returns `$this->findAll()`; `findAll()` calls
  `setRespectStoragePage(false)`.
- FormEngine passes `TCEFORM.<table>.<field>.itemsProcFunc.*` page TSconfig to
  the handler as `$parameters['TSconfig']['itemsProcFunc.']`
  (`AbstractItemProvider::resolveItemsProcessorFunction()`). Whether the same
  path reaches a FlexForm field has to be verified; FlexForm fields take their
  TSconfig from a different path.
- `ContractItems.php` and the repository method are byte-identical on `main`
  and `origin/2`.

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

### Keep the current value

FormEngine shows a value that is not among the items as invalid, and the
DataHandler may reject it on save. The currently referenced contract(s) are
therefore always part of the items, whatever the restriction says.

### The event runs after the restriction

`ModifyTcaSelectFieldItemsEvent` receives the restricted items, so a project
listener keeps the last word.

## Risks / Trade-offs

- [An integrator lists the wrong pages] → The select shows fewer contracts and
  existing values stay; the setting is documented with an example.
- [The FlexForm field does not receive the TSconfig] → The first task checks
  it; if it does not, the change documents the supported path or adds one, and
  the proposal is updated before implementing.

## Backport

Recommended. The code is identical on branch `2`, and multi-site installations
there are affected in the same way.
