## 1. Tests first

- [x] 1.1 Add the fixture extension `test_hidden_content_types` to the
      academic_base functional tests (one content type in the `academic` group,
      one in `default`, both hidden in its `Configuration/page.tsconfig`, with
      this branch's composer and ext_emconf constraints); verify it loads after
      `composerUpdate` for v12 and v13.
- [x] 1.2 Add `academic-base/Tests/Functional/Backend/FormDataProvider/KeepCurrentContentTypeSelectableTest.php`
      from `main` without the v14 backend layout case, keeping
      `fgtclb/environment-state-manager` in `$testExtensionsToLoad` and taking
      `TcaDatabaseRecord` from `GeneralUtility::makeInstance()`; run it before
      the provider exists on v12, and with its registration removed on v13, and
      record that the two positive cases fail.
- [x] 1.3 Verify that the negative cases (new record, text element, type outside
      the group, provider guards) and both DataHandler cases are green without
      the provider on v12 and v13; stop and revisit the design if a DataHandler
      case is red.

## 2. Form data provider

- [x] 2.1 Add the provider as `final class`, its registration in a new
      `academic-base/ext_localconf.php` (`depends` on `TcaSelectItems`,
      `before` on `TcaSelectTreeItems`) and the label in both
      `locallang_be.xlf` files with tab indentation; verify the test class is
      green on v12 and v13.
- [x] 2.2 Break each provider condition once on purpose and verify the test
      guarding it turns red, on v12; restore and verify the file is unchanged.

## 3. Documentation

- [x] 3.1 Add the behaviour to the hiding section of
      `docs/architecture/typoscript-and-site-sets.md` for v12 and v13, and verify
      the page keeps its `## See also`.
- [x] 3.2 Add the section to the academic_base `Documentation/Configuration/Index.rst`
      after `configuration-hidden-by-default`, and add
      `academic-base/Documentation/Changelog/2.4/Important-HiddenContentTypeStaysSelectable.rst`
      stating that already rewritten records are not repaired; verify with
      `checkRstRenderingAll`.

## 4. Definition of done

- [x] 4.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
      `functional` for TYPO3 v12 with PHP 8.1; the same after its own
      `composerUpdate` for TYPO3 v13 with PHP 8.2; the new test class also with
      `-d mysql` and `-d postgres` on both.
- [x] 4.2 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 4.3 Commit as `[BUGFIX] ACE-666: Keep hidden academic content types` in
      TYPO3 Core format, body written for this branch, footer `Resolves: ACE-666`
      and `Releases: 2`.
- [ ] 4.4 Archive the change as the last commit of the pull request.
