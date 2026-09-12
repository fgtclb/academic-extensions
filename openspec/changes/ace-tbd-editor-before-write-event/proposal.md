## Why

Projects need to veto or complete a write of the frontend editor: require
metadata on a document, keep a value fixed, fill a field from another source.
On 3.0 the editor controller is `final` and `@internal`, has private helpers,
and offers only `AfterProfileUpdateEvent` after persistence. There is nothing
before a write, and nothing at all for documents, contacts and the image. The
2.x ways (XCLASS, controller copies) are dead on 3.0.

## What Changes

- A new PSR-14 event of `academic_persons_edit`
  (`packages/fgtclb/academic-persons-edit`) is dispatched before every write
  of the frontend editor: the profile form, the skip-sync toggle, the five
  document endpoints, the five contact endpoints and the two image endpoints.
- A listener sees the profile, the action, the section and the record, the
  normalised submitted values, and the request.
- A listener can replace the submitted values; the replaced values run through
  the same validation and sanitising as submitted ones.
- A listener can refuse the write with a reason; the endpoint answers 422
  `write_refused` with that reason, stores nothing, and the editor shows the
  reason.
- Requests that fail authentication, authorisation, the action allow-list or
  validation never reach a listener.

The behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-persons-edit/profile-editing-write-event`: the extension point an
  integrator uses to veto or complete a write of the frontend editor.

### Modified Capabilities

None.

## Impact

- `academic_persons_edit`: one event class, one backed enum of the editing
  actions, a dispatch in each of the fourteen mutating endpoints, the
  TypeScript error display for `write_refused`.
- The factories' comments that already describe "a PSR-14 event from another
  source" become true.
- Developer documentation, integrator documentation with an example listener,
  a 3.0 feature changelog.

## Non-goals

- Making the controller extensible by inheritance.
- An event after each write; `AfterProfileUpdateEvent` stays the one
  announcement.
- The branch `2` controllers; ACE-445 there stays a change of its own.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`persons-data-13`). Two of the six analysed projects carry their own code for
this today. No YouTrack issue is filed yet; a new `[3.x]` issue is filed for
this change, and the change is renamed to
`ace-<NNN>-editor-before-write-event` when it is filed after implementation.

Relates to ACE-445, which keeps the branch `2` scope.
