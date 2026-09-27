## Context

See `proposal.md`. On `main`, re-checked on 2026-09-27 at `7a8a6241e`:

- Section read-only is `documentSections.<id>.readonly`. Field read-only is the
  `readonly` validation flag, global per field
  (`academic-persons/Configuration/AcademicPersons/Settings.yaml`).
- Contact actions follow the `contracts` section
  (`ProfileController::assertContractContactActionAllowed()`), document
  actions go through `assertDocumentActionAllowed()`. Both answer 403 with
  `contract_contact_action_not_allowed` or `document_action_not_allowed`.
- Every factory of `academic-persons-edit/Classes/Domain/Factory/` decides per
  property in a private `mayApplyProperty()` that knows only the validation
  set's `readOnly`/`disabled` and the submitted-property store.
- The profile carries `import_identifier` since the fe_users sync writes it
  on the profile too (`ProfileFactory.php`), so profile fields can be managed.
- **Not as first assumed:** a submitted read-only value is not ignored by the
  endpoints. `ProfileUpdateValidationService::createFormData()` answers
  `Unknown profile property`, and `normalizeAndValidateDocumentFields()` and
  `normalizeAndValidateContractContactFields()` answer "This field cannot be
  changed.", all with 422. The factories' own check is never reached for such
  a value.
- The browser sends every field of an open contract or contact, read-only
  ones included (`documents.ts`, `collectDocumentValues()` and
  `submitContractContact()`), so a record with a locked field cannot be saved
  from the browser today. The profile form sends editable fields only.
- A read-only select or checkbox is rendered enabled: HTML has no read-only
  state for either, and the field builder binds `readonly` on inputs and
  textareas only.
- In a translated site language the editor holds translation overlays, and
  an Extbase delete of an overlay removes the default-language record behind
  its uid (found in review, see "Translations" below and ACE-761).

This change depends on `ace-758-managed-fields-backend` for the settings key
and `ManagedFieldResolver`.

## Goals / Non-Goals

**Goals:**

- The client and the server take the decision from the same resolver call.
- One answer for every lock: a submitted locked value is kept, not an error.

**Non-Goals:**

- Changing the section-wide `readonly` or the `actions` allow-list.
- Changing the synchronisation switch and the visibility switch, whose
  endpoints carry a single value.

## Decisions

### The resolver answers property names for a domain record

`ManagedFieldResolver` of `academic_persons` gets a second entry point that
takes a profile, contract or contact model and answers the managed property
names, built from the same checks as the column answer for FormEngine. The
model gives the import identifier, its language, `skipSync` of a profile, the
profile of a contract and the contract of a contact. The parents are read
from the database past their visibility, as for the backend.

Rejected: an editor-side copy of the decision. Two copies of "managed" would
drift, and the backend and the editor must agree on a record.

### A managed row is a row with at least one managed field

The row locks, no delete and possibly no edit, apply to a synchronised row
only when the map names at least one field of its record type. An
installation that runs the synchronisation and declares nothing keeps every
action it has today.

Rejected: locking every row with an import identifier. It would change the
editor of every installation with the fe_users sync on update, without any
configuration asking for it.

### The resolver feeds the field descriptors

The controller asks the resolver once per rendered or edited record and
passes the managed property names on:

- document and contact field descriptors get `readOnly` and a `managed`
  marker that the field builder renders as a "synchronised" badge next to
  the label, and `required` is dropped for such a field, as for
  `frontendreadonly`, because the owner cannot supply a value,
- the profile fields of the page get a copy of their validation with
  `readOnly` set and `required` cleared, plus the same marker, built per
  request,
- a contact item carries `managed`, `editable` and `deletable`, and a
  contract row gets its action list without `delete`, and without `edit` when
  no editable field is left, with the same badge as the hidden marker.

Guessed layout, a sketch rather than a design:

```text
E-mail addresses
+------------------------------+---------------------+
| max@ex.test [lock] synced    | [hide]              |
| lehre@ex.test                | [edit] [hide] [del] |
+------------------------------+---------------------+
[+ add e-mail]
```

The rejected alternative of the first draft, a derived validation set with
`readOnly` forced on managed fields, was rejected because a validation set is
cached per section. The copies here are per field and per request, never
cached, so the reason does not apply to them.

### Factories receive the managed names as an argument

`mayApplyProperty()` gets a `list<string> $managedProperties` argument next to
the validation set and returns `false` for them, in the five factories of
records with an import identifier. The factories stay stateless, no request
state is stored on them. The endpoints already drop such a value, so this is
the second line, the same one `readOnly` has in these factories.

### Ignore a locked value, refuse only actions

A submitted value for a field locked by the map, `readonly`,
`frontendreadonly` or `disabled` is dropped before validation and the rest
of the request is stored. A field the section does not know is still refused
with 422. Delete of a managed row and edit of a fully managed row are refused
with the existing 403 codes, in the form endpoints as well as in the write
endpoints, so no new error contract is introduced.

This is also the fix of the existing read-only save: the browser keeps
sending every field, and the server no longer fails on the locked ones.

Rejected: answering every locked value with 422 and leaving locked fields out
of the browser's request. It keeps a strict contract, but every client that
sends a whole record would have to know the locks, and the validation
settings already promise that a locked value is ignored.

Rejected: ignoring managed values only. Two answers for "locked" side by
side, and the read-only save would stay broken.

### Translations

The resolver gets a third entry point that decides on the stored
default-language row by the model's uid, and a translation overlay is decided
with it:

- **Shared fields stay locked.** A managed field whose column all languages
  share (`l10n_mode` `exclude`, such as the website or the type of a phone
  number) is managed on the translation too. The backend shows such a column
  read-only on a translation, and a value written into the translation row
  would be replaced by the next translation synchronisation anyway.
- **Translatable fields are editable.** A managed field whose column is
  translated, such as the contract position, is text of the translation,
  which the synchronisation does not write, so it stays editable, as in the
  backend. The edit of a row follows the same answer.
- **The delete follows the default-language record**, because that is the row
  an Extbase delete of an overlay removes. A row the synchronisation owns
  therefore cannot be deleted in any language.

Rejected: locking every managed field on a translation. The backend leaves a
translated position editable, and so should the editor.

That the delete of an overlay removes the default-language record and leaves
the translation, and that contacts cannot be reached from a translated
language at all, are older defects, filed as ACE-761.

### Disabled select and checkbox for a read-only field

The field builder renders a read-only select or checkbox disabled. The value
the browser sends comes from the editor's state, not from the control, so a
disabled control does not drop it from the request.

### Hide and sort stay

The sync never touches `hidden` (ACE-235 and ACE-524) nor `sorting`, so both
are local presentation and remain the owner's choice. The visibility toggle
of a contact keeps following the `edit` action of the `contracts` section,
but not the managed check.

### Decided: no separate sorting lock

Sorting of managed rows stays the owner's choice. There is no separate lock
and the sort action stays available. It is revisited on demand.

Neither the fe_users profile factory nor its abstract base writes `sorting`,
so the order of a managed row is local presentation that the synchronisation
never overwrites. The locking requirements seen so far concern values, not
order. Rejected: dropping the sort action for managed rows.

## Risks / Trade-offs

- [Client and server disagree] → The descriptor and the endpoint read the
  same resolver result. A functional test posts a managed value and asserts
  both the rendered marker and the stored value.
- [A client relied on the 422 for a locked value] → The editor never did, it
  failed on it. The changelog names the new answer.
- [An extra lookup per contact row] → Bounded by the contacts of one profile.

## Open Questions

None.
