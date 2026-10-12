## Context

Measured on branch `2` at `7ebded7b8` by reading:

- `Classes/` of `academic_persons_edit` has no `Event/` directory, and no
  `->dispatch(` or `EventDispatcherInterface` anywhere. Its two classes in
  `EventListener/` listen to core events.
- `AbstractFormData.php:95-101` calls `setPropertyOverride()` "the intended
  extension point for PSR-14 event listeners filling up data from other
  sources before the transformation runs". `setPropertyOverride()` has no
  caller outside `Tests/`.
- The guard comment in all six factories repeats it: "registered as override
  (e.g. filled up by a PSR-14 event from another source before
  transformation)" (`ProfileFactory.php:70-71`, `ContractFactory.php:63-64`,
  `AddressFactory.php:65-66`, `ProfileInformationFactory.php:56-57`,
  `EmailFactory.php:46-47`, `PhoneNumberFactory.php:46-47`).
- `Important-FormDataTransformationOnlyMapsSubmittedFields.rst:37-44` and
  `:72-76` name a PSR-14 event as the reason for the store and as the place to
  register an override.
- Test docblocks describe a listener: `EmailFactoryTest.php:139`
  ("The documented extension point: a PSR-14 listener"), `:220`, `:293`,
  `ContractFactoryTest.php:123` ("the PSR-14 case"),
  `ProfileInformationFactoryTest.php:111`, and `ProfileFactoryTest.php:160-165`,
  which says the event "is ACE-445" and the typed read is part of its scope.
  Two unit tests repeat it as well: `Tests/Unit/Domain/Factory/ProfileFactoryTest.php:81`
  ("an event registered an override value") and
  `Tests/Unit/Domain/Model/Dto/EmailFormDataTest.php:59-62` ("`setEmail()`
  exists so a PSR-14 listener can correct the submitted address").
  `Tests/Unit/Domain/Validator/AbstractFormDataValidatorTest.php:56` names "a
  template or a listener" as a reader of the error code, which no event
  hands to a listener either.
- `docs/architecture/form-data-transformation.md:38`: "A listener cannot
  write a property the configuration locks."
- The form data classes, the factories and the controllers are all
  `@internal`, and the controllers are `final`. Nobody outside the extension
  reaches the store in a supported way.
- `git tag --contains 63609482d` lists nothing: the promise is not released.
- The `Changelog/2.4/` folder of `main` is byte identical to this branch
  since ACE-896, the entry included. On `main` the docblock of
  `AbstractFormData` already names the store internal plumbing of the request
  handler, and ACE-762 recorded that this issue keeps branch `2`.

## Goals / Non-Goals

**Goals:** no file of branch `2` promises an event for the override store,
and the store reads as what it is, an internal mechanism with no caller.

**Non-Goals:** any change of what the code does. See the proposal for the
event and the typed read.

## Decisions

### Reword, keep the store

The store stays, with its methods and its tests. The factories read it, the
tests pin how, and removing it would be a code change on an `@internal` API
for no gain on a maintenance line. The wording follows `main`, adapted to
this branch, where no request handler registers overrides: the store holds
values the transformation applies even when the property was not submitted,
nothing in the extension registers one, and no event hands the form data
object to a listener.

Rejected: implementing the event (the maintainer's decision, see the
proposal), and removing the store (a code change the decision rules out).

### The comments and the tests are in scope (Decision for the maintainer)

The decision named the docblock and the `Important` entry. Six factory
comments and the comments of four functional and three unit test files
repeat the same promise, and
`ProfileFactoryTest.php:162-165` refers to ACE-445 as the issue that will
bring the event. Left alone, they contradict the reworded entry and the next
reader takes them for the truth. Recommended: reword them in the same
change, comments only. The test bodies and their assertions stay untouched.
Confirm with the maintainer before editing.

### What the `Important` entry says

The entry stays an `Important` entry of ACE-33 with its anchor
`important-ace-33-academic-persons-edit` and its file name. Nothing in the
repository references the anchor, but the rendered manual links to it from
outside.
The bullet on the store describes it as internal: applied even when not
submitted, registered by nothing in the extension, no event to reach it. The
migration section keeps "Installations without custom code need no
migration" and drops the sentence on the event. It does not point to the
event of 3.0: a 2.4 entry documents 2.4, and the 3.0 changelog documents its
own event.

No new changelog entry: the reworded file is unreleased, nothing changes at
runtime, and a second entry about a promise nobody could rely on would only
add noise.

### The 2.4 changelog folder on `main` (Decision for the maintainer)

The reworded entry makes the `Changelog/2.4/` folders of the two branches
differ. Options: mirror the reworded file to `main` in a small follow-up
commit after the merge (2.4.0 is unreleased, so it is still editable), or
leave `main` as it is. Recommended: mirror it, so the folders stay identical
as ACE-896 left them, and so `main` does not keep documenting a 2.4 event
promise either. It is not part of this change.

A mirror is a plain copy of the file. The entry has no `:ref:` and its
anchor is referenced nowhere on `main`, and `main` cites it from
`docs/architecture/form-data-transformation.md` by path only. Its sentence
that nothing registers an override describes 2.4, while on `main` the
request handler of 3.0 does. That is correct for a 2.4 entry, and
`Feature-BeforeProfileEditingWriteEvent.rst` of the 3.0 changelog documents
the event of 3.0.

## Risks / Trade-offs

- [The ACE-423 ambiguity stays: `getPropertyOverride()` returns `null` both
  for "not registered" and for "registered as null"] -> Latent while nothing
  registers an override. The reworded docblock says so, and the proposal
  names the typed read as a precondition of any future caller.
- [An integrator built on the unreleased promise] -> Not possible in a
  supported way: every class involved is `@internal`, the controllers are
  `final`, and the promise is in no tag.
