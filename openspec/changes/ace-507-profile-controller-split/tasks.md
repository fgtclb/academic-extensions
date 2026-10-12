## 1. Preconditions

- [ ] 1.1 Confirm the three decisions of `design.md` with the maintainer:
  both steps before the 3.0.0 tag and the controller names `Document` and
  `ContractContact`, one change in two commits as two stacked pull
  requests, and the changelog handling. If step 2 is dropped,
  `/opsx:update` the change first (spec removed, `skip_specs: true`,
  groups 4 and the template tasks dropped).
- [ ] 1.2 Re-measure the premises of `design.md` *Context* on `main` HEAD:
  lines, constructor dependencies, methods and actions of
  `ProfileController`, the line ranges of the families, the helpers both
  families share, the four tests that pin the current shape, and that
  3.0.0 is still not tagged. Stop and `/opsx:update` on any difference that
  changes the plan.
- [ ] 1.3 Check whether `ace-tbd-editor-translation-writes` or
  `ace-tbd-editor-editable-profiles-event` has been implemented meanwhile.
  If so, the moves below include its code, and its tests join the safety
  net.

## 2. Safety net before any move

- [ ] 2.1 Widen `endpointFamilyProvider()` of
  `AcademicPersonsEditProfileEditingAuthorizationTest` from 12 to all 16
  JSON endpoints (add `updateDocument`, `deleteDocument`,
  `updateContractContact` and `deleteContractContact`), for the header,
  the anonymous and the foreign profile test. Run it on the unchanged code on v13 and v14: green, since
  it pins existing behaviour.
- [ ] 2.2 Record the functional test count of
  `packages/fgtclb/academic-persons-edit/Tests/Functional/` per core
  version (206 tests in `Plugins/` on 2026-10-12, plus 2.1). Every later
  run of this change has at least these tests and all of them green.

## 3. Step 1: services

- [ ] 3.1 Add `Service/Exception/EditorRequestException` (`final`,
  `#[Exclude]`, error identifier, status, message, field errors). In every
  JSON action of `ProfileController`, catch it before `\Throwable` and
  answer today's JSON body. Verify with the 422 `validation_failed` and
  `write_refused` tests of every family after 3.3 and 3.5.
- [ ] 3.2 Move `translate()` with its v13/v14 switch and its `@todo` into
  `EditorLabels`, taking the Extbase request as an argument. Verify with
  `AcademicPersonsEditLabelOverrideTest` on v13 and v14.
- [ ] 3.3 Move the document field definitions, display values, date
  parsing, managed field locking and item serialisation into
  `DocumentFieldDefinitions`, the normalisation and select lookup into
  `DocumentFieldNormalizer`, and the field validation shared with contacts
  into `EditorFieldValidation`. Unit tests: number bounds, `d.m.Y` and
  `Y-m-d` accepted, `32.01.2026` refused, an unknown select uid refused, a
  non boolean checkbox refused, rich text sanitised, a locked or managed
  field dropped, an unknown field refused, a required field missing on
  create. Show the date test red by removing the round trip comparison,
  restore.
- [ ] 3.4 Move listing, lookup, next sorting, order check and reordering of
  documents into `DocumentRecords`. Unit test the order check with a
  missing, a duplicate and a foreign uid, shown red by dropping the
  comparison of the sorted lists, restored. (The separate duplicate
  check cannot fail on its own: a duplicate already breaks the sorted
  comparison. Keep it, it costs nothing, but do not use it as the red
  proof.)
- [ ] 3.5 Move the contact field handling into `ContractContactFields` and
  the contact record lookup and writes over the three record types into
  `ContractContactRecords`. Keep that a required contact field is checked
  against the submitted fields, not the normalised ones as for documents,
  and pin it with a unit test, shown red by switching it, restored.
- [ ] 3.6 `ProfileController` keeps every action and address. Adapt
  `ProfileControllerTest` to the new constructor. The functional suite of
  the extension runs unchanged and green on v13 and v14.
- [ ] 3.7 Measure lines and constructor dependencies of
  `ProfileController` after the step. Commit step 1 as
  `[TASK] ACE-507: Extract editor field handling`, body with the measured
  numbers, footer `Related: ACE-507` (`Resolves:` if step 2 was dropped).
  Gates of group 7 for this commit on both core versions.

## 4. Step 2: three controllers

- [ ] 4.1 Move the guard, the JSON error answer, the payload readers both
  families use and the failure logging into `EditorEndpoint`, and the
  write offer, persisting and announcement into `EditorWrites`, keeping
  the exception codes 1777046203 and 1776760205. Unit test the guard
  (header missing, header with another value, header present), shown red
  by inverting the comparison, restored.
- [ ] 4.2 Write the functional tests of the spec first, each red on the
  state after step 1: the rendered addresses name `Document`,
  `ContractContact` and `Profile` per family (red: all name `Profile`), a
  document creation addressed under `Profile` fails and stores nothing
  (red: it stores the document), and the plugin action context of the
  write offer names `Document` for `createDocument` and `Profile` for
  `update` (red: `Profile` for both).
- [ ] 4.3 Add `DocumentController` and `ContractContactController`,
  `final`, `@internal`, constructor injection, an `initializeAction()`
  that applies the guard to every action, and move the six actions of
  each family verbatim. `ProfileController` keeps its JSON list for its
  four JSON actions and `uploadImage`, and `errorAction()`.
- [ ] 4.4 Register the three controllers in `ext_localconf.php`,
  `ProfileController` first, cacheable and non-cacheable lists identical,
  and build the addresses in `Templates/Profile/Index.html` with the
  controller of their family. The tests of 4.2 turn green.
- [ ] 4.5 Adapt the tests that pin the controller map:
  `profileEditingPluginRegistersOnlyTheNewControllerActions` (three
  controllers, each action list, cacheable equal to non-cacheable, every
  action has a method), `everyActionNamesAnActionOfTheEditor` (an action of
  one of the three), `ProfileControllerTest` split per controller, and
  `everyPluginControllerIsFound` of `ExtensionPointTest` from 9 to 11.
- [ ] 4.6 Show the guard is held per controller: remove the guard call
  from `DocumentController`, watch the header test of 2.1 go red for its
  six endpoints, restore, and the same for `ContractContactController`.
- [ ] 4.7 Measure lines and constructor dependencies of the three
  controllers. Commit step 2 as
  `[TASK] ACE-507: Split the profile editor controller`, body with the
  numbers, the new addresses and the changed log component, footer
  `Resolves: ACE-507`.

## 5. The pending editor changes

- [ ] 5.1 With `/opsx:update`, correct the method locations the *Context*
  of `ace-tbd-editor-translation-writes` and
  `ace-tbd-editor-editable-profiles-event` names, and nothing else of
  them. `openspec validate --all --strict` stays green.

## 6. Documentation

- [ ] 6.1 `docs/architecture/class-design.md`: replace the stale "36" at
  both places with the measured numbers of the three controllers, state
  that a plugin with several controllers shares through stateless
  services rather than a base controller, and re-measure with the
  commands of the page the counts this change moves (files and classes,
  the `final` table, `final readonly class`, promoted `readonly`).
- [ ] 6.2 Correct the other `docs/` pages that name the moved code:
  `label-overrides.md`, `form-data-transformation.md`,
  `translation-synchronization.md`, `testing/functional-tests.md` (whose
  "fourteen actions" is stale already) and, in `profile-editing-contract.md`,
  one sentence that the addresses carry the controller of their family.
- [ ] 6.3 Manual of `academic_persons_edit`: chapter "Profile editing"
  (the plugin registers three controllers, "Customizing the view" names
  the addresses an override takes over), and the Developers chapter (the
  controllers are `final` and `@internal`, the controller name a listener
  sees).
- [ ] 6.4 Changelog as decided in 1.1. Recommended: correct
  `Changelog/3.0/Breaking-ReplacedProfileEditingPlugin.rst` and
  `Feature-HideDocumentRows.rst` where they name the actions as actions of
  `ProfileController`, no new entry. If 3.0.0 is tagged before step 2:
  `Breaking-EditorEndpointsAddressedPerFamily.rst` from
  `Build/Documentation/Templates/`, and step 2 is committed as
  `[!!!][TASK] ACE-507: Split the editor controller`.
  Check every reST over- and underline against its title length.

## 7. Definition of done

- [ ] 7.1 After `-t 13 -s composerUpdate`: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green, functional on SQLite
  (`-j auto`), PostgreSQL (`-b docker -j 8`) and MariaDB (`-j 8`).
- [ ] 7.2 After `-t 14 -s composerUpdate`: the same.
- [ ] 7.3 `testJs`, `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 7.4 The functional test count is at least the one of 2.2, and every
  new test was shown red as its task says.
- [ ] 7.5 `docs/` and the manual are updated, and `README.md` and
  `CONTRIBUTING.md` still only summarize and link.
- [ ] 7.6 Both commit messages follow the TYPO3 Core format with the
  verified `ACE-507` key and no attribution. The archive of the change is
  the last commit, on top of step 2:
  `[TASK] ACE-507: Archive the profile controller split`.
