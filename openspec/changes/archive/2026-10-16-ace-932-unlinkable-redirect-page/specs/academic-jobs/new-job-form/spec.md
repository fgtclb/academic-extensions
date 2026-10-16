## ADDED Requirements

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

## MODIFIED Requirements

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

### Requirement: A redirect page receives the visitor

When the content element, the TypoScript constant or site setting, a listener
of the after-save event or, as the last resort, the deprecated list page
setting names a page other than the page of the form that can be linked in
the language of the visitor, the system SHALL redirect the visitor to that
page after a saved job. The confirmation SHALL be queued for the plugin as
the message mode says, for a page that renders the messages of the plugin
outside the page cache, and SHALL NOT be shown by the form later. Using the deprecated list page setting SHALL raise a
deprecation. This applies on TYPO3 v12 and v13 alike.

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
