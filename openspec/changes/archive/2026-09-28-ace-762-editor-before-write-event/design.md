## Context

See `proposal.md`. On `main`, `ProfileController` of `academic_persons_edit`
is `final`, `@internal`, about 3600 lines, with fifteen mutating endpoints
(`update`, `updateSkipSync`, `updateVisibility`, `createDocument`,
`updateDocument`, `toggleDocumentVisibility`, `deleteDocument`,
`sortDocument`, `createContractContact`, `updateContractContact`,
`deleteContractContact`, `toggleContractContactVisibility`,
`sortContractContact`, `uploadImage`, `deleteImage`). The analysis counted
fourteen. `updateVisibility`, the owner's visibility switch (ACE-50), came
after it. Each endpoint validates, then calls a factory, a repository or a
writer, then announces the change with `AfterProfileUpdateEvent`. Nothing is
dispatched before a write.

`AbstractFormData` has a property override store. The controller uses it as
the carrier of submitted values (`applyDocumentFormOverrides()`,
`ProfileUpdateValidationService`), and the factories' comments mention "a
PSR-14 event from another source" filling it, but no such event exists. That
corrects the analysis, which said the store has no caller.

The endpoints already drop a submitted value of a locked field (managed,
`readonly`, `frontendreadonly`, `disabled`) since ACE-760, and
`ManagedRecordLocks` refuses the delete and the edit of a synchronised row.
The event must not become a way around either.

## Goals / Non-Goals

**Goals:**

- One event class for every write, one dispatch point per endpoint.
- A listener cannot bypass validation, the rich text sanitiser or a lock.

**Non-Goals:**

- Opening the controller for inheritance.

## Decisions

### One event with an action enum

`FGTCLB\AcademicPersonsEdit\Event\BeforeProfileEditingWriteEvent` (`final`,
implements `StoppableEventInterface`) with `getProfile()`,
`getAction(): ProfileEditingAction`, `getSectionIdentifier(): ?string`,
`getRecord(): ?AbstractEntity`, `getContract(): ?Contract`, `getFields()`,
`setFields(array)`, `getPluginControllerActionContext()`,
`refuse(string $reason)`, `isRefused()`, `getReason()`.
`ProfileEditingAction` is a string-backed enum with the fifteen cases, valued
by the action names, and `carriesFields()`. `setFields()` on an action without
fields throws a `\LogicException`, so a listener mistake is loud: the request
fails with 500 and stores nothing. Refusing stops propagation.

Six actions carry fields a listener may replace: the profile, the skip-sync
switch, and the create and update of a document and of a contact. The others
report what they do in `getFields()`, read only: `['hidden' => bool]` for the
three visibility writes, `['direction' => 'up'|'down']` or
`['order' => list<int>]` for a sort, nothing for a delete or an image.

`getRecord()` is the document or contact that is updated, hidden, deleted or
moved, the uploaded image reference for an upload and the stored one for a
removal. `getContract()` is the contract of a contact write, which the record
of a new contact cannot give.

The event carries the plugin action context of `academic_base` rather than a
bare request, as the event rules of `docs/architecture/class-design.md` ask of
every event a plugin action dispatches (ACE-749, written after this change was
planned). Those rules name events `Modify…` or `After…`. The name
`BeforeProfileEditingWriteEvent` stays, and the rules gain `Before…Event` for
a listener that may refuse something about to happen.

Rejected: fourteen event classes. Listeners would register fourteen times for
the common "refuse if" case, and a fifteenth endpoint, which is what
`updateVisibility` became, would need a new class every listener misses.
Rejected: `ModifyProfileEditingWriteEvent`, which fits the rules unchanged but
hides the refusal, the reason most listeners exist.

### Dispatch after validation, validate again after a change

The event is dispatched after authorisation, the action allow-list, the locks
of the synchronisation on the row, normalisation and validation, right before
the factory or writer call. The sort by a complete order checks the order
before the dispatch, which `reorderDocumentRecords()` did on its own
before. The image upload dispatches after the translated profile row is
resolved, inside the block that removes the uploaded file on a failure, so a
refused upload leaves no file.

The fields a listener sees are the submitted values in their JSON shape,
reduced to the keys the normalisation accepted, so a locked field the browser
sent is not among them. When a listener called `setFields()`, the new values
go through the same normalisation, validation and sanitiser once more:
`createFormData()` and the validator for the profile, the document and contact
normalisers for the rest. A locked field among them is dropped as a submitted
one is, and the factories keep their own guard. Listeners never reach the
override store.

Rejected: dispatching before validation. A listener would then see raw,
unnormalised payloads, and the analysis's "after validation, trust the
listener" would let a listener copy unsanitised input into a rich text field.
Rejected: handing the listener the normalised values (dates as `DateTime`,
selects as entities). They could not be validated again, and a listener would
have to build entities to change a select.

### Refusal is 422 `write_refused`

`throwJsonError('write_refused', 422, $reason)`, the error shape the editor
already handles. Every request path of the TypeScript already shows the
`message` of a failed request as text through `textContent`, so no TypeScript
changes, and tests hold the three editors a person types into.

`deleteImageAction()` answered every `PropagateResponseException` raised
inside its `try` with 500, the 404 and 409 of `requirePersistedProfileUid()`
included. It rethrows it now like the other endpoints, which the refusal needs
and which makes those two answers the ones the documentation names.

Rejected: making `ProfileController` non-final. It is internal on purpose and
changes with every editor feature.

### Decided: a new [3.x] issue, ACE-445 keeps branch `2`

A new `[3.x]` ACE issue (Version 3.0.0) is filed for this change on `main`
and linked to ACE-445 as related. The change is named after the new key.
ACE-445 keeps its scope and Version 2.4.0 for branch `2`.

The issue convention ties the Version to the summary prefix, and ACE-445
describes the missing dispatch into the property override store in the 2.x
controllers. The 3.0 event over fifteen endpoints shares no code with that.
Rejected: re-scoping ACE-445 to `main`, with or without a new issue for
branch `2`.

## Risks / Trade-offs

- [Listeners slow down every write] → One dispatch per request.
- [A listener changes values of a managed field] → The replaced values are
  normalised again and a locked field is dropped, and the factories still
  apply their read-only and managed checks after the event.
- [A listener refuses with markup in the reason] → The editor writes the
  reason as text.
