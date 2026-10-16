## Context

See `proposal.md` for the motivation. On `main`:

- `GetSelectItemsForTcaManagedTableFieldMethodTrait` of `academic_base` calls
  the `itemsProcFunc` of a field with a parameter array it builds itself:
  `items`, `config`, `TSconfig`, `table`, `field`, `effectivePid`, `site`.
  `JobController::newAction()` of `academic_jobs` is its one caller, for `type`
  and `employment_type`. No other class of the academic extensions uses it.
- `TypeItems` and `EmploymentTypeItems` of `academic_jobs` hand that array to
  `ModifyTcaSelectFieldItemsEvent` unchanged. The other dispatchers,
  `ContractItems` and `ProfileShowFieldsItems` of `academic_persons` and
  `AddressRecordItems` of `academic_contacts4pages`, are reached through
  FormEngine only, which passes `row` on every record.
- FormEngine passes `row` always, on v13 from
  `AbstractItemProvider::resolveItemProcessorFunction()`, on v14 from
  `ItemProcessingService::processItems()`. It passes the inline keys always
  as well, empty strings outside an inline relation, and
  `flexParentDatabaseRow` for a FlexForm field only. The event declares the
  inline keys and `flexParentDatabaseRow` optional already.
- A test cannot register a closure listener for a frontend request: the
  testing framework boots a container of its own for every request.

## Goals / Non-Goals

**Goals:**

- The declared shape matches every caller, and an existing listener that
  reads `row` itself keeps working in the job form.

**Non-Goals:**

- See the proposal.

## Decisions

### Both: `row?:` in the shapes, and `row => []` from the trait

Declaring `row` optional is the honest contract, because the trait is
`@api` and a project may call it for its own fields, and because a listener
cannot know which caller dispatched the event. Passing `[]` keeps a listener
that reads `$parameters['row']` itself working without a change. Either alone
falls short: the shape alone leaves deployed listeners failing in the job
form until their authors run static analysis, the empty row alone keeps a
shape that tells an author the key is always there.

Rejected: the defaults of the job table as the row. Its `pid` would be
invented: a job of the form lands in the storage folder of the plugin, which
the trait does not know, and `effectivePid` already carries the page of the
form honestly.

The empty row does not save a listener that reads a field of the row without
a fallback, `$parameters['row']['pid']` warns about `pid` instead of `row`.
The manuals and the changelog say so, rather than promising more.

### The shapes of the backend-only providers stay

`ContractItems`, `ProfileShowFieldsItems`, `AddressRecordItems` and
`ContractRepository::getContractItemsForTcaItemsProcFunc()` keep
`row: array<string, mixed>`. FormEngine is their only caller, and their own
code reads the row. PHPStan accepts handing their array to the event, whose
shape is the wider one now.

### The fixture listener keeps its unguarded read

The fixture extension `test_job_select_items_listener` reads `row` without a
fallback and hands it to a method typed `array`, while it reads the fields
of the row with a fallback. It shows the narrower case the empty row
protects, not the listener of the issue, which reads `['row']['pid']`
without a fallback and still warns. With the shape optional, PHPStan
reports the read of `row`, and the
fixture carries an inline `@phpstan-ignore offsetAccess.notFound` with the
reason, as `test_plugin_view_event` does for its leftover listener. No
baseline entry is added or removed: neither baseline names the event, the
trait or the providers.

### Two tests

A functional test of the job form with the fixture listener, proving the
user-visible defect, and a functional test of the trait that pins the keys it
passes, in the order of TYPO3 v13. TYPO3 v14 moves `TSconfig` to the end,
which a handler reading the keys by name does not notice. The trait reads
page TSconfig through
`BackendUtility`, which needs a database, so a unit test of it would have to
replace static calls.

## Risks / Trade-offs

- [A project runs PHPStan over its listeners and gets a new report for an
  unguarded read of `row`] → That read is the defect, the report is the point
  of the change, and the changelog entry names it.
- [The 2.4 entry and the upgrade guide are copied to branch `2`] → They name
  only what exists there: the trait, the event, the job form and the providers,
  with no reference to the developer chapters `main` has alone.
