## 1. Premises and decisions

- [x] 1.1 Re-check the premises of `design.md`, Context, on the branch head:
  no event dispatched by the extension, no production caller of
  `setPropertyOverride()`, the promise in the docblock, the six factory
  comments, the test docblocks, the `Important` entry and
  `docs/architecture/form-data-transformation.md`, and still no tag
  containing `63609482d`. Record any difference and update the change before
  going on.
- [x] 1.2 Confirm the two decisions marked "Decision for the maintainer" in
  `design.md` (comments and tests in scope, the mirror on `main`) and record
  the answer in `design.md`.

## 2. Rewording

- [x] 2.1 Reword the docblock of `setPropertyOverride()` in
  `AbstractFormData.php`: internal, applied even when not submitted, no
  caller in the extension, no event to reach it. Verify with
  `grep -n "PSR-14\|extension point" Classes/Domain/Model/Dto/AbstractFormData.php`
  finding nothing.
- [x] 2.2 Reword the guard comment of the six factories the same way, if
  1.2 kept them in scope. Verify that `git diff --stat` shows comment lines
  only and `grep -rn "PSR-14" Classes/Domain/Factory` finds nothing.
- [x] 2.3 Reword the test docblocks of `Tests/Functional/Domain/Factory/`
  that describe a listener, `ProfileFactoryTest.php` included, and the
  comments of the three unit tests named in `design.md` (`ProfileFactoryTest`,
  `EmailFormDataTest`, `AbstractFormDataValidatorTest`), if 1.2 kept them in
  scope. Test bodies and assertions unchanged, verified with `git diff`
  showing only comment lines.
- [x] 2.4 Reword the store bullet and the migration section of
  `Documentation/Changelog/2.4/Important-FormDataTransformationOnlyMapsSubmittedFields.rst`,
  anchor and file name kept. Verify with `grep -n "PSR-14"` on the file
  finding nothing.
- [x] 2.5 Reword rule 2 of `docs/architecture/form-data-transformation.md`
  (no listener, the store has no caller on this branch).

No test is added: nothing changes at runtime, so there is no behaviour a
test could show red. The verification is the `grep` of each task, a final
`grep -rn -i "psr-14" packages/fgtclb/academic-persons-edit` that lists
only the core event of `SyncChangesToTranslations`, and a
`grep -rn -i "listener\|an event" packages/fgtclb/academic-persons-edit/Classes/Domain packages/fgtclb/academic-persons-edit/Tests/*/Domain`
read line by line, because a comment can promise a listener without naming
PSR-14.

## 3. Definition of done

- [x] 3.1 `Build/Scripts/runTests.sh -t 12 -s composerUpdate`, then
  `lintPhp`, `cgl -n`, `phpstan` and `unit` with `-t 12` green, and
  `functional` for `packages/fgtclb/academic-persons-edit/Tests/Functional/Domain/Factory`
  on SQLite as a check that the reworded test files still run.
- [x] 3.2 The same for `-t 13` after its own `composerUpdate`.
- [x] 3.3 The full functional suite is not required: no runtime behaviour
  changes. State that in the pull request.
- [x] 3.4 `lintMarkdown -n` and `checkRstRenderingAll` green, and no
  definition list in the rendered entry.
- [x] 3.5 No new changelog entry, the reworded 2.4 entry is the
  documentation (see `design.md`). `docs/` is covered by 2.5.
- [x] 3.6 Commit as `[TASK] ACE-445: <subject>` in TYPO3 Core format with
  `Resolves: ACE-445` and `Related: ACE-762`, and archive the change as the
  last commit of the same pull request.
