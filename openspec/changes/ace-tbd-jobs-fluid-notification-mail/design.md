## Context

`JobController::createAction()` persists the job, dispatches
`AfterSaveJobEvent` and then calls `sendEmail()`
(`packages/fgtclb/academic-jobs/Classes/Controller/JobController.php:413-425`).
That method builds a `MailMessage` with the fixed text at `:421` and sends it
through the injected `MailerInterface`. The settings reach the controller
through `Configuration/TypoScript/setup.typoscript:31-36`, fed by
`constants.typoscript:35-44` and by the site set definitions in
`Configuration/Sets/Full/settings.definitions.yaml:37-56`.

The candidate assumed `email.template` names a template. It does not: both
the constant and the site setting label it "Email content" and default it to
the sentence "A new job application has been submitted. Please check the
backend." A site that configured it holds a sentence, not a file name.

`FluidEmail` has the same constructor, `setTemplate()`, `assign()`,
`assignMultiple()` and `setRequest()` on TYPO3 13.4.34 and 14.3.6, and both
read `$GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths']` when no
template paths are passed. No version switch is needed.

## Goals / Non-Goals

**Goals:**

- The wording lives in a template that a project overrides by name.
- The existing `email.template` setting starts to do what its label says.
- How and when the mail is sent does not change.

**Non-Goals:**

- Extracting the mail into a service; see candidate `listings-23`.
- Changing error handling of the mail transport; see the decision below.

## Decisions

### A new `email.templateName` setting

`plugin.tx_academicjobs.email.templateName` (site setting, TypoScript
constant and setup mapping `settings.email.templateName`) selects the
template, default `JobCreated`.

Rejected: reinterpreting `email.template` as the template name, as the
candidate proposed. Every site that configured a sentence would then look up
a template of that name and fail after the job is already saved. Rejected
too: renaming `email.template` to `email.text`, which is breaking for the
sites that set it and gains nothing.

### The configured text is a template variable

The template receives `emailText` from `settings.email.template` and renders
it when it is not empty, else `f:translate` of a new label
`email.jobCreated.message` in `locallang.xlf` and `de.locallang.xlf`. The
shipped default of `email.template` becomes `''` in the constants and in
`settings.definitions.yaml`. A changed TypoScript or site setting default
reaches every site that did not override it, which is intended here: those
sites get the translated message, and sites with their own sentence keep it.

Rejected: keeping the English default text. The translated default message
could then never show.

### The template path is registered in `ext_localconf.php`

`$GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths'][200] =
'EXT:academic_jobs/Resources/Private/Templates/Email/'`. Core registers `0`
and `10`; `200` is above them and below the keys site packages usually take,
so a site package path with a higher key wins. The template uses the core
`SystemEmail` layout with the sections `Title` and `Main`, so a site that
restyles core mails restyles this one too.

Rejected: an own layout. It would look different from every other mail an
installation sends, for no gain.

### Variables, request and sending

`FluidEmail` gets `setTemplate($templateName)`, `setRequest($this->request)`
and the variables `job`, `url` (the existing `buildUrl()` result), `settings`
and `emailText`. The request is what the translation and link ViewHelpers
need inside a mail. Recipient, sender and subject come from the same settings
as today, and the mail is sent through the injected `MailerInterface` as
today.

### The test reads the mail from an mbox file

The functional test configures `MAIL.transport = mbox` with
`transport_mbox_file` below the test instance and reads the file after the
form POST. Both cores support that transport (`TransportFactory.php`, `case
'mbox'`, on 13.4.34 and 14.3.6).

Rejected: a spy `MailerInterface` in a fixture extension. The controller is
resolved from the container, so the spy would have to replace a core alias
per test. The mbox file also proves what the recipient gets: multipart
structure, headers and both bodies.

### Decided: a failing mail is logged in a separate bugfix change

A notification that fails after the job is saved (unknown template, transport
error) is to be logged, and the request continues to the success page. That
is done in its own bugfix change after this one, not here. This change keeps
today's behaviour: the exception reaches the visitor.

`JobController::sendEmail()` has no `try`/`catch` around
`$this->mailer->send()` today, so a visitor already sees an error for a job
that is saved. That is a defect of its own. Mixed into the template change,
a red test could not tell whether the template or the transport handling
changed.

## Risks / Trade-offs

- [The wording of the mail changes for sites that never configured it] →
  The `Feature-` changelog entry quotes the new message and shows how to set
  `email.template` or override `JobCreated`.
- [The translation ViewHelper might not take the language from the request
  inside a mail on one of the versions] → A German scenario test runs on v13
  and v14. If it fails on one of them, the template passes `languageKey`
  from the site language explicitly instead of relying on the request.
- [An unknown `email.templateName` throws after the job is saved] → The
  documentation names the failure; the follow-up bugfix change logs it and
  continues.

## Migration Plan

Nothing to migrate. A site that wants the old sentence sets `email.template`
to it.

## Open Questions

None.
