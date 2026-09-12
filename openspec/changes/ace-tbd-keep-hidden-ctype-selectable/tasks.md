## 1. Form data provider

- [ ] 1.1 Add a fixture extension for the academic_base functional tests that
      registers one content type in the `academic` group, one in another group,
      and hides both in its `Configuration/page.tsconfig`; verify the fixture
      is autoloaded after `composerUpdate` for v13 and v14.
- [ ] 1.2 Add `academic-base/Tests/Functional/Backend/FormDataProvider/KeepCurrentContentTypeSelectableTest.php`,
      compiling the `TcaDatabaseRecord` form data group (precedent:
      `packages-dev/testing-helper/Classes/FunctionalTestCase/PluginFlexFormDataStructureTrait.php`)
      for an existing record of the hidden academic type; assert the value is
      among the processed items with the suffixed label and is selected; run
      it before the provider exists and record that it fails.
- [ ] 1.3 Add the negative cases to the same test: a new record, an existing
      text element, a hidden type outside the `academic` group, and a type
      hidden through `keepItems`; verify the first three are unchanged by the
      provider and the fourth is preserved.
- [ ] 1.4 Add a DataHandler test that saves the unchanged hidden academic type
      on such a page and asserts the stored type; it pins DataHandler, so
      verify it is green without the provider on v13 and v14, and stop to
      revisit the design if it is not.
- [ ] 1.5 Implement the provider, its registration in a new
      `academic-base/ext_localconf.php` and the label in
      `Resources/Private/Language/locallang_be.xlf`; verify 1.2 and 1.3 are
      green on v13 and v14.

## 2. Documentation

- [ ] 2.1 Add the behaviour to the hiding section of
      `docs/architecture/typoscript-and-site-sets.md`, and verify the page
      keeps its `## See also`.
- [ ] 2.2 Document it in the academic_base `Documentation/` next to the
      content element group, and add
      `academic-base/Documentation/Changelog/3.0/Important-HiddenContentTypeStaysSelectable.rst`
      from `Build/Documentation/Templates/Changelog-Important.rst`, stating
      that already rewritten records are not repaired.

- [ ] 2.3 Prepare a forge report for the general core behaviour (a stored
      value hidden by `removeItems` gets no "invalid value" option in
      `TcaSelectItems`, identical in 13.4.35 and 14.3.6) for the maintainer
      to file, and reference the forge issue in the Important entry once it
      exists.

## 3. Backport

- [ ] 3.1 Backport: separate change on branch `2` after a backport analysis
      (`docs/workflow/backporting.md`); the v12 form engine is not verified
      here.

## 4. File the issue

- [ ] 4.1 File the ACE issue in YouTrack, rename the change to
      `ace-<NNN>-keep-hidden-ctype-selectable`, and commit as
      `[BUGFIX] ACE-<NNN>: Keep hidden academic content types` in TYPO3 Core
      format.

## 5. Definition of done

- [ ] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
      `functional` for TYPO3 v13; the same after its own `composerUpdate` for
      TYPO3 v14; the DataHandler test also with `-d postgres` on both.
- [ ] 5.2 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 5.3 `docs/` and the academic_base `Documentation/` changelog updated in
      the same change.
- [ ] 5.4 Archive the change as the last commit of the pull request.
