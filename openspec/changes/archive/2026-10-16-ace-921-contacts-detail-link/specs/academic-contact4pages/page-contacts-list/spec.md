## ADDED Requirements

### Requirement: The contact list follows the detail link setting of the persons extension
The contacts content element SHALL link the names of its contacts to the
profile detail view as long as the site keeps the detail link setting of
`academic_persons` at "link", its default, and SHALL show the names as text,
without a link, when the site sets it to "no link". This SHALL apply to
contacts with a role and without one, whether the site sets the setting as a
site setting of the persons site set or as the TypoScript constant of the
static templates, and on TYPO3 v13 and v14 alike.

#### Scenario: A site keeps the default
- **WHEN** a site sets neither the site setting nor the constant and a contacts content element shows the contacts of its page
- **THEN** the name of every contact links to the detail view of its profile

#### Scenario: A site set site without detail pages
- **WHEN** a site uses the site sets of both extensions and sets the site setting to "no link"
- **THEN** the contacts content element shows the name of every contact without a link

#### Scenario: A static template site without detail pages
- **WHEN** a site selects the static template of the contact list, and no static template of the persons extension, and sets the constant to "no link"
- **THEN** the contacts content element shows the name of every contact without a link
