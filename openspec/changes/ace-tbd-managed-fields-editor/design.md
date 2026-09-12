## Context

See `proposal.md`. On `main`:

- Section read-only is `documentSections.<id>.readonly`; field read-only is the
  `readonly` validation flag, global per field
  (`academic-persons/Configuration/AcademicPersons/Settings.yaml`).
- Contact actions follow the `contracts` section
  (`ProfileController::assertContractContactActionAllowed()`), document
  actions go through `assertDocumentActionAllowed()`; both answer 403 with
  `contract_contact_action_not_allowed` / `document_action_not_allowed`.
- Every factory of `academic-persons-edit/Classes/Domain/Factory/` decides per
  property in a private `mayApplyProperty()` that knows only the validation
  set's `readOnly`/`disabled` and the submitted-property store.
- The profile carries `import_identifier` since the fe_users sync writes it
  on the profile too (`ProfileFactory.php`), so profile fields can be managed.

This change depends on `ace-tbd-managed-fields-backend` for the settings key
and `ManagedFieldResolver`.

## Goals / Non-Goals

**Goals:**

- The client and the server take the decision from the same resolver call.
- Same semantics as the existing `readonly` flag: a locked value is kept, not
  an error.

**Non-Goals:**

- Changing the section-wide `readonly` or the `actions` allow-list.

## Decisions

### The resolver feeds the field descriptors

The controller asks `ManagedFieldResolver` once per rendered or edited record
and passes the managed property names to the descriptor serialisation
(`readonly` plus a `managed` marker the TypeScript renders as a lock with the
label "synchronised"). The per-row action list drops `delete`, and `edit` when
no editable field is left.

Guessed layout — a sketch, not a design:

```text
E-mail addresses
+------------------------------+---------------------+
| max@ex.test [lock] synced    | [hide]              |
| lehre@ex.test                | [edit] [hide] [del] |
+------------------------------+---------------------+
[+ add e-mail]
```

### Factories receive the managed names as an argument

`mayApplyProperty()` gets a `list<string> $managedProperties` argument next to
the validation set and returns `false` for them. The factories stay stateless;
no request state is stored on them.

Rejected: a derived validation set with `readOnly` forced on managed fields.
It would be cached per section and therefore wrong for the next record.

### Ignore silently, refuse only actions

A submitted value for a managed field is ignored, as the `readonly` flag
already does, so a full-form save of a partly managed row succeeds. Delete and
a fully managed edit are refused with the existing 403 codes, so no new error
contract is introduced.

Rejected: answering every managed value with 403 (the analysis's first idea).
The full-form save sends every field, so the whole form would fail on one
synchronised value.

### Hide and sort stay

The sync never touches `hidden` (ACE-235 and ACE-524) nor `sorting`, so both
are local presentation and remain the owner's choice.

### Decided: no separate sorting lock

Sorting of managed rows stays the owner's choice; there is no separate lock
and the sort action stays available. It is revisited on demand.

Neither the fe_users profile factory nor its abstract base writes `sorting`,
so the order of a managed row is local presentation that the synchronisation
never overwrites. The locking requirements seen so far concern values, not
order. Rejected: dropping the sort action for managed rows.

## Risks / Trade-offs

- [Client and server disagree] → The descriptor and the factory read the same
  resolver result; a functional test posts a managed value and asserts both
  the rendered marker and the stored value.
- [An extra lookup per contact row] → Bounded by the contacts of one profile.

## Open Questions

None.
