## 1. Preconditions

- [x] 1.1 Confirm on main that `ace-717-partners-projects-list-events`,
  `ace-766-program-psr14-events` and
  `ace-102-bite-jobs-request-result-events` are merged and their events
  are dispatched, and stop otherwise.
- [x] 1.2 Confirm that `ace-750-generic-plugin-view-event` is merged and
  dispatched in the program details action, both partnership actions and
  the B-ITE list action, or that a details event has been added to
  `ace-766-program-psr14-events` and the partnership and B-ITE actions are
  otherwise covered, and stop otherwise.
- [x] 1.3 Confirm the state of `ace-727-partner-list-pagination`,
  `ace-723-list-filter-get-urls` and `ace-91-program-finder-element`, so
  the changelog entries point only at what is shipped.

## 2. Architecture test

- [x] 2.1 Add two test methods to `ExtensionPointTest` of
  `packages-dev/monorepo-shared`, the event class test of
  `ace-749-extension-point-policy`: every class it reads below
  `packages/fgtclb/*/Classes/` that is not abstract and extends
  `ActionController` is `final`, and there are nine of them.
- [x] 2.2 Run it on main and record the failure: it names exactly
  `PartnerController`, `ProjectController`, `ProgramController`,
  `DetailsController` and `BiteJobsController`.

## 3. Controllers

- [x] 3.1 Make `PartnerController` `final` and its promoted collaborators
  `private readonly`.
- [x] 3.2 The same for `ProjectController`.
- [x] 3.3 The same for `ProgramController`.
- [x] 3.3a In all three list controllers, move the `ExtensionService` of the
  filter redirect from `injectFilterRedirectExtensionService()` and the
  `FilterTypeResolver` from `injectFilterTypeResolver()` into the
  constructor, and make `redirectFilterSubmission()` and
  `pluginControllerActionContext()` private: all of them exist only for
  subclasses (`ace-723-list-filter-get-urls`). Correct the subclass
  bullet of the three `Feature-FilterSelectionsHaveAUrl.rst` entries, and
  in `docs/architecture/class-design.md` the second legitimate case and the
  re-counted `inject*()` methods. The design's statement that no controller
  is documented as a subclassing point no longer holds for these three.
- [x] 3.4 The same for `DetailsController`, move its `ProgramFactsBuilder`
  from `injectProgramFactsBuilder()` into the constructor, and drop its
  unread `DemandFactory` constructor argument.
- [x] 3.5 The same for `BiteJobsController`. The test from 2.1 turns green.
- [x] 3.6 Show the test can fail once more: remove `final` from one of the
  five, watch it go red, restore.
- [x] 3.7 The existing functional plugin tests of all four extensions pass
  unchanged on both core versions, proving the container still builds the
  controllers.

## 4. Documentation

- [x] 4.1 Add from `Build/Documentation/Templates/Changelog-Breaking.rst`:
  `academic-partners/Documentation/Changelog/3.0/Breaking-PartnerControllerIsFinal.rst`,
  `academic-projects/Documentation/Changelog/3.0/Breaking-ProjectControllerIsFinal.rst`,
  `academic-programs/Documentation/Changelog/3.0/Breaking-ProgramControllersAreFinal.rst`
  (both controllers) and
  `academic-bite-jobs/Documentation/Changelog/3.0/Breaking-BiteJobsControllerIsFinal.rst`.
  Each names the affected plugins and the fatal error a subclass or XCLASS
  now raises, and carries a migration example from a subclass overriding
  the action to an event listener, with the mapping of subclass purposes
  for that controller from `design.md`. Add each entry to its
  `Changelog/3.0/Index.rst` if the index lists entries. Check every reST
  over- and underline against its title length.
- [x] 4.2 Update `docs/architecture/class-design.md`: re-measure the
  controller row and the totals of the `final` table with the commands on
  the page, and state that every plugin controller is `final`, is extended
  through its events, and that the architecture test enforces it. If
  `ace-749-extension-point-policy` has landed, link its integrator page
  from that sentence.

## 5. File the issue

- [x] 5.1 File the ACE issue in YouTrack (ACE-803), verify the key, rename
  the change to `ace-803-final-partner-project-controllers`, and commit in
  TYPO3 Core format as
  `[!!!][TASK] ACE-803: Make plugin controllers final`.

## 6. Definition of done

- [x] 6.1 `lintPhp` green.
- [x] 6.2 After `-t 13 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 13`.
- [x] 6.3 After `-t 14 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 14`. Nothing is written, so a PostgreSQL run
  is not required. Both core versions ran PostgreSQL and MariaDB as well.
- [x] 6.4 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 6.5 `docs/` and the four extensions' `Documentation/Changelog/3.0/`
  entries are part of the change, and `README.md` and `CONTRIBUTING.md` still
  only summarize and link.
- [x] 6.6 The commit message follows the TYPO3 Core format with the verified
  `ACE-803` key in subject and footer.
- [ ] 6.7 Archive the change as the last commit of the pull request.
