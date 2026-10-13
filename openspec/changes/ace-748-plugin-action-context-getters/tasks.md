## 1. Premises

- [ ] 1.1 Re-check the context of design.md on v12 and v13: the two getter
  bodies in both classes, `NullSite` not extending `Site`, the exception code
  1606222812.

## 2. Site and language

- [ ] 2.1 Add unit tests to
  `academic-base/Tests/Unit/Domain/Model/Dto/PluginControllerActionContextTest.php`:
  the site and the language of the request are returned, a `NullSite` under
  `site` gives `null`, a foreign value (`new \stdClass()`) under `site` and
  under `language` gives `null`, and no attribute gives `null`. Run them on
  the unchanged class and record the `TypeError` of the three foreign value
  cases on v12 and v13 (red).
- [ ] 2.2 Add a new unit test
  `academic-persons/Tests/Unit/Domain/Model/Dto/PluginControllerActionContextTest.php`
  with the cases of missing and foreign values, and record them red the same
  way. The site and the language the request carries are covered by the
  functional test of the same name.
- [ ] 2.3 Add the `instanceof` checks to `getSite()` and `getLanguage()` in
  both classes. Show each check is what makes its tests green by removing it
  again, watching the tests fail, and restoring from a backup.

## 3. Application type

- [ ] 3.1 Add `@throws \RuntimeException` with code 1606222812 and one
  sentence to `getApplicationType()` of both interfaces. No code change.

## 4. Documentation

- [ ] 4.1 Add `Important-PluginControllerActionContextSiteAndLanguage.rst` to
  `Changelog/2.4/` of `academic_base` and of `academic_persons`.

## 5. Definition of done

- [ ] 5.1 `composerUpdate -t 12`, then `lintPhp`, `cgl -n`, `phpstan`,
  `unit` and `functional` (SQLite, `-j auto`) for TYPO3 v12. Nothing is
  written to a database, so PostgreSQL and MariaDB runs are not required.
- [ ] 5.2 `composerUpdate -t 13`, then the same for TYPO3 v13.
- [ ] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green. No `docs/`
  page describes the context, so `docs/` is unchanged.
- [ ] 5.4 Commit as `[BUGFIX] ACE-748: Guard site and language getters` in
  TYPO3 Core format, footer `Resolves: ACE-748`, `Related: ACE-442`, no
  attribution of any kind.
- [ ] 5.5 Archive the change as the last commit of the same pull request.
