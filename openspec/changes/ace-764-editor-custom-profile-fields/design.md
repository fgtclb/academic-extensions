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
    fieldType: input
    renderType: text
    validators: [maxLength:30]
```

`custom: true` requires `fieldName`. The property name of a project field is
its identifier, which is also the key the editor submits. The factory records
the field and reads no TCA (decided 2026-09-28, see below). A project field
without a `fieldName` is kept with an empty column, so that the check below
names the mistake instead of the field disappearing. Only a YAML `true`
declares one.

### Where a wrong column fails (decided 2026-09-28)

A project column is allowed when the compiled profile TCA has it, it is no
system column (`uid`, `pid`, the `t3ver_*` columns and every column the `ctrl`
section names for the core), no column of the shipped `Profile` model (its
properties, underscored), and its TCA type is `input`, `text`, `email`, `link`,
`number` or `check`. A project's model XCLASS does not count as the shipped
model, so a project that maps its column there can still declare it.

Found while implementing (2026-09-28), and checked by the same class: the
identifier of a project field must not be a property of the shipped model,
because the editor would treat the submitted value as that property and write
the model, and the renderers `select` and `combinedLink` are refused, following
the decision on scalar renderers below. The `ctrl` keys taken as system
columns are the ones naming a column the core or the workspaces fill, not
`label` or `searchFields`, which name ordinary columns and may name the project
column itself.

Decided 2026-09-28: the renderer has to fit the type, a checkbox for a `check`
column and for nothing else, rich text for a `text` column, and an `email` or
`link` column needs the `email` or `url` validator. The DataHandler checks those
two types itself, empties an invalid value and reports an error, which comes
after the Extbase write of the request. Without the validator an invalid address
would empty the column and answer `500` with the regular fields already stored.
A `link` column also has to allow the link type `url`, and a project field with
the `url` validator takes `http` and `https` addresses only. The validator lets
a `t3://` link through, which the DataHandler resolves and empties when it
resolves to a type the column does not allow, or to nothing.

The rules live in `ProjectProfileFieldCheck` of `academic_persons`, which reads
a TCA array: the listener hands it the TCA of the event, the editor one built
from the TCA schema of the request. One implementation, so the two cannot
disagree about a column.

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
  The page checks every project field and fails with
  `InvalidProjectProfileFieldException`. A write checks the project fields it
  names before their values are validated, and again after a listener of the
  write event replaced the fields, and answers `500` with the error
  `invalid_project_field` and the message: the mistake is the installation's,
  not the request's, and no validation of the value may answer first. A write
  naming no project field keeps working.
- The editor's check is not redundant with the listener's. A listener ordered
  after the settings can still remove or change the column, and that is also
  the only way a functional test reaches it: the notice of a column refused at
  boot is raised while the test instance compiles its TCA, where PHPUnit drops
  it from the expectations of the test and still fails the run on it. The
  tests of the notice call the listener in the test body and collect the
  notice with an error handler of their own, because PHPUnit 11.5 counts even
  an expected deprecation unless the whole test ignores deprecations.

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
datamap on the edited row (the translation uid for an overlay, resolved by
`LocalizedProfileUidResolver` like the image writes), and only then announces
the update, so the translation sync sees the change. DataHandler keeps history,
the reference index and the hooks. `DataHandlerExecutionContext` is `@internal`
to the record synchronisation, and its doc comment is widened to the monorepo's
extensions.

Changed while implementing: the run uses `runAsLiveBackendUser()`, not
`runAsBackendUser()`, and is marked `ProfileWriteCorrelation::Internal`, as the
visibility switch of the editor is. A backend user who happens to be logged in
must not drop a column through their permissions, and the backend save
announcement must not announce the write a second time. A workspace preview is
refused with `409` before anything is written, as it is for the visibility and
the image.

Decided 2026-09-28: on a translation the DataHandler drops a column with
`l10n_mode: exclude` from the data of the row, and one with
`allowLanguageSynchronization` while the `l10n_state` takes it from the parent,
without an error. The write sends an excluded column to the default-language
record, from which the same run carries it into the translations, and marks a
synchronised column `custom` in the `l10n_state` of the translation, as the
backend does when an editor types a value of the translation's own. The answer
of the request carries the values read back after the write.

A submitted key is looked up among the project fields first, because the form
data object has a property of its own for the names of the project values.

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

A checkbox renderer of a profile field, which no shipped field uses, showed the
labels of the visibility switch, `Public` and `Private`. It now shows `Yes` and
`No` (`profileEditing.field.checked` and `profileEditing.field.unchecked`),
because a project checkbox such as a guest lecturer flag is neither.

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
