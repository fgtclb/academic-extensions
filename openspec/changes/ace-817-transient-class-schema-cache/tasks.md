## 1. Verify the premises

- [x] 1.1 On `main` at `80ffa116c`, list the functional test cases that extend
  a test case outside the repository (`grep -rl "extends FunctionalTestCase"
  packages packages-dev`): the twelve abstract test cases of the extensions,
  `AbstractSeedTestCase` and `SnapshotManifestTest`, all of them on
  `SBUERK\TYPO3\Testing\TestCase\FunctionalTestCase`. List the classes that
  configure the `extbase` cache (`git grep -n TransientMemoryBackend --
  'packages*/**/Tests/**'`): seven. Confirm in the installed testing framework
  that `$configurationToUseInTestInstance` is merged last and read in
  `FunctionalTestCase::setUp()`, and in both core versions that
  `VariableFrontend` skips serialization for a `TransientBackendInterface`
  backend, which `TransientMemoryBackend` and `NullBackend` implement.

## 2. The base class

- [x] 2.1 Add `packages-dev/testing-helper/Classes/TestCase/FunctionalTestCase.php`
  as `design.md` describes, and let the fourteen classes of 1.1 extend it by
  changing their import. Verify with `-s unit` and with the functional tests
  of 2.2.
- [x] 2.2 Add `FunctionalTestCaseTest` and `FunctionalTestCaseConfigurationTest`
  in `packages-dev/testing-helper/Tests/Functional/TestCase/`. Show them red:
  without the merge in `setUp()` the first fails with `SimpleFileBackend`, with
  the arguments of `array_replace_recursive()` swapped the second fails with
  `TransientMemoryBackend`. Restore after each.

## 3. The check

- [x] 3.1 Add `packages-dev/monorepo-shared/Tests/Unit/FunctionalTestBaseClassTest.php`
  as `design.md` describes and verify it is green with
  `-s unit packages-dev/monorepo-shared/Tests/Unit/FunctionalTestBaseClassTest.php`.
- [x] 3.2 Show it red: `AbstractAcademicBaseTestCase` and `SnapshotManifestTest`
  back on the test case of `sbuerk/typo3-site-based-test-trait` fail the data
  sets `packages/fgtclb/academic-base` and `packages-dev/dev-site`, naming every
  class of academic_base and `SnapshotManifestTest`. Restore.

## 4. Remove the workarounds

- [x] 4.1 Remove the `frontendPluginTestConfiguration()` overrides, their
  docblocks, the trait method aliases and the imports from
  `ModifyPluginViewEventTest`, `AcademicPartnersListEventsTest`,
  `AcademicPersonsProfileTemplatePartialsTest`,
  `AcademicPersonsEditProfileImageUploadTest`,
  `AcademicPersonsEditBeforeWriteEventImageUploadTest`,
  `AcademicProgramsEventsTest` and `AcademicProjectPageLayoutTest`. Verify that
  `git grep -n "TransientMemoryBackend\|'extbase' =>" -- 'packages*/**/Tests/**'`
  finds nothing outside `packages-dev/testing-helper`.

## 5. Documentation

- [x] 5.1 `docs/testing/functional-tests.md`: the base class in *Declaring what
  a test loads*, a section on the base class, the defect, how a class
  configures another backend and the two checks, and the functional classes of
  `packages-dev/testing-helper` next to those of `packages-dev/dev-site`.
- [x] 5.2 `docs/testing/testing-helper.md`: a section on the base class, why it
  is not a trait, its tests, and the intro and the paragraphs on dependencies,
  tests and namespaces.
- [x] 5.3 `docs/testing/unit-tests.md`: a section on the check, the check in
  *Discovery*, the class count (147 become 148, nine in
  `packages-dev/monorepo-shared` become ten) and the count of unit tests below
  `packages-dev/` that extend PHPUnit's `TestCase` (eighteen, measured with
  `grep -l "extends TestCase" -r packages-dev/*/Tests/Unit`).
- [x] 5.4 `AGENTS.md`: the Layout bullets of `packages-dev/monorepo-shared`
  (nine unit tests become ten) and `packages-dev/testing-helper`, the test
  discovery paragraph and the row of the testing helper page.
- [x] 5.5 `docs/architecture/class-design.md` (370 files, 325 classes, six
  abstract classes, measured with the commands on the page),
  `docs/development/monorepo-layout.md`, `docs/development/quality-gates.md`,
  `docs/workflow/backporting.md` (branch `2` has the trait default instead),
  `docs/Index.md` and `docs/testing/Index.md`.
- [x] 5.6 No changelog entry: no extension changes what it ships.

## 6. Definition of done

- [x] 6.1 `lintPhp` green.
- [x] 6.2 After `-t 13 -s composerUpdate`: `cgl -n`, `phpstan` and `unit` green
  with `-t 13`.
- [x] 6.3 After `-t 14 -s composerUpdate`: `cgl -n`, `phpstan` and `unit` green
  with `-t 14`.
- [x] 6.4 `functional` green for v13 and v14, each after its own
  `composerUpdate`: SQLite with `-j auto`, PostgreSQL with `-b docker -j 8` and
  MariaDB with `-j 8`. The defect cannot be reproduced on demand, so the proof
  of the change is the red runs of 2.2 and 3.2.
- [x] 6.5 `lintMarkdown -n` green, `docs/` updated, `README.md` and
  `CONTRIBUTING.md` still only summarize. No `Documentation/` changed.
- [x] 6.6 `openspec validate --all --strict` green.
- [x] 6.7 Commit `[TASK] ACE-817: Keep test class schemas in memory` in TYPO3
  Core format, without attribution of any tool, and archive the change as the
  last commit of the branch.
- [x] 6.8 No backport: branch `2` sets the same backend through
  `FrontendPluginRenderingTrait` since ACE-742.
