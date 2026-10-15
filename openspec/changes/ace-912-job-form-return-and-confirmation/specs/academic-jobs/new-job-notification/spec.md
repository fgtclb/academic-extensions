## MODIFIED Requirements

### Requirement: A failing notification does not fail the submission

When the notification mail about a submitted job cannot be sent, because the
recipient or sender address is empty or invalid, or because the mail transport
refuses or cannot deliver it, the system SHALL keep the job saved, SHALL record
the failure in the log of the installation with the job it belongs to, and
SHALL answer the visitor as after any saved job. Where the visitor sees the
messages of the form, on the form itself when the visitor returns to it, or on
a redirect page that renders the messages of the plugin, the visitor SHALL see
that the job was created but the notification could not be sent, instead of
the success message. The message mode decides whether the warning is queued,
as it does for the success message. This SHALL behave the same on TYPO3 v12
and v13.

#### Scenario: The mail transport fails

- **WHEN** a visitor submits the new-job form and the mail transport cannot deliver the notification
- **THEN** the job is saved and hidden, as after a sent notification
- **AND** the visitor gets the answer of a saved job and no error
- **AND** the log holds one error entry that names the saved job

#### Scenario: No recipient is configured

- **WHEN** `email.recipientEmail` is empty, as it is by default, and a visitor submits the new-job form
- **THEN** the job is saved, the visitor gets the answer of a saved job and no error
- **AND** the log holds one error entry that names the saved job

#### Scenario: The visitor is told that nobody was notified

- **WHEN** the notification of a submitted job cannot be sent, and the form shows its messages on the page the visitor gets
- **THEN** the visitor sees the warning that the job was created but the notification could not be sent
- **AND** not the message that the job was created and the mail was sent

#### Scenario: The form tells the visitor that nobody was notified

- **WHEN** the notification of a submitted job cannot be sent and the form has no redirect page
- **THEN** the visitor returns to the form, which shows the warning that the job was created but the notification could not be sent
- **AND** not the message that the job was created and the mail was sent

#### Scenario: The conditional mode with a redirect page

- **WHEN** the notification of a submitted job cannot be sent, a redirect page is set, and the message mode suppresses the message for a redirect to another page
- **THEN** the visitor is redirected to that page without the warning
