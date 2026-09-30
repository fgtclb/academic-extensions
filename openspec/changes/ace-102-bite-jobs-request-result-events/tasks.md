## 1. Tests first

- [x] 1.1 In `Tests/Functional/Services/BiteJobsServiceTest.php`, call the
  service twice on one instance: the first with a stub answering postings,
  the second with a handler throwing `ConnectException`. Assert the second
  call returns no postings. Record that it fails against the unchanged
  service, which returns the first response again.
- [x] 1.2 Add a fixture extension below `Tests/Functional/Fixtures/Extensions/`
  (psr-4 autoload, then `composerUpdate`) with a request listener adding
  `filter.custom.zuordnung` and a result listener removing one posting and
  adding `relationName` to the rest. Assert the captured request body and the
  returned postings. Record that both fail today, where no event is
  dispatched.
- [x] 1.3 Assert that the limit applies after the result listener, and that
  the default payload without listeners is unchanged. Break the order on
  purpose once, watch the limit case go red, restore.

## 2. Implementation

- [x] 2.1 Replace `$responseBody` with a local variable, make the class
  `final readonly` and correct the return annotation; verify task 1.1 passes
  and `phpstan` stays green on v13 and v14.
- [x] 2.2 Add `ModifyBiteJobPostingsRequestEvent` and `ModifyBiteJobPostingsEvent`
  and dispatch them; verify tasks 1.2 and 1.3 pass on v13 and v14.

## 3. Documentation

- [x] 3.1 Add a developer chapter
  `packages/fgtclb/academic-bite-jobs/Documentation/Developers/Index.rst`
  (the shape of the other extensions with events, see `design.md`), listing
  every payload key and showing the two example listeners.
- [x] 3.2 Add `Documentation/Changelog/3.0/Feature-RequestAndResultEvents.rst`
  and `Important-FailedRequestRendersNoJobs.rst`, and add a pointer to the
  events to the migration of
  `Documentation/Changelog/2.1/Breaking-RemoveProjectSpecificCustomFields.rst`
  (its `[TODO]` was already replaced by ACE-677).
- [x] 3.3 Record the two events on the extension points page of
  `academic_base` and in `docs/architecture/list-plugin-events.md`, and drop
  the service from the list of stateful services in
  `docs/architecture/dependency-injection.md`.

## 4. File the issue

- [x] 4.1 Verify ACE-102 and ACE-154 in YouTrack. Unless ACE-40 (the parent
  of ACE-154) is the epic of `academic_bite_jobs`, keep ACE-102, close
  ACE-154 as its duplicate and rename the change to
  `ace-102-bite-jobs-request-result-events`; otherwise keep the issue under
  that epic and name the change after it.
- [ ] 4.2 Commit in TYPO3 Core format `[FEATURE] ACE-102: <subject>`,
  subject at most 52 characters, body wrapped at 72, referencing only the
  issue that stays.

## 5. Backport

- [ ] 5.1 Backport: separate change on branch `2` after a backport analysis
  (`docs/workflow/backporting.md`).

## 6. Definition of done

- [ ] 6.1 `Build/Scripts/runTests.sh -t 13 -s composerUpdate`, then
  `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` with `-t 13` green.
- [ ] 6.2 The same for `-t 14` after its own `composerUpdate`.
- [ ] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.4 `docs/` and the `Documentation/` changelog entry are part of the
  change; `README.md` and `CONTRIBUTING.md` still only summarize and link.
- [ ] 6.5 Archive the change as the last commit of the pull request and
  verify the delta spec landed in `openspec/specs/`.
