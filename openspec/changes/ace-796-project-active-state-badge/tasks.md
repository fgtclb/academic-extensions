## 1. Tests first

- [x] 1.1 Add unit cases for `ActiveState::fromEndDate()` to
  `Tests/Unit/Domain/Model/Dto/ActiveStateTest.php`: no end date, tomorrow,
  yesterday. Record that they fail today, where the method does not exist.
  Failed before the change with "Call to undefined method
  ActiveState::fromEndDate()" in all five cases (the three above, the very
  moment of the end date, and a mutable `\DateTime`).
- [x] 1.2 Add `Tests/Unit/Domain/Model/ProjectTest.php` for
  `getActiveState()` with the same three cases, and record that it fails
  today. Failed before the change with "Call to undefined method
  Project::getActiveState()" in all three cases.
- [x] 1.3 Add a functional case that runs both filters on one fixture set,
  including one page with `NULL` and one with `0` as end date, and asserts
  that every listed project is in the matching state. It went into
  `Tests/Functional/Domain/Repository/ProjectRepositoryTest.php`, next to the
  tests of the filter query and on their fixture, rather than into a
  rendering test: the filter is the query, and the rendered state is covered
  by task 1.4. For the `NULL` page, assert that it is in the state active
  and, as today, missing from the "Active" filter, with a comment naming
  ACE-433, which turns the filter assertions around. Do not change the
  filter.
- [x] 1.4 Add functional cases to
  `Tests/Functional/Plugins/AcademicProjectsProjectListPluginTest.php` for
  the badge off (no label) and on (label "Completed" for an ended project).
  Record that the "on" case fails today. Failed before the change: the
  cards rendered without a badge, and the German case of
  `AcademicProjectsProjectListLocalizationTest` and the option case of
  `Tests/Functional/Tca/PluginFlexFormTest.php` failed alongside. The "off"
  cases pass before and after, and fail once the template renders the badge
  without asking for the option.

## 2. Implementation

- [x] 2.1 Add `ActiveState::fromEndDate()` and `Project::getActiveState()`;
  verify tasks 1.1 and 1.2 pass.
- [x] 2.2 Add `settings.showActiveStateBadge` to
  `Configuration/FlexForms/ProjectSettings.xml` with labels in
  `locallang_be.xlf` and `de.locallang_be.xlf`, and its TypoScript default
  `0`; verify with the FlexForm test of the extension.
- [x] 2.3 Render the badge in `Resources/Private/Partials/Project/Item.html`;
  verify task 1.4 passes on v13 and v14.

## 3. Documentation

- [x] 3.1 Document the option and the template value in
  `packages/fgtclb/academic-projects/Documentation/Configuration/Index.rst`,
  section "The state of a project", next to the list filter section.
  `Configuration/General/` is an empty stub page.
- [x] 3.2 Add `Documentation/Changelog/3.0/Feature-ProjectActiveStateBadge.rst`
  and verify the `3.0` index lists it.
- [x] 3.3 Grep `docs/` for statements about the active state rule and update
  them; no new page is expected.

## 4. File the issue

- [x] 4.1 After implementation, file the ACE issue in YouTrack, verify the key
  and rename the change to `ace-<NNN>-project-active-state-badge`.
- [x] 4.2 Commit in TYPO3 Core format `[FEATURE] ACE-<NNN>: <subject>`,
  subject at most 52 characters, body wrapped at 72.

## 5. Definition of done

- [x] 5.1 `Build/Scripts/runTests.sh -t 13 -s composerUpdate`, then
  `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` with `-t 13` green.
- [x] 5.2 The same for `-t 14` after its own `composerUpdate`.
- [x] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 5.4 `docs/` and the `Documentation/` changelog entry are part of the
  change; `README.md` and `CONTRIBUTING.md` still only summarize and link.
- [ ] 5.5 Archive the change as the last commit of the pull request and
  verify the delta spec landed in `openspec/specs/`.
