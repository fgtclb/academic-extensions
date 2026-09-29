## Context

A job submitted through the new-job form is saved, `AfterSaveJobEvent` is
dispatched, and `sendEmail()` then builds and sends the notification. Its
boolean result chooses between the flash messages `job_created` and
`job_created_no_email`. What this branch has:

- `JobController::sendEmail()` is public and builds a plain-text
  `MailMessage`, then returns `MailMessage::send()`. On TYPO3 12.4.45 and
  13.4.34, `send()` calls the mailer, which calls the transport without a
  catch, and returns false only when the transport returns no sent message.
- The addresses fail while the mail is built: `Symfony\Component\Mime\Address`
  throws `RfcComplianceException` for an empty or invalid address when `to()`
  or `from()` is called.
- The testing helper of this branch has no `frontendPostRequest()` and no test
  base that posts the form or reads a mail.

## Goals / Non-Goals

**Goals:**

- A saved job always ends in the redirect of a saved job, on TYPO3 v12 and
  v13.
- The failure is visible to an administrator in the log.

**Non-Goals:**

- Catching errors of the save itself, or of the event listeners before the
  mail.

## Decisions

### Catch the addresses and the transport

`sendEmail()` wraps building and sending the mail, catches
`Symfony\Component\Mime\Exception\RfcComplianceException`,
`Symfony\Component\Mime\Exception\InvalidArgumentException` and
`Symfony\Component\Mailer\Exception\TransportExceptionInterface`, logs, and
returns false. The second is a sibling of the first, not its parent, and
covers an address that cannot be parsed at all, such as a display name without
its closing `>`, and, with the symfony/mime versions that check it, one with a
control character. Their common interface is not caught, since it also covers
the `LogicException` of a missing email validator, a setup defect. There is no
Fluid mail here, so `TYPO3Fluid\Fluid\Exception` is not caught. The signature
stays as it is.

Rejected: catching `\Throwable`. A type error or a missing service is a
programming error that a log entry would hide on a production site.

### Log through the PSR-3 logger of the controller

As on `main`: `Psr\Log\LoggerInterface` is injected into the constructor, which
TYPO3 v12 and v13 bind to a logger named after the class. The class is final,
so the new constructor argument breaks nobody. Level `error`, the uid of the
job and the exception in the context.

### Tests post the form themselves

A test base of its own posts the rendered form with its hidden fields, builds
the request body inline because the testing helper has no helper for it, and
reads the log from a file writer configured for the controller. The failing
transport is the fixture class of `main`, selected as `MAIL.transport`. The
address cases, an empty `email.recipientEmail` and an empty `email.from`, and
the warning run with the `null` transport, which sends nothing and reports the
mail as sent.

## Risks / Trade-offs

- [A failing mail is now quiet for the visitor] → The warning tells the
  visitor that nobody was notified, and the log entry has level `error`.

## Migration Plan

Nothing to migrate.
