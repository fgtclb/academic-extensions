## Why

The frontend profile editor of `academic_persons_edit`
(`packages/fgtclb/academic-persons-edit`) is served by one controller of
3945 lines with 39 constructor dependencies, 113 methods and 19 plugin
actions. It was 3280 lines after ACE-262, and every editor feature since has
landed in it. Most of the size is the field definition, normalisation,
validation and serialisation of documents and contract contacts, which
cannot be unit tested while it is private to the controller. 3.0.0 is not
released yet, so the endpoint addresses can still change without breaking a
released version.

## What Changes

Two steps, two commits of one change:

- **Step 1, services.** The field handling of documents and contract
  contacts, their record lookup and writes, and the label translation with
  its core version switch move into stateless services with unit tests. The
  controller keeps its actions and addresses. Nothing observable changes.
- **Step 2, controllers.** The six document actions and the six contract
  contact actions move into two new controllers of the same plugin. The
  profile and image actions, the profile list and the editor page stay. The
  request guard (`X-Requested-With`), the JSON error answer and the write
  lifecycle (write offer to listeners, persisting, update announcement)
  become shared stateless services, not an abstract base controller. The
  editor template addresses the document and contact endpoints under their
  new controller names. Action names, payloads and answers stay the same.
- Every JSON endpoint is tested for the request guard and the anonymous
  answer, so a controller that misses the guard fails a test.
- `docs/architecture/class-design.md` drops the stale "36 dependencies" for
  the measured numbers, and further `docs/` pages naming the controller are
  corrected.

The behaviour is identical on TYPO3 v13 and v14. The label translation keeps
its v13/v14 switch in one place.

## Capabilities

### New Capabilities

- `academic-persons-edit/editor-endpoints`: how the editor's endpoints are
  addressed, which a template override has to follow, and the request guard
  and anonymous answer every endpoint keeps.

### Modified Capabilities

None.

## Impact

- `academic_persons_edit`: `ProfileController`, two new controllers, about
  nine new services, `ext_localconf.php`, `Templates/Profile/Index.html`,
  the tests that pin the controller map, the 3.0 changelog entries and the
  manual chapters naming `ProfileController`.
- `packages-dev/monorepo-shared`: the plugin controller count of the
  extension point test rises from 9 to 11.
- A site that overrides `Templates/Profile/Index.html` must take over the new
  addresses. No released version had the old ones.
- A write event listener that reads the controller name of its plugin action
  context sees the new name for document and contact writes.
- Lands before `ace-tbd-editor-translation-writes` and
  `ace-tbd-editor-editable-profiles-event`, see `design.md`.

## Non-goals

- Changing a payload, an answer, a status code or an action name.
- Moving the image upload into a controller of its own.
- New features, events or settings of the editor.
- A backport: branch `2` has the old editor with six controllers.

## Source

Board triage of 2026-10-12, ACE-507 (`[3.x]`, a follow-up of ACE-262).
