## Context

See `proposal.md`. On `main`:

- `ProfileFormData` has a fixed property list;
  `AbstractFormData::hasProperty()` is `property_exists()`, and an unknown
  submitted identifier ends in `invalid_profile_data` (422) from
  `ProfileUpdateValidationService::createFormData()`.
- `AcademicPersonsSettingsFactory` derives `propertyName` and `fieldName` for
  every field; nothing checks a column against TCA.
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

`custom: true` requires `fieldName`; the factory checks it against
`TcaSchemaFactory` for `tx_academicpersons_domain_model_profile` and refuses
system columns and columns the Extbase model already maps.

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
