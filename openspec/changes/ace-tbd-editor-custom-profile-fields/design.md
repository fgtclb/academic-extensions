## Context

See `proposal.md`. On `main`:

- `ProfileFormData` has a fixed property list;
  `AbstractFormData::hasProperty()` is `property_exists()`, and an unknown
  submitted identifier ends in `invalid_profile_data` (422) from
  `ProfileUpdateValidationService::createFormData()`.
- `AcademicPersonsSettingsFactory` derives `propertyName` and `fieldName` for
  every field; nothing checks a column against TCA.
- **Corrected 2026-09-28:** before `ace-763-settings-tca-after-overrides`,
  the first TCA file that asked for the graph built it, before any TCA
  override had run, so the factory could not check a column against the
  TCA. `ace-763-settings-tca-after-overrides` moves the merge into
  a listener of `AfterTcaCompilationEvent` and is merged before this change.
- `DataHandlerExecutionContext` of `academic_persons` already provides a
  backend user for DataHandler runs in frontend and CLI context, and
  `RecordSynchronizer` uses it for the translation sync.

## Goals / Non-Goals

**Goals:**

- No model property, no XCLASS, no dynamic property on a DTO.
- One validation pass for regular and project fields, before anything is
  written.

**Non-Goals:**

- Select renderers with options (see the decision below).

## Decisions

### Settings shape

```yaml
profile:
  namePrefix:
    custom: true
    fieldName: tx_test_prefix
    fieldType: text
    renderType: input
    validators: [maxLength:30]
```

`custom: true` requires `fieldName`. The property name of a project field is
its identifier, which is also the key the editor submits. The factory records
the field and reads no TCA (decided 2026-09-28, see below).

### Where a wrong column fails (decided 2026-09-28)

A project column is allowed when the compiled profile TCA has it, it is no
system column (`uid`, `pid`, the `t3ver_*` columns and every column the `ctrl`
section names), no column of the shipped `Profile` model (its properties,
underscored), and its TCA type is `input`, `text`, `email`, `link`, `number`
or `check`. A project's model XCLASS does not count as the shipped model, so a
project that maps its column there can still declare it.

- **While the TCA is compiled**, the settings listener merges the validators
  of an allowed project column like any other, so `required` and `readOnly`
  reach the backend form. For a column that is not allowed it merges nothing
  and raises an `E_USER_DEPRECATED` notice naming the column and the reason.
  A functional test run fails on it, the backend and the install tool keep
  working. The compiled TCA is cached, so the notice comes once per cache
  build. A deprecation rather than a warning because it is the one level a
  TYPO3 installation already routes to a log of its own and that the test
  suites of this repository and of most projects turn into a failure. It is
  not a real deprecation, and the message says what is wrong.
- A regular field of the settings without a column stays silently left out of
  the merge, as `ace-763-settings-tca-after-overrides` made it. Only a
  declared project field raises the notice, so the fixture of that change keeps
  passing under `failOnDeprecation`.
- **The editor** checks the same rules against the TCA schema whenever it
  renders or writes project fields, and fails with a message naming the column.
  Where deprecation logging is off, as in most production installations, that
  failure is what an integrator sees, which is why it has to name the column.

Rejected: throwing while the TCA is compiled. A typo would take down the
backend, the frontend and the install tool, which builds the TCA through the
same event, until the cache is flushed from the command line. Rejected:
skipping and logging, which lets a typo go unnoticed.

### A value bag on the form data object

`ProfileFormData` gets `getCustomValues()`/`setCustomValue()`, filled by
`ProfileUpdateValidationService` for identifiers the settings mark as custom.
Validators run on the bag with the same `ValidationSet` entries.

Rejected: a dynamic `__get()`/`__set()` on the DTO. It hides typos from
phpstan and from the property-existence check that rejects unknown payloads
today.

### DataHandler writes the columns after the Extbase save

After `persistAll()`, the controller writes the bag through one DataHandler
datamap on the edited row (the translation uid for an overlay), inside
`DataHandlerExecutionContext::runAsBackendUser()`. DataHandler keeps history,
the reference index and the hooks, so the translation sync sees the change.
`DataHandlerExecutionContext` is `@internal` to the record synchronisation;
its doc comment is widened to the monorepo's extensions.

Rejected: a `QueryBuilder` update of the columns. No history, no hooks, and a
second write path that bypasses the workspace handling the rest of the editor
relies on. Rejected: XCLASSing the DTO and factory (one project today).

Guessed layout — a sketch, not a design:

```text
Personal data
  Title [Prof. Dr.]  First name [Anna]  Name prefix* [von]  Last name [Berg]
  * project field (tx_test_prefix)
```

### Decided: scalar renderers only, selects as a follow-up

The first version supports the scalar renderers only. Select renderers that
take their options from the column's TCA `items` are a follow-up.

Every project field seen so far is scalar: varchar, bigint, text and
mediumtext columns and one checkbox. Rejected: select renderers with TCA
`items` options now. Also rejected: select options declared in
`Settings.yaml`, which would duplicate the TCA as a second source of truth.

## Risks / Trade-offs

- [Two writes in one request: the Extbase save succeeds and the DataHandler
  write fails] → Values are validated before either write, so only an
  infrastructure error remains; it is logged and answered 500, and the
  Extbase part stays stored. Documented.
- [A project column with TCA `readOnly`] → The editor honours the settings
  flags only; the TCA of the project column is the project's business.

## Open Questions

None.
