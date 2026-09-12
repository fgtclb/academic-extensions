## Context

See `proposal.md`. On `main`, `ProfileController` of `academic_persons_edit`
is `final`, `@internal`, about 3300 lines, with fourteen mutating endpoints
(`update`, `updateSkipSync`, `createDocument`, `updateDocument`,
`toggleDocumentVisibility`, `deleteDocument`, `sortDocument`,
`createContractContact`, `updateContractContact`, `deleteContractContact`,
`toggleContractContactVisibility`, `sortContractContact`, `uploadImage`,
`deleteImage`). Each validates, then calls a factory or repository, then
`persistAndDispatchProfileUpdate()`.

`AbstractFormData` has a property override store. The controller uses it as
the carrier of submitted values (`applyDocumentFormOverrides()`,
`ProfileUpdateValidationService`), and the factories' comments mention "a
PSR-14 event from another source" filling it, but no such event exists. That
corrects the analysis, which said the store has no caller.

## Goals / Non-Goals

**Goals:**

- One event class for every write, one dispatch point per endpoint.
- A listener cannot bypass validation or the rich text sanitiser.

**Non-Goals:**

- Opening the controller for inheritance.

## Decisions

### One event with an action enum

`FGTCLB\AcademicPersonsEdit\Event\BeforeProfileEditingWriteEvent` (`final`,
implements `StoppableEventInterface`) with `getProfile()`,
`getAction(): ProfileEditingAction`, `getSectionIdentifier(): ?string`,
`getRecord(): ?AbstractEntity`, `getFields()`, `setFields(array)`,
`getRequest()`, `refuse(string $reason)`, `isRefused()`, `getReason()`.
`ProfileEditingAction` is a string-backed enum with the fourteen cases.
`setFields()` on an action without fields (delete, toggle, sort, image
removal) throws a `\LogicException`, so a listener mistake is loud.
Refusing stops propagation.

Rejected: fourteen event classes. Listeners would register fourteen times for
the common "refuse if" case, and a fifteenth endpoint would need a new class
every listener misses.

### Dispatch after validation, validate again after a change

The event is dispatched after authorisation, the action allow-list,
normalisation and validation, right before the factory call. When a listener
called `setFields()`, the new values go through the same normalisation,
validation and sanitiser once more. The extension has no event directory yet;
`Classes/Event/` is added.

Rejected: dispatching before validation. A listener would then see raw,
unnormalised payloads, and the analysis's "after validation, trust the
listener" would let a listener copy unsanitised input into a rich text field.

### Refusal is 422 `write_refused`

`throwJsonError('write_refused', 422, $reason)`, the error shape the editor
already handles; the TypeScript shows the message as text, never as HTML.

Rejected: making `ProfileController` non-final. It is internal on purpose and
changes with every editor feature.

### Decided: a new [3.x] issue, ACE-445 keeps branch `2`

A new `[3.x]` ACE issue (Version 3.0.0) is filed for this change on `main`
and linked to ACE-445 as related. The change is named after the new key.
ACE-445 keeps its scope and Version 2.4.0 for branch `2`.

The issue convention ties the Version to the summary prefix, and ACE-445
describes the missing dispatch into the property override store in the 2.x
controllers. The 3.0 event over fourteen endpoints shares no code with that.
Rejected: re-scoping ACE-445 to `main`, with or without a new issue for
branch `2`.

## Risks / Trade-offs

- [Listeners slow down every write] → One dispatch per request.
- [A listener changes values of a managed field] → The factories still apply
  their read-only and managed checks after the event.

## Open Questions

None.
