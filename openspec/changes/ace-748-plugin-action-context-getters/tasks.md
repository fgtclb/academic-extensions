## 1. Decision and premises

- [ ] 1.1 Confirm the application type option with the maintainer (design.md,
  "The application type, decision for the maintainer", A recommended). For
  option B, rewrite the second requirement of the `academic-base` delta with
  `/opsx:update` before any code, and replace task 3.1 by the Breaking entry.
  Verified when the decision is recorded in design.md under "Decisions".
- [ ] 1.2 Re-check the premises of design.md, "Context", on HEAD for both
  cores (`.Build/vendor` after `composerUpdate -t 14`, `core-13/vendor`): the
  three getter bodies in both classes, `NullSite` not extending `Site`, the
  exception code 1606222812, no production caller of the application type
  getter. Verified when every premise is confirmed or the change is updated.

## 2. Site and language

- [ ] 2.1 Add unit tests to
  `academic-base/Tests/Unit/Domain/Model/Dto/PluginControllerActionContextTest.php`:
  the site and the language of the request are returned, a `NullSite` under
  `site` gives `null`, a foreign value (`new \stdClass()`) under `site` and
  under `language` gives `null`, and no attribute gives `null`. Run them on
  the unchanged class and record the `TypeError` of the three foreign value
  cases (red).
- [ ] 2.2 Add the same tests for the persons class to
  `academic-persons/Tests/Unit/Domain/Model/Dto/PluginControllerActionContextTest.php`
  and record them red the same way.
- [ ] 2.3 Add the `instanceof` checks to `getSite()` and `getLanguage()` in
  both classes. The tests of 2.1 and 2.2 turn green. Show each check is what
  makes them green by removing it again, watching the test fail, and
  restoring from a backup.

## 3. Application type (option A)

- [ ] 3.1 Add `@throws \RuntimeException` with code 1606222812 and one
  sentence to `getApplicationType()` of the `academic_base` interface, and
  adjust the docblock of the unit test that pins the exception so it points at
  the interface. No code change, the existing test stays green on v13 and v14.

## 4. Documentation

- [ ] 4.1 Add
  `academic-base/Documentation/Changelog/3.0/Important-PluginControllerActionContextSiteAndLanguage.rst`
  from `Build/Documentation/Templates/`: both classes answer `null` for a
  foreign site or language value, what a listener saw before. Link it from
  the existing content object entry, whose sentence on the application type
  stays true under option A.
- [ ] 4.2 Extend the row of the context in
  `academic-base/Documentation/Developers/ExtensionPoints/Index.rst` ("What
  an event hands over"): every getter answers with none when the request
  lacks the value, except the application type, which raises for a request
  no TYPO3 application handled. `checkRstRenderingAll` renders it without a
  definition list.
- [ ] 4.3 Add one sentence to "Every event carries the plugin context" in
  `docs/architecture/list-plugin-events.md`. `lintMarkdown -n` is green.

## 5. The issue and the commit

- [ ] 5.1 YouTrack ACE-748 (exists, do not file a new one) to `In Progress`
  at the start, `Testing PR` with the pull request, `Done` after the merge.
- [ ] 5.2 Commit as `[BUGFIX] ACE-748: Guard site and language getters`
  (option A, 49 characters) in TYPO3 Core format, body at 72, footer
  `Resolves: ACE-748`, no attribution of any kind. Option B commits as
  `[!!!][BUGFIX] ACE-748: Context getters answer null` (50 characters).

## 6. Definition of done

- [ ] 6.1 `composerUpdate -t 13`, then `lintPhp`, `cgl -n`, `phpstan`, `unit`
  and `functional` (SQLite, `-j auto`) for TYPO3 v13. Nothing is written to a
  database, so PostgreSQL and MariaDB runs are not required, say so in the
  pull request.
- [ ] 6.2 `composerUpdate -t 14`, then the same for TYPO3 v14.
- [ ] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.4 `docs/` and the `Documentation/Changelog/3.0/` entry are part of
  the change. `README.md` and `CONTRIBUTING.md` still only summarize and link.
- [ ] 6.5 Archive the change as the last commit of the same pull request.
