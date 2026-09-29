## 1. Tests first

- [x] 1.1 Add a fixture transport that throws a `TransportException` from
  `send()`, below `Tests/Functional/Plugins/Fixtures/`, and a test class that
  selects it as `MAIL.transport` and posts the new-job form. Assert the job
  row, the answer of a saved job without an exception, and one error log entry
  naming the uid, read from a file log writer configured for the controller's
  logger. Record that it fails against the unchanged controller, where the
  `TransportException` leaves the plugin.
- [x] 1.2 Add the unknown template case on the `mbox` base of ACE-370:
  `email.templateName` names a template no path holds. Record that it fails
  against the unchanged controller with `InvalidTemplateResourceException`.
  Add the address cases the same way: `email.recipientEmail` empty,
  `email.from` empty, and a recipient that cannot be parsed. Record that they
  fail with `RfcComplianceException` and the `InvalidArgumentException` of
  Symfony Mime.
- [x] 1.3 Pin the warning: on a page that renders the flash messages of the
  form (`f:flashMessages` with the queue of the plugin), after following the
  redirect, the `job_created_no_email` text shows and the `job_created` text
  does not. Record that it fails against the unchanged controller. If the
  queue cannot be rendered in a functional test on both core versions, say so
  in the pull request and cover the return value instead.

## 2. Implementation

- [x] 2.1 Inject `Psr\Log\LoggerInterface` into `JobController` and catch
  `RfcComplianceException`, the `InvalidArgumentException` of Symfony Mime,
  `TransportExceptionInterface` and `TYPO3Fluid\Fluid\Exception` around
  building and sending the mail in
  `sendEmail()`. Log at level error with the uid and the exception,
  and return `false`. Verify that the tests of group 1 pass on v13 and v14,
  and that an exception of another class still leaves the plugin (a short
  test, or a statement in the pull request with the reason).

## 3. Documentation

- [x] 3.1 `Documentation/Changelog/3.0/Important-FailingJobMailIsLogged.rst`
  from `Build/Documentation/Templates/`: what the visitor saw before and sees
  now, the log entry, and that another error still ends the request.
- [x] 3.2 Replace the note on the failure in the notification mail section of
  `Documentation/Configuration/General/Index.rst` with the new behaviour and
  where to find the log entry.
- [x] 3.3 `docs/testing/functional-tests.md`, section "Reading a sent mail":
  add the fixture transport as the way to test a failing mail.

## 4. Commit

- [x] 4.1 Commit as `[BUGFIX] ACE-772: Log a failing job mail` in TYPO3 Core
  format, body wrapped at 72, with `Resolves: ACE-772`.

## 5. Definition of done

- [x] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional -j auto` for TYPO3 v13, all green.
- [x] 5.2 The same five suites for TYPO3 v14 after its own `composerUpdate`.
- [x] 5.3 `functional` on PostgreSQL for both core versions (`-d postgres -j
  8`), since the form writes a job record.
- [x] 5.4 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 5.5 `docs/` and the `academic-jobs` `Documentation/` changelog updated,
  `README.md` and `CONTRIBUTING.md` still only summarize and link.
- [ ] 5.6 Archive the change as the last commit of the pull request, and
  verify the requirement landed in
  `openspec/specs/academic-jobs/new-job-notification/spec.md`.
- [x] 5.7 Backport analysis for branch `2` (`docs/workflow/backporting.md`):
  the controller sends a plain `MailMessage` there, has no Fluid mail and no
  `frontendPostRequest()` in its testing helper. The backport is a change of
  its own on `2`, catching `RfcComplianceException` and
  `TransportExceptionInterface`, with a `2.4` `Important-*.rst` entry.
  Reproduced there on TYPO3 12.4.45 on 2026-09-29: the job is saved and the
  `TransportException` leaves the plugin.
