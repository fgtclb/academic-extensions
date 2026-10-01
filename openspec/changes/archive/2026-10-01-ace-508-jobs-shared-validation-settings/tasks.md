## 1. Safety net

- [x] 1.1 Run the jobs functional tests (`AcademicJobsNewJobFormPluginTest`,
  `AcademicJobsNewJobFormUploadTest`, `AcademicJobsNewJobFormFlagsTest`,
  `JobValidatorTest`) for v13 and v14 before touching code and record them
  green. Every one that is not about a flag this change alters must pass
  unchanged after group 2.
- [x] 1.2 Write the new tests of group 3 first, against the current code, and
  record which ones are red there. Those are the proof that the change does
  something.

## 2. Implementation

- [x] 2.1 Add `AcademicJobsSettings` and `AcademicJobsSettingsFactory` below
  `Classes/Settings/`, register the settings object in `Services.yaml` through
  the factory, and remove the registry entry. Verify with the settings tests
  of 3.1.
- [x] 2.2 Rewrite `JobValidator` on the `job` validation set and the
  exceptions of `academic_base`, keeping the codes 1753702412 and 1753702335.
  Verify with `JobValidatorTest`, adjusted only where a flag changes.
- [x] 2.3 Assign the `Validation` map in `newAction()` and change
  `Job/Forms/FieldWrapper.html` and `Job/Forms/Textfield.html` to
  `p:validationEnsure`. Verify with the form tests of 3.2 and an unchanged
  markup diff of the shipped form apart from the phone input type.
- [x] 2.4 Add `EventListener/ApplySettingsToTca` with the identifier
  `academic-jobs/apply-settings-to-tca`. Verify with the TCA tests of 3.3.
- [x] 2.5 Change `contactPhone` to `tel` in the shipped settings file and
  rewrite its comment block to the shared vocabulary.
- [x] 2.6 Delete `AcademicJobsSettingsLoader`, `AcademicJobsSettingsRegistry`,
  both `ViewHelpers/Validation` classes, both jobs exceptions,
  `ServiceProvider`, and the tests of the loader and the registry. Verify
  that no reference is left with a search over `packages/`, `docs/` and
  `Build/`, and with `phpstan` on v13 and v14.

## 3. Tests

- [x] 3.1 Settings: the shipped `job` set as a whole, the validators each flag
  chooses, an empty set for an unknown identifier, the service the container
  publishes, and the round trip through the core cache. Red proof: the class
  did not exist before, and pointing the factory at another cache identifier
  fails the two cache tests.
- [x] 3.2 Form: with the shipped settings the six required fields are marked,
  the phone renders `tel`, an invalid e-mail address is refused on its field,
  and `+49 30 123` is stored (a guard: the old code stored it as well, the
  browser refused it). With the fixture extension `test_job_validation_override`
  only the fields it names change, an unknown flag renders a text input,
  `number` a number input, a locked, disabled, emptied and removed field is
  optional, and a field required in capitals is refused when empty. Red
  proof: against the old code the input types, the per-field merge, the
  capitals and the property-less `salary` fail.
- [x] 3.3 TCA: with the shipped settings `company_name` and `description` are
  required in the compiled TCA, the cached TCA and the cached schema. With the
  fixture extension `readonly` and `disabled` lock a column, `[]` makes the
  start date optional against the TCA, `number` makes a number column, `~`
  leaves the description alone, a TCA override of `required` is overruled, the
  content blocks stand-in runs first, a listener ordered after
  `academic-jobs/apply-settings-to-tca` keeps its change, `salary` adds no
  column and the `email` flag brings its soft reference. Red proof: without
  the listener, with its identifier renamed, without its ordering, without the
  column filter and without the soft reference step, each of these goes red.

## 4. Documentation

- [x] 4.1 Rewrite `Documentation/Configuration/Validations/Index.rst` of
  `academic_jobs`: the flag table for form, check and backend, the per-field
  merge with `[]` and `null`, and the listener identifier for a project that
  must change a key after the settings.
- [x] 4.2 Add to `academic_jobs` `Documentation/Changelog/3.0/`:
  `Breaking-JobValidationSettingsClassesRemoved.rst` (the classes, the two
  ViewHelpers and the markup that replaces them),
  `Breaking-JobValidationSettingsMergedPerField.rst`,
  `Breaking-JobValidationSettingsReachTheBackend.rst` and
  `Important-JobValidationFlagsFollowTheSharedVocabulary.rst` (case, unknown
  flags, the phone input). Verify that the `3.0` index lists all four.
- [x] 4.3 List the identifier `academic-jobs/apply-settings-to-tca` on the
  extension points page of `academic_base`, and verify the extension points
  test of `packages-dev/monorepo-shared`.
- [x] 4.4 `docs/architecture/validation-settings.md`: replace *The second
  implementation* with a section on jobs as a consumer of the shared classes,
  and state that `ValidationEnsureViewHelper` has a caller again.
  `docs/architecture/dependency-injection.md`: drop the jobs loader from the
  non-compliance list and update the listener counts. `AGENTS.md`: the count
  of TYPO3 `#[AsEventListener]` listeners.

## 5. Definition of done

- [x] 5.1 `Build/Scripts/runTests.sh -t 13 -s composerUpdate`, then `lintPhp`,
  `cgl -n`, `phpstan`, `unit` and `functional -j auto` with `-t 13` green.
- [x] 5.2 The same for `-t 14` after its own `composerUpdate`.
- [x] 5.3 `functional -d postgres -j 8` and `functional -d mariadb -j 8` green
  for v13 and v14, because the form writes records.
- [x] 5.4 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 5.5 `README.md` and `CONTRIBUTING.md` still only summarize and link.
- [x] 5.6 Commit as `[!!!][TASK] ACE-508: <subject>`, subject at most 52
  characters including the tags, body wrapped at 72, no attribution.
- [x] 5.7 Archive the change as the last commit of the pull request.
