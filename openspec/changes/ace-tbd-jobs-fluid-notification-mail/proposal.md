## Why

`academic_jobs` (`packages/fgtclb/academic-jobs`) tells an editor about a job
submitted through the new-job form with one hard-coded English sentence plus
the backend link. The setting `email.template` is defined and labelled "Email
content", but nothing reads it (ACE-370). Every project that wants other or
translated wording has to change PHP or live with the sentence.

## What Changes

- The notification is rendered from a Fluid mail template and sent as a mail
  with an HTML and a plain-text part, through the configured mailer as today.
- A new setting `email.templateName` (default `JobCreated`) selects the
  template. The extension registers its own mail template path, so a project
  changes the wording with a template of the same name in its own path.
- The existing `email.template` setting keeps its documented meaning, the
  message text, and is finally rendered: a configured text replaces the
  default message. Its shipped default becomes empty, so a site that never
  set it gets the default message from the template, in English or German.
- The template receives the job, the backend edit link and the plugin
  settings.
- Recipient, sender and subject settings are unchanged.

The behaviour is the same on TYPO3 v13 and v14; the mail API it uses is
identical on both.

## Capabilities

### New Capabilities

- `academic-jobs/new-job-notification`: what the editor notification about a
  submitted job contains, and how an integrator changes its wording.

### Modified Capabilities

None.

## Impact

- `academic_jobs`: the controller that sends the mail, `ext_localconf.php`, a
  new `Resources/Private/Templates/Email/` directory, the site set settings
  definition, the TypoScript constants and setup, both language files.
- Installations: the mail becomes multipart and carries the new default
  message, unless the site configured its own `email.template` text.
- No database change, no new dependency.

## Non-goals

- A confirmation mail to the visitor who submitted the job.
- Attaching the uploaded image to the mail.
- An event that replaces the mail; a template override covers the wording.
- Moving the mail out of the controller; that belongs to the jobs form
  extraction of candidate `listings-23`.
- Catching a failing mail after the job is saved; a separate bugfix change
  after this one logs the failure and continues.
- Backporting to branch `2`, where the mail is sent through a different code
  path.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`listings-17`). Three of the six analysed projects carry their own code for
this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-<slug>` when the issue is filed after implementation.

Implements ACE-370. While ACE-370 is still open, the change is named
`ace-370-jobs-fluid-notification-mail` instead of filing a new issue.
