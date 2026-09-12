## Why

The frontend editor lets a person edit exactly the profiles their frontend user
is linked to. Delegated editing (an assistant for a chair, an office for a
department) and one project's plan to change the user-to-profile link need a
different rule. The service that decides it is `final readonly`, so today the
only way is to replace the controller.

## What Changes

- A new PSR-14 event of `academic_persons_edit`
  (`packages/fgtclb/academic-persons-edit`) lets a listener change the list of
  profiles a logged-in frontend user may edit, starting from the linked
  profiles.
- The profile list of the editor and every editing request (the edit page and
  all fourteen write endpoints) use the same, event-modified list, so a
  profile a listener grants is editable everywhere and a profile a listener
  removes is editable nowhere.
- The event is not dispatched for visitors who are not logged in.
- Without a listener nothing changes.

The behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-persons-edit/editable-profiles`: which profiles a logged-in
  frontend user may edit, and how an integrator changes that.

### Modified Capabilities

None.

## Impact

- `academic_persons_edit`: one event class, `ProfileUpdateRequestService`
  gains a single method that returns the editable profiles and needs the
  request. The service is new in the unreleased 3.0, so the signature change
  breaks no released API.
- Security relevant: a listener can widen access. Documented as such.
- Functional tests of the authorisation, integrator documentation, 3.0
  feature changelog.

## Non-goals

- A delegation data model or backend UI.
- Changing the frontend user to profile relation.
- Backporting to branch `2`.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`persons-data-15`). One of the six analysed projects plans its own code for
this; it is not built yet. No YouTrack issue is filed yet; the change is
renamed to `ace-<NNN>-editor-editable-profiles-event` when the issue is filed
after implementation.
