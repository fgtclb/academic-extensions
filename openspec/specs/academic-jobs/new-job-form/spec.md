# academic-jobs/new-job-form Specification

## Purpose
Defines what the new-job form of `academic_jobs` offers a visitor, how it
stores the two job flags "internationals welcome" and "recommended by
alumni" and the dates a visitor enters, and where an integrator adds a field
of their own.

## Requirements

### Requirement: The form offers the two job flags

The new-job form SHALL offer "internationals welcome" and "recommended by
alumni" as optional checkboxes with translated labels. This applies to
TYPO3 v13 and v14 alike.

#### Scenario: Form is rendered

- **WHEN** a visitor opens the new-job form
- **THEN** the form contains a checkbox for each of the two flags

#### Scenario: Alumni flag label

- **WHEN** a visitor opens the new-job form on the default language
- **THEN** the alumni checkbox is labelled "Recommended by alumni", and on a
  German site language "Von Alumni empfohlen"

#### Scenario: Internationals flag label

- **WHEN** a visitor opens the new-job form on the default language
- **THEN** the internationals checkbox is labelled "International applicants
  welcome", and on a German site language "Internationale Bewerbungen
  willkommen"

### Requirement: Flags are stored as set or not set

A submitted job SHALL store a flag as set when its checkbox was checked and as
not set when it was unchecked. An unchecked flag MUST NOT cause a validation
or mapping error.

#### Scenario: Flag unchecked

- **WHEN** a visitor submits the form with both flag checkboxes unchecked and
  all required fields filled
- **THEN** the job is created with both flags not set and no error is shown

#### Scenario: Flag checked

- **WHEN** a visitor submits the form with the "internationals welcome"
  checkbox checked
- **THEN** the job is created with that flag set

#### Scenario: Form returned with an error

- **WHEN** a visitor submits the form with a flag checked and a required field
  empty
- **THEN** the form is shown again with the error, and the flag is still
  checked

### Requirement: Dates are stored as whole days

The form SHALL store the day a submitted job is shown from, the field "When",
at the start of that day and the application deadline at the last second of
its day, both in the time zone of the server, whatever the time of day of the
submission. The employment start date SHALL be stored as the chosen day. A date
left empty SHALL stay empty. This applies to TYPO3 v13 and v14 alike.

#### Scenario: Shown from, deadline and employment start date

- **WHEN** a visitor submits the form with "When" 2027-03-01, the application
  deadline 2027-03-31 and the employment start date 2027-04-01
- **THEN** the job is shown from 2027-03-01 00:00:00 and through
  2027-03-31 23:59:59, in the time zone of the server
- **AND** its employment start date is 2027-04-01

#### Scenario: No dates

- **WHEN** a visitor submits the form without "When" and an application
  deadline
- **THEN** the job is stored without either

### Requirement: The form has a slot for additional fields

The new-job form SHALL render an additional-fields partial before its submit
button. The shipped partial SHALL render nothing, so an integrator can add
fields by overriding that one partial.

#### Scenario: Default installation

- **WHEN** the form is rendered without an override of the partial
- **THEN** the form output is unchanged apart from the two flag checkboxes

#### Scenario: Integrator adds a field

- **WHEN** an integrator overrides the additional-fields partial with a field
- **THEN** the field is rendered before the submit button

### Requirement: A redirect page that is no page counts as none

After a job was saved, a redirect page of 0 or below SHALL count as no
redirect page, whether it comes from the plugin, from TypoScript or from a
listener of the after-save event. Without a redirect page the visitor SHALL
return to the form of the plugin, which SHALL show the form and the
confirmation message the configured message mode allows, as it does without
a redirect page. A listener reading the redirect page after another listener
SHALL still read the value that listener set. This applies on TYPO3 v13 and
v14.

#### Scenario: A listener sets page 0

- **WHEN** a listener of the after-save event sets the redirect page to 0 and
  the message mode suppresses the message only for a redirect to another page
- **THEN** the visitor stays on the page of the form
- **AND** the visitor sees the confirmation message there

#### Scenario: A listener sets a negative page

- **WHEN** a listener of the after-save event sets the redirect page to -3
- **THEN** the visitor is not redirected to another page

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

#### Scenario: A listener sets page 0 while a list page is configured

- **WHEN** a listener of the after-save event sets the redirect page to 0 and
  the deprecated list page setting names a page
- **THEN** the visitor returns to the page of the form, not to the list page

### Requirement: A saved job returns the visitor to the form

When neither the content element, the site setting, a listener of the
after-save event nor the deprecated list page setting names a redirect page,
when the redirect page is the page of the form, or when the redirect page
cannot be linked in the language of the visitor, the system SHALL answer
the submission of a saved job with a redirect to the page of the form, to its
address without any argument of a plugin. The form SHALL show the
confirmation message above itself, inside the content element, unless the
message mode is "never". The confirmation SHALL be shown once: a reload of
the form, and a visitor without the session of the submission, SHALL get the
form without it. This applies on TYPO3 v13 and v14 alike.

#### Scenario: No redirect page

- **WHEN** a visitor submits a valid job through a form without any redirect
  page
- **THEN** the job is saved
- **AND** the answer is a redirect "303 See Other" to the address of the form
  page without a query
- **AND** the form page shows the form with the confirmation "Your job advert
  has been successfully created" inside the content element

#### Scenario: The redirect page is the form page

- **WHEN** the content element or the site setting names the page of the form
  as the redirect page and a visitor submits a valid job
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
alone, with the status "303 See Other" and an empty body, on TYPO3 v13 and
v14 alike. No page SHALL be rendered for the submission.

#### Scenario: Answer on TYPO3 v13

- **WHEN** a visitor submits a valid job on TYPO3 v13
- **THEN** the answer has the status 303 and an empty body

#### Scenario: Answer on TYPO3 v14

- **WHEN** a visitor submits a valid job on TYPO3 v14
- **THEN** the answer has the status 303 and an empty body

### Requirement: A redirect page receives the visitor

When the content element, the site setting, a listener of the after-save
event or, as the last resort, the deprecated list page setting names a page
other than the page of the form that can be linked in the language of the
visitor, the system SHALL redirect the visitor to
that page after a saved job. The confirmation SHALL be queued for the plugin
as the message mode says, for a page that renders the messages of the plugin
outside the page cache, and SHALL NOT be shown by the form later. Using the
deprecated list page setting SHALL raise a deprecation. This applies on
TYPO3 v13 and v14 alike.

#### Scenario: Redirect page of the content element

- **WHEN** the content element names a redirect page with the mode "always"
  and a visitor submits a valid job
- **THEN** the visitor is redirected to that page, and a page that renders
  the messages of the plugin shows the confirmation

#### Scenario: Redirect page of the site setting

- **WHEN** only the site setting names a redirect page, with the mode
  "always", and a visitor submits a valid job
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
v13 and v14 alike.

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

### Requirement: A job that cannot be saved returns to the form

When a submitted job could not be saved, the system SHALL send the visitor
back to the form page with the message "Your job advert could not be
created" shown in the form, and SHALL NOT send a notification mail or
dispatch the after-save event for it.

#### Scenario: The job has no uid after saving

- **WHEN** a submitted job passed the validation but was not saved
- **THEN** the visitor returns to the form page and the form shows that the
  job advert could not be created

#### Scenario: A request without a job

- **WHEN** the form receives a submission that carries no job at all
- **THEN** nothing is saved and the visitor returns to the form page without
  a message

### Requirement: Changes of an after-save listener are stored

A change a listener of the after-save event makes to the saved job, or to
another record, through a repository SHALL be stored before the visitor is
redirected, also when the listener does not persist it itself. This applies
on TYPO3 v13 and v14 alike.

#### Scenario: A listener changes the title

- **WHEN** a listener of the after-save event changes the title of the saved
  job and hands it to the repository without persisting it
- **THEN** the job is stored with the changed title

### Requirement: The message mode items say what they do

The message mode of the content element SHALL offer its items with labels
that describe their effect: "Use the site or TypoScript setting" for the
item that defers to the site setting or the TypoScript constant, "always",
"never", and "Suppress when a redirect page is set" for the conditional item,
with German labels on a German backend. The stored values SHALL stay `-1`,
`1`, `2` and `0`.

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
The record of
an edited job in the backend SHALL stay what the backend form passes. This
SHALL apply on TYPO3 v13 and v14 alike.

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
visitor as before. This applies on TYPO3 v13 and v14 alike.

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

- **WHEN** the redirect page of the English form is hidden, deleted or a
  folder
- **THEN** the visitor returns to the English form page with the confirmation,
  and the warning names the English language

#### Scenario: The redirect page exists in the language of the visitor

- **WHEN** the English form names the same redirect page, which exists in
  English
- **THEN** the visitor is sent to that page, and nothing is logged
