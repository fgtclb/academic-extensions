## ADDED Requirements

### Requirement: A failing notification does not fail the submission

When the notification mail about a submitted job cannot be sent, because the
recipient or sender address is empty or invalid, or because the mail transport
refuses or cannot deliver it, the system SHALL keep the job saved, SHALL record
the failure in the log of the installation with the job it belongs to, and
SHALL answer the visitor as after any saved job. Where the site shows the
messages of the form, the visitor SHALL see that the job was created but the
notification could not be sent, instead of the success message. This SHALL
behave the same on TYPO3 v12 and v13.

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
