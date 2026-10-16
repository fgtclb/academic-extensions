# academic-jobs/new-job-form Specification

## Purpose
Defines where the new-job form of `academic_jobs` sends the visitor after a
job was saved, and whether the confirmation message is shown there.

## Requirements

### Requirement: A redirect page that is no page counts as none

After a job was saved, a redirect page of 0 or below SHALL count as no
redirect page, whether it comes from the plugin, from TypoScript or from a
listener of the after-save event. Without a redirect page the visitor SHALL
return to the form of the plugin, which SHALL show the form and the
confirmation message the configured message mode allows, as it does without
a redirect page. The deprecated list page setting SHALL apply only when
neither the plugin nor TypoScript names a page above 0 and no listener sets a
redirect page, so it SHALL NOT replace a redirect page of 0 or below that a
listener set. A listener reading the redirect page after another listener
SHALL still read the value that listener set. This applies on TYPO3 v12 and
v13.

#### Scenario: A listener sets page 0

- **WHEN** a listener of the after-save event sets the redirect page to 0 and
  the message mode suppresses the message only for a redirect to another page
- **THEN** the visitor is sent back to the page of the form
- **AND** the visitor sees the form with the confirmation message there

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

### Requirement: A saved job returns the visitor to the form

When neither the content element, the TypoScript constant or site setting, a
listener of the after-save event nor the deprecated list page setting names a
redirect page, when the redirect page is the page of the form, or when the
redirect page cannot be linked in the language of the visitor, the system
SHALL answer the submission of a saved job with a redirect to the page of the
form, to its address without any argument of a plugin. The form SHALL show
the confirmation message above itself, inside the content element, unless the
message mode is "never". The confirmation SHALL be shown once: a reload of
the form, and a visitor without the session of the submission, SHALL get the
form without it. This applies on TYPO3 v12 and v13 alike.

#### Scenario: No redirect page

- **WHEN** a visitor submits a valid job through a form without any redirect
  page
- **THEN** the job is saved
- **AND** the answer is a redirect "303 See Other" to the address of the form
  page without a query
- **AND** the form page shows the form with the confirmation "Your job advert
  has been successfully created" inside the content element

#### Scenario: The redirect page is the form page

- **WHEN** the content element or the TypoScript constant names the page of
  the form as the redirect page and a visitor submits a valid job
- **THEN** the visitor returns to the form page and sees the confirmation in
  the form

#### Scenario: Reloading the form

- **WHEN** the visitor who saw the confirmation reloads the form page
- **THEN** the form is shown without the confirmation

#### Scenario: The mode "never"

- **WHEN** the message mode is "never" and a visitor submits a valid job
  through a form without a redirect page
- **THEN** the visitor returns to the form page, and the form shows no
  confirmation

### Requirement: The submission of a saved job renders no page

The system SHALL answer the submission of a saved job with the redirect
alone, with the status "303 See Other" and an empty body, on TYPO3 v12 and
v13 alike. No page SHALL be rendered for the submission.

#### Scenario: Answer on TYPO3 v12

- **WHEN** a visitor submits a valid job on TYPO3 v12
- **THEN** the answer has the status 303 and an empty body

#### Scenario: Answer on TYPO3 v13

- **WHEN** a visitor submits a valid job on TYPO3 v13
- **THEN** the answer has the status 303 and an empty body

### Requirement: A redirect page receives the visitor

When the content element, the TypoScript constant or site setting, a listener
of the after-save event or, as the last resort, the deprecated list page
setting names a page other than the page of the form that can be linked in
the language of the visitor, the system SHALL redirect the visitor to that
page after a saved job. The confirmation SHALL be queued for the plugin as
the message mode says, for a page that renders the messages of the plugin
outside the page cache, and SHALL NOT be shown by the form later. Using the
deprecated list page setting SHALL raise a deprecation. This applies on TYPO3
v12 and v13 alike.

#### Scenario: Redirect page of the content element

- **WHEN** the content element names a redirect page with the mode "always"
  and a visitor submits a valid job
- **THEN** the visitor is redirected to that page, and a page that renders
  the messages of the plugin shows the confirmation

#### Scenario: Redirect page of the TypoScript constant

- **WHEN** only the TypoScript constant or site setting names a redirect
  page, with the mode "always", and a visitor submits a valid job
- **THEN** the visitor is redirected to that page with the confirmation

#### Scenario: Deprecated list page

- **WHEN** only the deprecated list page setting names a page and a visitor
  submits a valid job
- **THEN** the visitor is redirected to the list page
- **AND** a deprecation names the list page setting

#### Scenario: Conditional mode with a redirect page

- **WHEN** the message mode suppresses the message for a redirect to another
  page, and a redirect page is set
- **THEN** the visitor is redirected to that page and no confirmation is
  queued

#### Scenario: Confirmation left on a page that does not render it

- **WHEN** the mode is "always" and the redirect page does not render the
  messages of the plugin, and the visitor opens the form again afterwards
- **THEN** the form does not show the confirmation of the earlier job

### Requirement: A rejected submission shows only the field errors

When the validation rejects a submitted job, the system SHALL show the form
again with the message of each rejected field, and SHALL NOT queue a generic
error message for the plugin. A confirmation shown after the visitor
corrected the job SHALL come without such a message. This applies on TYPO3
v12 and v13 alike.

#### Scenario: A required field is empty

- **WHEN** a visitor submits a job without a title, on a page that renders
  the messages of the plugin
- **THEN** the form is shown again with the title marked as invalid
- **AND** no message starting "An error occurred while trying to call" is
  shown

#### Scenario: Corrected after a rejection

- **WHEN** a visitor submits a job without a title, then submits it with a
  title, and is redirected to a page that renders the messages of the plugin
- **THEN** that page shows the confirmation and no message starting "An
  error occurred while trying to call"

### Requirement: A listener's change to the saved job is stored

A change a listener of the after-save event makes to the saved job and hands
to the repository SHALL be stored, also when the listener does not persist it
itself, whether the visitor returns to the form or is sent to a redirect
page. This applies on TYPO3 v12 and v13 alike.

#### Scenario: A listener changes the title

- **WHEN** a listener of the after-save event changes the title of the saved
  job and hands the job to the repository without persisting it
- **THEN** the stored job carries the changed title

### Requirement: A submission without a job returns to the form

A request to the action that saves a job, but without a job, SHALL be
answered with the redirect "303 See Other" to the form page without any
argument of a plugin, with an empty body, and SHALL store nothing. This
applies on TYPO3 v12 and v13 alike.

#### Scenario: No job in the request

- **WHEN** a request reaches the saving action of the form without any job
- **THEN** the answer is a redirect to the address of the form page
- **AND** no job is stored

### Requirement: A job that cannot be saved returns to the form

When a submitted job could not be saved, the system SHALL send the visitor
back to the form page with the message "Your job advert could not be
created" shown in the form, and SHALL NOT send a notification mail or
dispatch the after-save event for it.

#### Scenario: The job has no uid after saving

- **WHEN** a submitted job passed the validation but was not saved
- **THEN** the visitor returns to the form page and the form shows that the
  job advert could not be created

### Requirement: The message mode items say what they do

The message mode of the content element SHALL offer its items with labels
that describe their effect: "Use the site or TypoScript setting" for the item
that defers to the TypoScript constant or site setting, "always", "never", and "Suppress
when a redirect page is set" for the conditional item, with German labels on
a German backend. The stored values SHALL stay `-1`, `1`, `2` and `0`. This
applies on TYPO3 v12 and v13 alike.

#### Scenario: Editor opens the content element

- **WHEN** an editor opens the job form content element in an English
  backend
- **THEN** the message mode offers "Use the site or TypoScript setting",
  "always", "never" and "Suppress when a redirect page is set"

#### Scenario: German backend

- **WHEN** an editor opens the job form content element in a German backend
- **THEN** the conditional item reads "Unterdrücken, wenn eine
  Weiterleitungsseite gesetzt ist"

### Requirement: Select item listeners find an empty record in the job form
When the job form builds the choices of its fields "Job Type" and
"Employment Type", it SHALL hand every listener of the select items event an
empty record, because the job does not exist yet, so that the form renders
when a listener reads the record, instead of the listener raising a warning.
The record of an edited job in the backend SHALL stay what the backend form passes. This
SHALL apply on TYPO3 v12 and v13 alike.

#### Scenario: A listener reads the record in the job form
- **WHEN** a site has a listener of the select items event that reads the record without a fallback and adds a choice for the page of that record, falling back to the page of the form for a record without a page
- **THEN** the job form renders, and both selects offer the choice of the listener for the page of the form

#### Scenario: The job form calls no listener with a record of its own
- **WHEN** the job form builds the choices of its two selects
- **THEN** the record a listener receives holds no field

### Requirement: A redirect page that cannot be linked returns the visitor to the form

When the redirect page of a saved job cannot be linked in the language of the
visitor, for example because it has no translation into a language with
strict fallback, because it is hidden, deleted or missing, or because it is
restricted to other users or outside its publish dates, the system SHALL
treat it as no redirect page, whichever of the content element, the site
setting, a listener of the after-save event or the deprecated list page
setting names it. The answer SHALL be a redirect to the page of the form in
the language of the visitor, without any argument of a plugin, and the form
SHALL show the confirmation as the message mode decides for a form without a
redirect page.
The system SHALL log a warning that names the redirect page, the page of the
form and the language. A redirect page that can be linked SHALL receive the
visitor as before. This applies on TYPO3 v12 and v13 alike, except for a
folder: TYPO3 v13 links no folder, so a folder SHALL return the visitor to the
form there, while TYPO3 v12 links it and SHALL send the visitor to it.

#### Scenario: The redirect page has no translation

- **WHEN** the site has an English and a German language with strict
  fallback, the German content element of the form names a redirect page
  without a German translation with the mode "Suppress when a redirect page
  is set", and a visitor submits a valid job on the German form page
- **THEN** the job is saved
- **AND** the answer is a redirect "303 See Other" to the German address of
  the form page
- **AND** the form shows the German confirmation
- **AND** the log holds one warning that names the redirect page, the form
  page and the German language

#### Scenario: Every source of the redirect page

- **WHEN** the untranslated redirect page is named by the site setting, by a
  listener of the after-save event or by the deprecated list page setting
- **THEN** the visitor returns to the German form page with the confirmation,
  and the warning is logged

#### Scenario: The mode "never"

- **WHEN** the message mode is "never" and the redirect page cannot be linked
- **THEN** the visitor returns to the form page, and the form shows no
  confirmation

#### Scenario: The mode "always"

- **WHEN** the message mode is "always" and the redirect page cannot be linked
- **THEN** the visitor returns to the form page, and the form shows the
  confirmation
- **AND** the form page requested without the session of the submission shows
  no confirmation

#### Scenario: A notification mail that cannot be sent

- **WHEN** the notification mail cannot be sent and the redirect page cannot
  be linked
- **THEN** the visitor returns to the form page, and the form shows the
  warning that the notification mail could not be sent

#### Scenario: A page that cannot be linked in any language

- **WHEN** the redirect page of the English form is hidden or deleted
- **THEN** the visitor returns to the English form page with the confirmation,
  and the warning names the English language

#### Scenario: A folder on TYPO3 v13

- **WHEN** the redirect page of the English form is a folder, on TYPO3 v13
- **THEN** the visitor returns to the English form page with the confirmation,
  and the warning names the English language

#### Scenario: A folder on TYPO3 v12

- **WHEN** the redirect page of the English form is a folder, on TYPO3 v12
- **THEN** the visitor is sent to the address of the folder, and nothing is
  logged

#### Scenario: The redirect page exists in the language of the visitor

- **WHEN** the English form names the same redirect page, which exists in
  English
- **THEN** the visitor is sent to that page, and nothing is logged
