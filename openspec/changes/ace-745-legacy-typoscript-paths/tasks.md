## 1. Tests first

- [x] 1.1 Added `LegacyStaticTemplatePathTest` per extension. It builds the
      whole frontend TypoScript (settings and setup) of an in-memory root
      record once with `Full` and once with the 2.3 path, through the new
      `StaticTemplateTypoScriptTrait` of the testing helper, and asserts both
      are equal after checking that `Full` delivers the plugin or content
      element configuration. It also asserts the component setup is read
      once. Red without the change on v14, and the read-once test alone went
      red with a contacts4pages `include_static_file.txt` naming `List`. For
      contacts4pages the stored record holds the academic_persons entry next
      to the old path, as a record of 2.3 does, and the test asserts the
      persons setup is read once too; an `include_static_file.txt` naming the
      persons folder in the old path turns it red.
- [x] 1.2 The same class compares an import of the 2.3 files with an import
      of the component files; red without the change. contacts4pages also
      asserts that the old path alone does not bring the academic_persons
      TypoScript along.
- [x] 1.3 `StaticRegistrationTest` of each extension has the 2.3 value in its
      data provider, which also asserts the folder carries TypoScript. The
      study_plan test that pinned the value as gone is removed. On top,
      `LegacyStaticTemplatePathTest` compiles the backend form of a stored
      record and asserts the 2.3 value survives while an unregistered second
      value is dropped; red with only the registration removed.
- [x] 1.4 `ConfigurationCheckerLegacyPathTest` (academic_base) asserts the
      upgrade check reports neither a stored 2.3 path nor an import of the
      2.3 files, which the archived ACE-713 change left to this one; red
      without the change.

## 2. Implementation

- [x] 2.1 Added the thin `setup.typoscript` and `constants.typoscript` to the
      2.3 folders of bite_jobs, contacts4pages and persons_edit, the new
      `academic-study-plan/Configuration/TypoScript/Default/` folder; 1.1 and
      1.2 green. contacts4pages gets no `include_static_file.txt` (see
      design).
- [x] 2.2 Registered the 2.3 paths in each
      `Configuration/TCA/Overrides/sys_template.php` as "<Extension>: Path up
      to 2.3 (deprecated, use All components)", with a comment in the style
      of academic_partners; 1.3 green.

## 3. Documentation

- [x] 3.1 `docs/architecture/typoscript-and-site-sets.md`: new section on the
      2.3 paths, why the thin files and not an `include_static_file.txt`
      naming `Full`, and the constants gap of contacts4pages and study_plan.
      `docs/testing/testing-helper.md` documents the new trait; the trait
      counts there, in `AGENTS.md`, `docs/development/monorepo-layout.md` and
      `docs/workflow/backporting.md` are updated, and so are the file and
      trait counts of `docs/architecture/class-design.md`.
- [x] 3.2 `Documentation/Changelog/2.4/Deprecation-LegacyStaticTemplatePath.rst`
      in each of the four extensions - 2.4, not 3.0, because the change is
      backported - naming "All components" as the replacement and pointing
      at the one-mechanism-per-site section. The Impact section of each 2.4
      `Breaking-SiteSetsAndStaticTemplatesRestructured.rst` is corrected, and
      each `Documentation/Configuration` chapter lists the deprecated entry.

## 4. Backport

- [x] 4.1 Backport: separate change on branch `2` after a backport analysis
      (`docs/workflow/backporting.md`); the 2.4 restructure has the same dead
      paths. The testing helper trait is not on branch `2` and has to come
      with the backport, built without `FrontendTypoScriptFactory`, which
      TYPO3 v12 lacks.

## 5. File the issue

- [x] 5.1 Filed ACE-745, renamed the change to
      `ace-745-legacy-typoscript-paths`, and committed as
      `[TASK] ACE-745: Restore the TypoScript paths of 2.3` in TYPO3 Core
      format.

## 6. Definition of done

- [x] 6.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
      `functional` for TYPO3 v13; the same after its own `composerUpdate` for
      TYPO3 v14. Functional also with `-j 4`, the split CI uses.
- [x] 6.2 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 6.3 `docs/` and the four extensions' `Documentation/` changelogs updated
      in the same change.
- [x] 6.4 Archive the change as the last commit of the pull request.
