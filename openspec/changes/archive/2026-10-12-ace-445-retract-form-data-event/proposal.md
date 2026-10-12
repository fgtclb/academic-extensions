## Why

The profile editing of `academic_persons_edit`
(`packages/fgtclb/academic-persons-edit`) promises an event that does not
exist. The docblock of the form data base class and the `Important` entry of
ACE-33 in `Documentation/Changelog/2.4/` name the per-property override store
"the intended extension point for PSR-14 event listeners", and tell
integrators to register overrides "within a PSR-14 event". The extension
dispatches no event at all, so no listener can reach a form data object, and
the store has no production caller.

The promise is not released: it came with `63609482d` (ACE-33), which no tag
contains. The latest tag is 2.3.4. Retracting it now keeps 2.4.0 from
shipping a documented extension point that is not there.

Decided by the maintainer on 2026-10-12: retract the promise for 2.4.0 by
rewording, no event on the 2.x line. Implementing the event would add a 2.x
only API that nobody asked for, and every listener would have to be
rewritten for the event of 3.0 (ACE-762), which has a different shape.

## What Changes

- The docblock of the override store names it internal plumbing of the form
  data transformation, with no caller in the extension, and promises no
  event.
- The 2.4 `Important` entry
  `Important-FormDataTransformationOnlyMapsSubmittedFields.rst` describes the
  store the same way, and its migration section no longer tells integrators
  to register overrides from an event.
- The comments that repeat the promise go with it: the guard comment of the
  six factories, the docblocks of the factory tests that call the store "the
  documented extension point", the unit test comments that name an event or
  a listener for the form data, and the rule 2 section of
  `docs/architecture/form-data-transformation.md`.

No code changes, and nothing changes at runtime on TYPO3 v12 or v13.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

None. The change rewords documentation and comments only and sets
`skip_specs: true`.

## Impact

- `Classes/Domain/Model/Dto/AbstractFormData.php` (docblock), the comment of
  the property guard in the six factories of `Classes/Domain/Factory/`.
- `Documentation/Changelog/2.4/Important-FormDataTransformationOnlyMapsSubmittedFields.rst`.
- Docblocks of `Tests/Functional/Domain/Factory/` that describe a listener,
  and comments of three unit tests below `Tests/Unit/Domain/`.
- `docs/architecture/form-data-transformation.md`.

## Non-goals

- An event. If a project on 2.x needs one, the full scope of ACE-445 as it
  stood before this change applies, and the typed read of overrides that
  ACE-423 described has to land together with it.
- The typed read itself. With no caller of the store it guards nothing, and
  the three factory tests that pin the inline type checks stay as they are.
- `main`. ACE-762 gave 3.0 an event of its own, and the docblock there is
  already correct. Whether the reworded 2.4 entry is mirrored into the
  `Changelog/2.4/` folder of `main` is a separate decision, see `design.md`.
