## Why

A visitor who submits a job through the new-job form of `academic_jobs`
(`packages/fgtclb/academic-jobs`) gets an error instead of the confirmation
when the notification mail cannot be sent, although the job is already saved.
Reloading the error page submits the form again and saves the job a second
time. The warning the form has for exactly this case, "Notification email
could not be sent", never shows.

## What Changes

- When the notification mail about a submitted job cannot be sent, the job
  stays saved, the failure is logged with the uid of the job, and the visitor
  gets the same redirect as after a sent mail. Where the site shows the
  messages of the form, the visitor sees the existing warning that the job was
  created but nobody was notified, instead of the success message.
- A failure is a recipient or sender address that is empty or invalid, a
  mail transport that refuses or cannot deliver the mail, and, on `main` only,
  a mail template that cannot be found or rendered, for example an
  `email.templateName` that no mail template path holds. The shipped defaults
  of `email.recipientEmail` and `email.from` are empty, so a site that never
  set them meets the error after every submitted job today.
- Any other error still ends the request, as it does today.
- Behaviour is identical on TYPO3 v13 and v14. Branch `2` has the same defect
  on TYPO3 v12 and v13, reproduced on v12.4.45, and gets the fix as a change
  of its own after `main`.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-jobs/new-job-notification`: what a visitor gets when the
  notification cannot be sent.

## Impact

- `academic_jobs`: the controller of the new-job form and its logger. No
  schema change, no new dependency, no new setting.
- Installations: a visitor who met an error page before now gets the
  confirmation redirect with a warning, and the site log gets an error entry.
  That changes what a visitor sees, so it gets an `Important-*.rst` changelog
  entry.

## Non-goals

- Retrying or queueing the mail. A site that needs delivery guarantees
  configures a spool transport.
- A setting to switch the logging off, or to show the error again.
- An event about the failed mail.
- Telling the editors in another way than the log.

## Source

Filed as ACE-772 on 2026-09-29 while implementing ACE-370 (#794), where the
change was left out on purpose so that its tests could tell a template change
from a change of the error handling. Relates to ACE-370.
