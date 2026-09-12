## 1. Preconditions

- [ ] 1.1 Confirm on main that `ace-tbd-partners-projects-list-events`,
  `ace-tbd-program-psr14-events` and
  `ace-tbd-bite-jobs-request-result-events` are merged and their events
  are dispatched; stop otherwise.
- [ ] 1.2 Confirm that `ace-tbd-generic-plugin-view-event` is merged and
  dispatched in the program details action, both partnership actions and
  the B-ITE list action, or that a details event has been added to
  `ace-tbd-program-psr14-events` and the partnership and B-ITE actions are
  otherwise covered; stop otherwise.
- [ ] 1.3 Confirm the state of `ace-tbd-partner-list-pagination`,
  `ace-tbd-list-filter-get-urls` and `ace-tbd-program-finder-element`, so
  the changelog entries point only at what is shipped.

## 2. Architecture test

- [ ] 2.1 Add a unit test to a `packages-dev/` package covered by the
  phpunit glob, next to the event class test of
  `ace-tbd-extension-point-policy` if that has landed: it reflects every
  non-abstract class extending `ActionController` below
  `packages/fgtclb/*/Classes/Controller/`, asserts that it is `final`, and
  asserts that nine controllers were found.
- [ ] 2.2 Run it on main and record the failure: it names exactly
  `PartnerController`, `ProjectController`, `ProgramController`,
  `DetailsController` and `BiteJobsController`.

## 3. Controllers

- [ ] 3.1 Make `PartnerController` `final` and its promoted collaborators
  `private readonly`.
- [ ] 3.2 The same for `ProjectController`.
- [ ] 3.3 The same for `ProgramController`.
- [ ] 3.4 The same for `DetailsController`, and drop its unread
  `DemandFactory` constructor argument.
- [ ] 3.5 The same for `BiteJobsController`. The test from 2.1 turns green.
- [ ] 3.6 Show the test can fail once more: remove `final` from one of the
  five, watch it go red, restore.
- [ ] 3.7 The existing functional plugin tests of all four extensions pass
  unchanged on both core versions, proving the container still builds the
  controllers.

## 4. Documentation

- [ ] 4.1 Add from `Build/Documentation/Templates/Changelog-Breaking.rst`:
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
- [ ] 4.2 Update `docs/architecture/class-design.md`: re-measure the
  controller row and the totals of the `final` table with the commands on
  the page, and state that every plugin controller is `final`, is extended
  through its events, and that the architecture test enforces it. If
  `ace-tbd-extension-point-policy` has landed, link its integrator page
  from that sentence.

## 5. File the issue

- [ ] 5.1 After implementation, file the ACE issue in YouTrack, verify the
  key, rename the change to `ace-<NNN>-final-partner-project-controllers`,
  and commit in TYPO3 Core format as
  `[!!!][TASK] ACE-<NNN>: Make the plugin controllers final`.

## 6. Definition of done

- [ ] 6.1 `lintPhp` green.
- [ ] 6.2 After `-t 13 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 13`.
- [ ] 6.3 After `-t 14 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 14`. Nothing is written, so a PostgreSQL run
  is not required.
- [ ] 6.4 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.5 `docs/` and the four extensions' `Documentation/Changelog/3.0/`
  entries are part of the change; `README.md` and `CONTRIBUTING.md` still
  only summarize and link.
- [ ] 6.6 The commit message follows the TYPO3 Core format with the verified
  `ACE-<NNN>` key in subject and footer.
- [ ] 6.7 Archive the change as the last commit of the pull request.
