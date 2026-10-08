# academic-jobs/new-job-notification Specification

## Purpose
Defines the mail an editor receives when a visitor submits a job through the
new-job form of `academic_jobs`, and how an integrator changes its wording.

## Requirements

### Requirement: Submitted jobs are announced with a templated mail

When a visitor submits a job through the new-job form, the system SHALL send
the configured recipient one mail with the configured sender and subject,
rendered from a mail template into an HTML and a plain-text part, unless the
mail format of the installation, or on TYPO3 v14 of the site, selects one of
them. Every part MUST name the submitted job by its title and MUST contain a
link that opens the new job record in the TYPO3 backend. The link MUST carry no
security token and no return URL, so that a backend user who opens it is taken
to the record editor of the job, through the backend login when not logged in.
This SHALL behave the same on TYPO3 v13 and v14.

#### Scenario: Visitor submits a job

- **WHEN** a visitor submits the new-job form with the title "Research assistant"
- **THEN** the configured recipient receives one mail with the configured sender and subject
- **AND** its HTML and its plain-text part both contain "Research assistant" and a link to the backend edit form of the new job record

#### Scenario: Editor opens the link of the mail

- **WHEN** a backend editor opens the link of the mail about a submitted job
- **THEN** the backend shows the record editor of that job, after the login when the editor was not logged in
- **AND** not the dashboard

### Requirement: Integrators choose the mail template

The system SHALL render the notification from the template named by the
`email.templateName` setting, looked up in the mail template paths of the
installation, with `JobCreated` as the default name. A template of that name
in a mail template path an integrator registered with a higher priority SHALL
replace the one shipped with the extension.

#### Scenario: Default template

- **WHEN** an integrator leaves `email.templateName` unchanged and registers no mail template path of their own
- **THEN** the mail is rendered from the `JobCreated` template shipped with `academic_jobs`

#### Scenario: Project template replaces the shipped one

- **WHEN** a site package registers a mail template path with a higher priority that contains a `JobCreated` template
- **THEN** the mail is rendered from the site package's template

#### Scenario: Mail template path of the site on TYPO3 v14

- **WHEN** a site on TYPO3 v14 uses the site set `typo3/email` and lists a mail template path with a `JobCreated` template in `email.templateRootPaths`
- **THEN** the mail is rendered from that template, as every mail core sends for that site

#### Scenario: Another template name

- **WHEN** an integrator sets `email.templateName` to the name of another template in the mail template paths
- **THEN** the mail is rendered from that template

### Requirement: The configured message text is used

The shipped template SHALL render the text of the `email.template` setting as
the message of the mail. When that setting is empty, the shipped template
SHALL render its default message in the language of the site language the
form was submitted in, with English and German shipped.

#### Scenario: Site configured its own text

- **WHEN** `email.template` holds "Please review the new job offer."
- **THEN** the mail contains that sentence and not the default message

#### Scenario: Default message on an English site language

- **WHEN** `email.template` is empty and the form is submitted on an English site language
- **THEN** the mail contains the English default message

#### Scenario: Default message on a German site language

- **WHEN** `email.template` is empty and the form is submitted on a site language with a German locale
- **THEN** the mail contains the German default message

### Requirement: A failing notification does not fail the submission

When the notification mail about a submitted job cannot be sent, because the
recipient or sender address is empty or invalid, because the mail transport
refuses or cannot deliver it, or because the mail template cannot be found or
rendered, the system SHALL keep the job saved, SHALL record
the failure in the log of the installation with the job it belongs to, and
SHALL answer the visitor as after any saved job. Where the site shows the
messages of the form, the visitor SHALL see that the job was created but the
notification could not be sent, instead of the success message. This SHALL
behave the same on TYPO3 v13 and v14.

#### Scenario: The mail transport fails

- **WHEN** a visitor submits the new-job form and the mail transport cannot deliver the notification
- **THEN** the job is saved and hidden, as after a sent notification
- **AND** the visitor gets the answer of a saved job and no error
- **AND** the log holds one error entry that names the saved job

#### Scenario: No recipient is configured

- **WHEN** `email.recipientEmail` is empty, as it is by default, and a visitor submits the new-job form
- **THEN** the job is saved, the visitor gets the answer of a saved job and no error
- **AND** the log holds one error entry that names the saved job

#### Scenario: The configured mail template does not exist

- **WHEN** `email.templateName` names a template that no mail template path holds, and a visitor submits the new-job form
- **THEN** the job is saved, the visitor gets the answer of a saved job and no error
- **AND** the log holds one error entry that names the saved job and the missing template

#### Scenario: The visitor is told that nobody was notified

- **WHEN** the notification of a submitted job cannot be sent, and the form shows its messages on the page the visitor gets
- **THEN** the visitor sees the warning that the job was created but the notification could not be sent
- **AND** not the message that the job was created and the mail was sent
