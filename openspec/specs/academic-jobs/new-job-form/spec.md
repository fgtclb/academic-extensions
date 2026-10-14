# academic-jobs/new-job-form Specification

## Purpose
Defines where the new-job form of `academic_jobs` sends the visitor after a
job was saved, and whether the confirmation message is shown there.

## Requirements

### Requirement: A redirect page that is no page counts as none

After a job was saved, a redirect page of 0 or below SHALL count as no
redirect page, whether it comes from the plugin, from TypoScript or from a
listener of the after-save event. Without a redirect page the visitor SHALL
return to the form of the plugin, and the confirmation message SHALL follow
the configured message mode as it does without a redirect page. The
deprecated list page setting SHALL apply only when neither the plugin nor
TypoScript names a page above 0 and no listener sets a redirect page, so it
SHALL NOT replace a redirect page of 0 or below that a listener set. A
listener reading the redirect page after another listener SHALL still read the
value that listener set. This applies on TYPO3 v12 and v13.

#### Scenario: A listener sets page 0

- **WHEN** a listener of the after-save event sets the redirect page to 0 and
  the message mode suppresses the message only for a redirect to another page
- **THEN** the visitor is sent back to the page of the form
- **AND** the visitor sees the confirmation message there

#### Scenario: A listener sets a negative page

- **WHEN** a listener of the after-save event sets the redirect page to -1
- **THEN** the visitor is sent back to the page of the form, not to another
  page

#### Scenario: A list page is configured as well

- **WHEN** a listener of the after-save event sets the redirect page to 0 and
  the deprecated list page setting names another page
- **THEN** the visitor is sent back to the page of the form, not to the list
  page

#### Scenario: A listener sets a page

- **WHEN** a listener of the after-save event sets the redirect page to an
  existing page other than the page of the form, and the message mode
  suppresses the message only for a redirect to another page
- **THEN** the visitor is redirected to that page without a message, as
  before

#### Scenario: Listeners see what was set

- **WHEN** a first listener sets the redirect page to 0 and a second listener
  reads it
- **THEN** the second listener reads 0
