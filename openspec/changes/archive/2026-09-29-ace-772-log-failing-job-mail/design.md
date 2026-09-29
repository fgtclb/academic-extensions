## Context

`JobController::createAction()` persists the job, dispatches
`AfterSaveJobEvent`, and then calls `sendEmail()`, whose boolean result
chooses between the flash messages `job_created` and `job_created_no_email`.
On `main`, `sendEmail()` renders a `FluidEmail` and calls
`MailerInterface::send()`, then returns `true` unconditionally. Nothing
catches an exception.

Core does not catch either. `Mailer::send()` calls the transport directly on
TYPO3 12.4.45, 13.4.34 and 14.3.7, so an exception of the transport, and on
`main` an exception of the template rendering (which runs inside the send),
reaches the plugin. Reproduced on 2026-09-29 with a transport that throws:

- `main`, TYPO3 14.3.7: the job row exists with `hidden = 1`, and
  `Symfony\Component\Mailer\Exception\TransportException` leaves the plugin.
- `main`, TYPO3 14.3.7, `email.templateName = DoesNotExist`: the job row
  exists, and `TYPO3Fluid\Fluid\View\Exception\InvalidTemplateResourceException`
  leaves the plugin.
- Branch `2`, TYPO3 12.4.45: the job row exists, and the same
  `TransportException` leaves the plugin. There, `sendEmail()` returns
  `MailMessage::send()`, which is `false` only when the transport returns no
  sent message, so the warning is nearly unreachable on `2` as well.

The addresses fail before the send. `Symfony\Component\Mime\Address`
throws `RfcComplianceException` for an empty or invalid address when `to()`
or `from()` is called, verified with the Symfony Mime of both vendor trees.
The shipped defaults of `email.recipientEmail` and `email.from` are empty.

In production the content object exception handler of the page renders an
error in place of the plugin. The redirect after the save never happens, so a
reload posts the form again.

Core guards its own mails the same way this change does: the login
notifications of EXT:backend, the install tool and EXT:workspaces catch
`TransportException` or `TransportExceptionInterface` around
`MailerInterface::send()`.

## Goals / Non-Goals

**Goals:**

- A saved job always ends in the redirect of a saved job.
- The failure is visible to an administrator in the log.

**Non-Goals:**

- Catching errors of the save itself, or of the event listeners before the
  mail.

## Decisions

### Catch the addresses, the transport and the template, nothing wider

`sendEmail()` wraps building and sending the mail, and catches
`Symfony\Component\Mime\Exception\RfcComplianceException`,
`Symfony\Component\Mime\Exception\InvalidArgumentException`,
`Symfony\Component\Mailer\Exception\TransportExceptionInterface` and
`TYPO3Fluid\Fluid\Exception`, logs, and returns `false`. The first covers an
empty or invalid recipient or sender, as core's login notification catches
it. The second is its sibling, not its parent, and covers an address that
cannot be parsed at all, such as a display name without its closing `>`, and
one with a control character. Its common interface
`Symfony\Component\Mime\Exception\ExceptionInterface` is not caught, since it
also covers the `LogicException` of a missing email validator, a setup
defect. The Fluid base
class covers a template that cannot be found (`InvalidTemplateResourceException`,
verified on Fluid 4.6.1 and 5.3.2) and a template that cannot be parsed or
rendered.

Rejected: catching `\Throwable`. A type error or a missing service is a
programming error that a log entry would hide on a production site. Rejected
as well: catching the transport only, as core does. On `main` the template
name is a setting, and a wrong one is the likeliest failure after an update.

Branch `2` has no Fluid mail and catches `RfcComplianceException` and
`TransportExceptionInterface`.

### Log through the PSR-3 logger of the controller

The controller gets `Psr\Log\LoggerInterface` injected, which TYPO3 binds to a
logger named after the class. The entry has level `error`, the uid of the
job, and the exception in the context, as core's login notification passes
it. It does not include the job fields. Core logs its login notification at
`warning`. A lost job notification is an error, because the editors learn
about the job from nothing else.

Rejected: a flash message for the editors or a second mail. The one mail that
failed is the notification to the editors.

### The visitor gets the existing warning

`sendEmail()` returns `false`, so `createAction()` takes the existing branch
and queues `job_created_no_email` (warning) instead of `job_created`, under
the same flash message creation mode. The redirect is unchanged. No new label.

### Tests use a transport that throws

A fixture class implementing Symfony's `TransportInterface` throws a
`TransportException` from `send()`. It is selected as
`MAIL.transport = <class name>`, which `TransportFactory` instantiates on
TYPO3 12.4, 13.4 and 14.3 alike. A class of its own is needed because the mail
configuration is fixed per test instance (see `docs/testing/functional-tests.md`,
"Reading a sent mail").

The unknown template case reuses the `mbox` test base of ACE-370 with
`email.templateName` set to a name no path holds, and the address cases with
`email.recipientEmail` and `email.from` set to an empty value.

What the tests assert: the job row exists, no exception leaves the plugin,
and one log entry of level error names the uid. The log is read from a file
writer configured for the controller's logger in the test instance. The
redirect of a saved job is asserted in a test for TYPO3 v14 only, since v13
sends a returned redirect with `header()` and answers 200 with the rest of the
page.

## Risks / Trade-offs

- [A failing mail is now quiet for the visitor] → The warning tells the
  visitor that nobody was notified and asks them to get in touch, and the log
  entry has level `error`.
- [A misconfigured template name goes unnoticed longer] → The log entry names
  the exception message, which names the template and the paths looked in.
  The configuration chapter says to check the log.

## Migration Plan

Nothing to migrate.
