## Context

See `proposal.md` for the motivation. Verified on `main`:

- `ContactRepository::findByPid()` (:46) resolves the contact uids with a raw
  pre-query restricted to `deleted`, `hidden` (unless `$showHidden`) and the
  workspace of the contact table (:109-159, the ACE-484 language logic), then
  fetches those rows through Extbase.
- Relations are resolved by the DataMapper with a fresh query per relation
  (`DataMapper::getPreparedQuery()`): only language settings are passed on,
  never the enable-field settings of the parent query. A hidden contract
  (contract `enablecolumns`: `hidden`) therefore maps to `null`, and so does
  a hidden, timed or fe_group restricted profile (profile `enablecolumns`:
  `hidden`, `starttime`, `endtime`, `fe_group`) - with and without
  `showHiddenRecords`.
- `Contact::getContract()` (:49) and `getUnfilteredContract()` (:68) return
  that `null`; `Contract::getProfile()` (:79) returns `null` for the profile.
- `ContactsController::listAction()` hands every contact to the view and
  builds `roles` and `contactsWithoutRole` from all of them.
  `ContactsProcessor::process()` does the same for `contacts` and `roles`,
  without `contactsWithoutRole` and always without hidden records.
- `Templates/Contacts/List.html:26` passes `contact.contract.profile` (`null`)
  into the persons `Profile/Item` partial.

## Goals / Non-Goals

**Goals:**

- One place decides which contacts of a page are shown, and both entry points
  use it.
- The rule reuses Extbase's own visibility decision instead of repeating it.

**Non-Goals:**

- Extending the processor with `contactsWithoutRole`, `as` or
  `showHiddenRecords`; that is a follow-up change.

## Decisions

### A stateless provider shared by controller and processor

A new `final readonly` service `PageContactsProvider` in
`Classes/Service/` returns a `final readonly` value object `PageContacts`
(`contacts` as `list<Contact>`, `roles`, `contactsWithoutRole`) for a page uid
and the show-hidden flag. It calls `findByPid()`, drops every contact whose
`getUnfilteredContract()` is `null` or whose contract has no profile, and then
builds the role split from the rest. `getUnfilteredContract()` is used so the
check does not build the address-record display copy.

The controller keeps setting the address record provider on the returned
contacts, because that is a display concern tied to its option.

The extension's `Services.yaml` autowires `Classes/`. The processor is
instantiated by class name from TypoScript, so it becomes a public service
that receives the provider through its constructor
(`#[Autoconfigure(public: true)]`, Symfony's attribute); the controller gets it
through its existing inject method style.

Rejected: filtering in the template. Every project that overrides
`List.html` would have to repeat the guard, which is the situation today.

### Filter the resolved objects, not the pre-query

Rejected: joining contract and profile into
`resolveContactUidsForLanguage()`. It would duplicate the enable columns of
two foreign tables - including `fe_group` and the time window - plus their
workspace overlay, next to the language logic that already needed two
rewrites.

### "Show hidden records" stays about contact rows

A hidden profile is left out in both modes. Showing it would need a relation
resolution that ignores enable fields, which Extbase does not offer for
relations, and it would publish a person the persons editor has hidden.

### Decided: backport to branch `2` as a change of its own

The filtering is backported to branch `2` in a separate change, re-derived
from a diff of the contact repository, the controller and the processor on
that branch. One project carries a composer patch for this defect on 2.3.4,
and the backport lets it drop the patch. The ACE-484 pre-query may not exist
on branch `2`, so the backport must not be a cherry-pick.

### Decided: ACE-430 is narrowed to the partnership repository

ACE-430 is narrowed to the partnership repository's `findByPid()`
(`academic-partners`), with a note that contacts are covered by ACE-484.
ACE-484 already pins `ContactRepository::findByPid()` to one row per contact
and language on v13 and v14, while the partnership repository and its open
per-language decision are untouched. The decision does not change this
design.

## Risks / Trade-offs

- [PHP code reads `contacts` as a query result] → `Important-` changelog
  entry; Fluid `f:for` and `f:count` are unaffected.
- [One extra loop per rendering] → The list is the contacts of one page;
  negligible.
- [A role disappears when all its contacts are hidden] → Intended; named in
  the changelog entry.

## Migration Plan

None. Output changes on update; there is no stored data to migrate.

## Open Questions

None.
