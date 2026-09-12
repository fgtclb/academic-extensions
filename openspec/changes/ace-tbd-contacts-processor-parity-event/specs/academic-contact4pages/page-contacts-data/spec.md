## Purpose

Defines which contacts of a page `academic_contacts4pages` provides to page
templates, how an integrator configures that, and how an extension changes the
contacts shown by the content element and the page output alike.

## ADDED Requirements

### Requirement: The page output provides the contacts the content element shows
The page output SHALL provide the contacts of the page, the roles among them
and the contacts without a role, built from the same contacts the content
element shows.

#### Scenario: Page with a contact without role
- **WHEN** a page has one contact with the role "Dean" and one contact without a role
- **THEN** the page template receives both contacts, the role "Dean", and the contact without role in the list of contacts without a role

### Requirement: Integrators name the page variable
When an integrator configures a variable name for the page output, the page
template SHALL receive the contacts, the roles and the contacts without role
below that name only. Without a configured name, the page template SHALL
receive the same top-level variables as before the change, plus the contacts
without role.

#### Scenario: Variable name configured
- **WHEN** an integrator configures the page output with the variable name `pageContacts`
- **THEN** the page template reads the contacts, roles and contacts without role below `pageContacts` and finds no top-level contacts variable

#### Scenario: No variable name configured
- **WHEN** an integrator keeps the shipped configuration
- **THEN** the page template reads the contacts and roles at the top level as before

### Requirement: Hidden contacts appear in the page output only on request
The page output SHALL leave out hidden contacts by default and SHALL include
them when the integrator enables hidden records for the page output.

#### Scenario: Default configuration
- **WHEN** a page has a hidden contact and the page output is not configured otherwise
- **THEN** the page template does not receive the hidden contact

#### Scenario: Hidden records enabled
- **WHEN** the integrator enables hidden records for the page output
- **THEN** the page template receives the hidden contact

### Requirement: The page output can read the contacts of another page
The page output SHALL read the contacts of the current page by default and
SHALL read the contacts of the page an integrator configures instead.

#### Scenario: Another page configured
- **WHEN** an integrator configures the page output to read page 12
- **THEN** the page template receives the contacts of page 12, whatever page is rendered

### Requirement: Extensions change the contacts of a page
An installed extension SHALL be able to change the list of contacts of a page
before it is rendered. The change SHALL apply to the content element and the
page output alike, and the extension SHALL be able to tell which of the two
asked. Roles and contacts without role SHALL follow the changed list.

#### Scenario: Extension removes a contact
- **WHEN** an installed extension removes one contact of a page
- **THEN** neither the content element nor the page output shows that contact

#### Scenario: Extension changes only the page output
- **WHEN** an installed extension removes a contact only when the page output asks
- **THEN** the content element still shows the contact and the page output does not

#### Scenario: Removed contact was the only one of its role
- **WHEN** an installed extension removes the only contact with the role "Office"
- **THEN** the role "Office" is not provided either
