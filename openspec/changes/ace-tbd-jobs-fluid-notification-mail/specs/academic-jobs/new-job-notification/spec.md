## Purpose

Defines the mail an editor receives when a visitor submits a job through the
new-job form of `academic_jobs`, and how an integrator changes its wording.

## ADDED Requirements

### Requirement: Submitted jobs are announced with a templated mail
When a visitor submits a job through the new-job form, the system SHALL send
the configured recipient one mail with the configured sender and subject,
rendered from a mail template into an HTML and a plain-text part. Both parts
MUST name the submitted job by its title and MUST contain a link that opens
the new job record in the TYPO3 backend. This SHALL behave the same on TYPO3
v13 and v14.

#### Scenario: Visitor submits a job
- **WHEN** a visitor submits the new-job form with the title "Research assistant"
- **THEN** the configured recipient receives one mail with the configured sender and subject
- **AND** its HTML and its plain-text part both contain "Research assistant" and a link to the backend edit form of the new job record

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
