## 1. Backport analysis

- [x] 1.1 Diffed every file the `main` change touches against branch `2`. The
      TypoScript folders, the `Full` aggregates and the registrations are the
      same. The component setups differ: on `2` the study_plan setup adds its
      assets to the page object, the contacts4pages and study_plan setups read
      fewer constants, and the persons_edit setup reads its layout path
      instead of the academic_persons `detailPid`. The testing helper has six
      traits here, there is no upgrade check, and TYPO3 v12 has no
      `FrontendTypoScriptFactory`.

## 2. Tests first

- [x] 2.1 `LegacyStaticTemplatePathTest` per extension, as on `main`, on the
      branch-2 `StaticTemplateTypoScriptTrait` that builds the trees with
      `SysTemplateTreeBuilder`; every one of them red without the change on
      v12.
- [x] 2.2 `StaticRegistrationTest` of each extension has the 2.3 value in its
      data provider; the study_plan test that pinned it as gone is removed.

## 3. Implementation

- [x] 3.1 The thin `setup.typoscript` and `constants.typoscript` in the 2.3
      folders and the new study_plan `Default/` folder, as on `main`;
      contacts4pages gets no `include_static_file.txt` there either.
- [x] 3.2 The deprecated registration in each
      `Configuration/TCA/Overrides/sys_template.php`, as on `main`.

## 4. Documentation

- [x] 4.1 `docs/architecture/typoscript-and-site-sets.md` and
      `docs/testing/testing-helper.md` as on `main`, adapted to v12 and to
      the trait of this branch; the trait count there, in `AGENTS.md`,
      `docs/development/monorepo-layout.md` and `docs/workflow/backporting.md`
      is seven. The counts of `docs/architecture/class-design.md` were stale
      before this change across the whole page and are left alone.
- [x] 4.2 `Documentation/Changelog/2.4/Deprecation-LegacyStaticTemplatePath.rst`
      in the four extensions, the corrected Impact of each 2.4 Breaking
      entry, and the deprecated entry in each Configuration chapter.

## 5. Definition of done

- [x] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
      `functional` for TYPO3 v12; the same after its own `composerUpdate` for
      TYPO3 v13. Functional also with `-j 4`, the split CI uses.
- [x] 5.2 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 5.3 Commit as `[TASK] ACE-745: Restore the TypoScript paths of 2.3`
      and archive the change as the last commit of the pull request.
