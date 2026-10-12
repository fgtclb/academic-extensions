## 1. Re-check the premises and prove the cause

- [x] 1.1 Re-check every statement of design.md, Context, on the branch HEAD:
  the core lines in `core-13/vendor` (TYPO3 13.4.x) and, for the
  `contentRenderingTemplates` condition, in the TYPO3 v12 tree the harness
  installs with `-t 12`, the legacy record in `ScenarioLegacy.yaml`, the
  packages `AbstractSeedTestCase` loads, the `/` side substitution, and the
  `0.0` entries of the four `FunctionalTestTimes-<dbms>.json`. Verify by noting
  file and line of each in the pull request. A premise that is false stops the
  work until the change is updated.
- [x] 1.2 After `-t 13 -s composerUpdate`, remove only the skip (and the
  `@phpstan-ignore` it needed) and run the class:
  `Build/Scripts/runTests.sh -t 13 -s functional packages-dev/dev-site/Tests/Functional/LegacyDeliveryTest.php`.
  Verify it is red, and record the number of differing page pairs and the
  first differing line.
- [x] 1.3 In the red state of 1.2, verify the cause rather than the symptom:
  a legacy page that holds a core content element (a `text` or `header`
  element) reports that element as having no rendering definition as well,
  not only the plugins. That separates the missing content rendering template
  from the disproved explanation, which predicted missing plugin definitions
  only. Record the page and the message.
- [x] 1.4 After `-t 12 -s composerUpdate`, run
  `Build/Scripts/runTests.sh -t 12 -s functional packages-dev/dev-site/Tests/Functional/`
  and verify from its output that `LegacyDeliveryTest` is not executed there
  (group `not-core-12`) while the other seed checks pass. TYPO3 v12 has no
  second tree to compare, so there is no v12 red or green run of this class.

## 2. Substitute the theme on the legacy side

- [x] 2.1 Implement decision 1 of design.md in `LegacyDeliveryTest`: after
  `importSeed()`, in both test methods, replace the theme entry of the legacy
  root `sys_template` row with `EXT:fluid_styled_content/Configuration/TypoScript`
  in the same position, guarded by the two assertions. Verify with the run of
  1.2 turning green on v13.
- [x] 2.2 If 2.1 is not green, analyse each remaining difference by a run
  (decision 2 and the first risk of design.md), adjust the substitution only
  for a harness artefact, and update design.md through `/opsx:update` with the
  evidence before going on. Nothing is added to `normalise()`.
- [x] 2.3 Show the restored test can fail for the reason it exists: drop one
  `Full` aggregate (for example `EXT:academic_jobs/Configuration/TypoScript/Full`)
  from the row inside the substitution, verify the class goes red naming the
  pages of that extension, restore from a backup below `.agent/tmp/`.
- [x] 2.4 Show the guard works: change the expected theme entry in the
  substitution to a string the row does not contain, verify the class fails
  with the guard's message instead of comparing, restore from the backup.
- [x] 2.5 Implement decision 4: declare the constants the class uses, and
  expect `404` for the detail pages 205, 209 and 233. Verify with both
  test methods green on v13.

## 3. Correct the stale explanations

- [x] 3.1 `LegacyDeliveryTest`: rewrite the paragraph "WHAT IS SUBSTITUTED, AND
  WHY IT IS SUBSTITUTED ON BOTH SIDES" of the class docblock so it names
  fluid_styled_content on both sides and the content rendering condition, and
  delete the skip comment. Verify by `grep -n "ACE-468\|TCA cache\|addStaticFile" packages-dev/dev-site/Tests/Functional/LegacyDeliveryTest.php`
  returning nothing that describes the old cause.
- [x] 3.2 `Build/Scripts/generateLegacyScenario.php`, `templateRecord()`:
  correct the sentence claiming that an entry of an extension that is not
  loaded costs no other entry. It costs the content rendering the theme brings
  with it. Comment only: verify that
  `php Build/Scripts/generateLegacyScenario.php --check` reports no change and
  `GeneratedLegacyScenarioTest` passes.
- [x] 3.3 Check every other text that explains the legacy side in the harness,
  `packages-dev/dev-site/Configuration/TypoScript/setup.typoscript` and
  `Configuration/Sets/PageObject/config.yaml` among them, and correct what the
  substitution makes wrong. Verify with
  `git grep -n "LegacyDeliveryTest\|resolves to nothing"`.

## 4. Documentation

- [x] 4.1 `docs/testing/seed-verification.md`, section `LegacyDeliveryTest`:
  remove the skip note, state the substitution on both sides (page object plus
  fluid_styled_content in the theme's place), and replace the claim that the
  `/legacy/` tree "cannot be" themed, which is the wording of `main`: on this
  branch both trees of `core-13` are themed by EXT:bootstrap_package 15, the
  harness has no theme. Verify with `lintMarkdown -n`.
- [x] 4.2 `docs/development/environment.md`, "No chunk can be faster than the
  heaviest test class": name the heaviest class with the duration measured in
  this change, and say for the measured table whether it predates the class
  running. Verify with `lintMarkdown -n`.
- [x] 4.3 No `Documentation/Changelog/2.4/` entry: `packages-dev/dev-site` has
  no manual and is never released, and nothing an editor, integrator or
  visitor sees changes. State that in the pull request.

## 5. Recorded durations

- [x] 5.1 After the first green CI run of the pull request, refresh the four
  `Build/phpunit/FunctionalTestTimes-<dbms>.json` from its v13 JUnit
  artifacts of one PHP version with `-s recordFunctionalTestTimes`, as
  `docs/development/environment.md` describes (decision 3). Verify that the
  class carries a duration above zero in all four files and that the next CI
  run's chunks are within the spread the page names.

## 6. Definition of done

- [x] 6.1 `lintPhp` green.
- [x] 6.2 After `-t 12 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional -j auto` on SQLite green with `-t 12`.
- [x] 6.3 After `-t 13 -s composerUpdate`: `cgl -n`, `phpstan`, `unit`,
  `functional -j auto` on SQLite, `-d postgres -b docker -j 8` and
  `-d mariadb -j 8` green with `-t 13`, and the class alone with `-d mysql`.
- [x] 6.4 `lintMarkdown -n` green.
- [x] 6.5 The red run of 1.2, the cause check of 1.3 and the failure proofs of
  2.3 and 2.4 are written down in the pull request description.
- [x] 6.6 Commit message in TYPO3 Core format, `[TASK] ACE-468: ...`, footer
  `Resolves: ACE-468`, no attribution of any kind.
- [x] 6.7 Archive the change as the last commit of the same pull request.
