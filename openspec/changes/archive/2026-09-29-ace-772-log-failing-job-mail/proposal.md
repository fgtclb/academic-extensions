## Why

The backport of the `main` change of the same name, ACE-772, archived there as
`openspec/changes/archive/2026-09-29-ace-772-log-failing-job-mail`.

A visitor who submits a job through the new-job form of `academic_jobs`
(`packages/fgtclb/academic-jobs`) gets an error instead of the confirmation
when the notification mail cannot be sent, although the job is already saved.
Reloading the error page submits the form again. The warning the form has for
this case, "Notification email could not be sent", nearly never shows, because
`MailMessage::send()` returns false only when the transport returns no sent
message. Reproduced on TYPO3 12.4.45 on 2026-09-29.

## What Changes

- When the notification mail about a submitted job cannot be sent, the job
  stays saved, the failure is logged with the uid of the job, and the visitor
  gets the same redirect as after a sent mail. Where the site shows the
  messages of the form, the visitor sees the existing warning instead of the
  success message.
- A failure is a recipient or sender address that is empty or invalid, and a
  mail transport that refuses or cannot deliver the mail. The defaults of
  `email.recipientEmail` and `email.from` are empty here as well.
- Any other error still ends the request, as it does today.
- Behaviour is identical on TYPO3 v12 and v13.

Not taken over from `main`: the failing mail template. This branch sends a
plain-text `MailMessage` and has no Fluid mail, so there is no template to
miss. The requirement goes to a new capability
`academic-jobs/new-job-notification` of this branch, which has no spec of the
notification yet.

## Capabilities

### New Capabilities

- `academic-jobs/new-job-notification`: what a visitor gets when the
  notification about a submitted job cannot be sent.

### Modified Capabilities

None.

## Impact

- `academic_jobs`: the controller of the new-job form and its logger. No
  schema change, no new dependency, no new setting. `sendEmail()` keeps its
  public signature.
- Installations: a visitor who met an error page before now gets the
  confirmation redirect with a warning, and the site log gets an error entry.
  That gets an `Important-*.rst` changelog entry for 2.4.

## Non-goals

- Retrying or queueing the mail.
- A setting to switch the logging off.
- Moving the mail to a Fluid template, which `main` did as a feature.
