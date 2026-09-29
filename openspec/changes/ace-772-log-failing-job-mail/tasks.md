## 1. Tests first

- [x] 1.1 A test base that posts the new-job form with its hidden fields and
  reads the log of the controller from a file writer, and the failing
  transport of `main` as a fixture. Assert the job row, the answer of a saved
  job and one error log entry naming the uid. Record that it fails against the
  unchanged controller, where the `TransportException` leaves the plugin.
- [x] 1.2 The address cases: `email.recipientEmail` empty, `email.from` empty,
  and a recipient that cannot be parsed. Record that they fail with
  `RfcComplianceException` and the `InvalidArgumentException` of Symfony Mime.
- [x] 1.3 The warning on the page the form redirects to, and the success
  message as its counterpart. Record that the warning test fails against the
  unchanged controller.
- [x] 1.4 An error of another class still ends the request, shown red with a
  catch of `\Throwable`.

## 2. Implementation

- [x] 2.1 Inject `Psr\Log\LoggerInterface` into `JobController`, catch
  `RfcComplianceException`, the `InvalidArgumentException` of Symfony Mime
  and `TransportExceptionInterface` in
  `sendEmail()`, log at level error with the uid and the exception, and return
  false. The tests of group 1 pass on v12 and v13.

## 3. Documentation

- [x] 3.1 `Documentation/Changelog/2.4/Important-FailingJobMailIsLogged.rst`.
- [x] 3.2 The configuration chapter of `academic_jobs` says what happens when
  the mail cannot be sent, and where the log entry is.
- [x] 3.3 `docs/testing/`: how a failing mail and the messages of a form are
  tested.

## 4. Commit

- [x] 4.1 Commit as `[BUGFIX] ACE-772: Log a failing job mail` in TYPO3 Core
  format, with `Resolves: ACE-772`.

## 5. Definition of done

- [x] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v12 and v13, and `functional` on PostgreSQL with
  `-j 8` for both.
- [x] 5.2 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 5.3 `docs/` and the `academic-jobs` `Documentation/` changelog updated,
  `README.md` and `CONTRIBUTING.md` still only summarize and link.
- [x] 5.4 What is left out is stated in the pull request: the missing mail
  template of `main`, which has no counterpart here.
- [ ] 5.5 Archive the change as the last commit of the pull request.
