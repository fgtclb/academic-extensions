## Context

See `proposal.md` for the motivation. On branch `2`:

- `GetSelectItemsForTcaManagedTableFieldMethodTrait` of `academic_base` calls
  the `itemsProcFunc` of a field with a parameter array it builds itself:
  `items`, `config`, `TSconfig`, `table`, `field`, `effectivePid`, `site`.
  `JobController::newAction()` of `academic_jobs` is its one caller, for `type`
  and `employment_type`. The parameter array is the same as on `main`, the
  rest of the trait differs: the page comes from the `frontend.controller`
  attribute, and the labels are translated without a request argument.
- `TypeItems` and `EmploymentTypeItems` of `academic_jobs` are identical to
  `main` and hand that array to `ModifyTcaSelectFieldItemsEvent` unchanged.
  The other dispatchers, `ContractItems` and `ProfileShowFieldsItems` of
  `academic_persons` and `AddressRecordItems` of `academic_contacts4pages`, are
  reached through FormEngine only. The three address controllers of
  `academic_persons_edit` call a handler outside FormEngine too, but that
  handler, `RecordTypes`, dispatches no event.
- FormEngine passes `row` always, on v12 and v13 from
  `AbstractItemProvider::resolveItemProcessorFunction()`, in the order
  `items`, `config`, `TSconfig`, `table`, `row`, `field`, `effectivePid`,
  `site`, followed by the inline keys, and `flexParentDatabaseRow` for a
  FlexForm field only. The event declares the inline keys and
  `flexParentDatabaseRow` optional already.
- The event carries no `@api` tag on this branch, unlike `main`, where the
  extension point policy added it. That is not part of this change.
- A test cannot register a closure listener for a frontend request: the
  testing framework boots a container of its own for every request.

## Goals / Non-Goals

**Goals:**

- The declared shape matches every caller, and an existing listener that
  reads `row` itself keeps working in the job form, on v12 and v13.

**Non-Goals:**

- See the proposal.

## Decisions

### The same fix as on `main`

`row?:` in the three shapes of the event and in the two job providers, and
`'row' => []` from the trait, after `table`, with the same comment. The
maintainer decided that both branches get the same fix. The reasoning of the
`main` change holds unchanged: the optional key is the honest contract for a
helper a project may call for its own fields, and the empty row keeps a
deployed listener that reads the row itself working without a change.

Rejected, as on `main`: the defaults of the job table as the row. Its `pid`
would be invented: a job of the form lands in the storage folder of the
plugin, which the trait does not know, and `effectivePid` already carries the
page of the form honestly.

The empty row does not save a listener that reads a field of the row without
a fallback, `$parameters['row']['pid']` warns about `pid` instead of `row`.
The changelog entry and the upgrade guide say so, rather than promising
more.

### The fixture listener is registered by tag

TYPO3 v12 has no `#[AsEventListener]` attribute. The fixture extension
`test_job_select_items_listener` registers its listener with the
`event.listener` tag in `Services.yaml`, as `test_job_after_save_listener`
does on this branch. The listener class is the one of `main` without the
attribute.

The fixture reads `row` without a fallback and hands it to a method typed
`array`, while it reads the fields of the row with a fallback. It shows the
narrower case the empty row protects, not the listener of the issue, which
reads `['row']['pid']` without a fallback and still warns. With the shape
optional, PHPStan reports the read of `row`.

The fixture keeps its unguarded read with the same inline
`@phpstan-ignore offsetAccess.notFound` as on `main`, verified against the
PHPStan configuration of both core versions here: without it PHPStan
reports the offset, with it the analysis is clean. No baseline entry is
added or removed, neither baseline names the event, the trait or the
providers.

### The fixture composer.json follows this branch

`typo3/cms-core: ^12.4.22 || ^13.4`, `fgtclb/academic-jobs: ~2.4.0@dev`, no
`extra.typo3/cms.version`, and the `ext_emconf.php` ranges of the other
fixtures here, `12.4.22-13.4.99` and `2.4.0`.

### Two tests, the trait test in a new directory

The job form test is the one of `main`, unchanged. The trait test lives in
`academic-base/Tests/Functional/Controller/`, which does not exist on this
branch yet, with its fixtures in a `Fixtures/` folder next to it as the other
test directories of `academic_base` keep theirs. It extends the base test case
of `academic_base` here, which extends the test case of
`sbuerk/typo3-site-based-test-trait`. The request it builds carries no page,
so the page falls back to the root page of the site on both branches.

## Risks / Trade-offs

- [A project runs PHPStan over its listeners and gets a new report for an
  unguarded read of `row`] → That read is the defect, the report is the point
  of the change, and the changelog entry names it.
- [A listener told the job form apart by a missing `row`, with `isset()`,
  `array_key_exists()` or `?? null`] → It takes the branch meant for the
  backend now. The check that tells the two apart is
  `($parameters['row'] ?? []) === []`, which the 2.4 entry names.
- [The 2.4 entry and the upgrade guide are copied from `main`] → They name
  only what exists here: the trait, the event, the job form and the
  providers. They are copied byte for byte once the `main` change is final,
  and compared with `cmp`.
