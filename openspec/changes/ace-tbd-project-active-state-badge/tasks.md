## 1. Tests first

- [ ] 1.1 Add unit cases for `ActiveState::fromEndDate()` to
  `Tests/Unit/Domain/Model/Dto/ActiveStateTest.php`: no end date, tomorrow,
  yesterday. Record that they fail today, where the method does not exist.
- [ ] 1.2 Add `Tests/Unit/Domain/Model/ProjectTest.php` for
  `getActiveState()` with the same three cases, and record that it fails
  today.
- [ ] 1.3 Add a functional case to the project list plugin tests that renders
  both filters on one fixture set, including one page with `NULL` and one
  with `0` as end date, and asserts that every listed project renders the
  matching state. For the `NULL` page, assert that it is in the state active
  and, as today, missing from the "Active" filter, with a comment that the
  follow-up bugfix change flips that assertion. Do not change the filter.
- [ ] 1.4 Add functional cases to
  `Tests/Functional/Plugins/AcademicProjectsProjectListPluginTest.php` for
  the badge off (no label) and on (label "Completed" for an ended project).
  Record that the "on" case fails today.

## 2. Implementation

- [ ] 2.1 Add `ActiveState::fromEndDate()` and `Project::getActiveState()`;
  verify tasks 1.1 and 1.2 pass.
- [ ] 2.2 Add `settings.showActiveStateBadge` to
  `Configuration/FlexForms/ProjectSettings.xml` with labels in
  `locallang_be.xlf` and `de.locallang_be.xlf`, and its TypoScript default
  `0`; verify with the FlexForm test of the extension.
- [ ] 2.3 Render the badge in `Resources/Private/Partials/Project/Item.html`;
  verify task 1.4 passes on v13 and v14.

## 3. Documentation

- [ ] 3.1 Document the option and the template value in
  `packages/fgtclb/academic-projects/Documentation/Configuration/General/`.
- [ ] 3.2 Add `Documentation/Changelog/3.0/Feature-ProjectActiveStateBadge.rst`
  and verify the `3.0` index lists it.
- [ ] 3.3 Grep `docs/` for statements about the active state rule and update
  them; no new page is expected.

## 4. File the issue

- [ ] 4.1 After implementation, file the ACE issue in YouTrack, verify the key
  and rename the change to `ace-<NNN>-project-active-state-badge`.
- [ ] 4.2 Commit in TYPO3 Core format `[FEATURE] ACE-<NNN>: <subject>`,
  subject at most 52 characters, body wrapped at 72.

## 5. Definition of done

- [ ] 5.1 `Build/Scripts/runTests.sh -t 13 -s composerUpdate`, then
  `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` with `-t 13` green.
- [ ] 5.2 The same for `-t 14` after its own `composerUpdate`.
- [ ] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 5.4 `docs/` and the `Documentation/` changelog entry are part of the
  change; `README.md` and `CONTRIBUTING.md` still only summarize and link.
- [ ] 5.5 Archive the change as the last commit of the pull request and
  verify the delta spec landed in `openspec/specs/`.
