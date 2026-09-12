## Purpose

Defines how the contacts content element of `academic_contacts4pages` groups
the contacts of its page and through which template each contact renders.

## ADDED Requirements

### Requirement: Role grouping is configurable per content element
The contacts content element SHALL offer an editor option to group contacts
by role. The option SHALL be on for new content elements and for content
elements saved before the option existed. With the option on, contacts with a
role SHALL render under one heading per role, and contacts without a role
SHALL render after the grouped contacts, as they do today.

#### Scenario: Grouping on
- **WHEN** a page has contacts with the roles "Dean" and "Office" and the option is on
- **THEN** the content element renders a "Dean" and an "Office" heading, each followed by the contacts of that role

#### Scenario: Content element saved before the option existed
- **WHEN** a content element was saved before the option was introduced and is rendered again
- **THEN** its contacts render grouped by role, as before

#### Scenario: Grouping off
- **WHEN** an editor switches the option off
- **THEN** the content element renders no role headings and lists all contacts of the page in the order the editor sorted them

### Requirement: The ungrouped list names the role of each contact
With role grouping switched off, the content element SHALL show the role name
with each contact that has a role, and SHALL show no role for a contact
without one.

#### Scenario: Contact with a role in the ungrouped list
- **WHEN** grouping is off and a contact has the role "Dean"
- **THEN** that contact renders with the role name "Dean"

#### Scenario: Contact without a role in the ungrouped list
- **WHEN** grouping is off and a contact has no role
- **THEN** that contact renders without a role name

### Requirement: Every contact renders through one overridable item template
The content element SHALL render each contact through one item template of
`academic_contacts4pages`, in the grouped list, in the ungrouped list and for
contacts without a role. Without an override, the item template SHALL produce
the same markup as before the change. An integrator SHALL be able to replace
the item template alone through the extension's partial paths.

#### Scenario: No override
- **WHEN** no integrator template overrides the item template
- **THEN** every contact renders with the same markup as before the change

#### Scenario: Integrator overrides the item template
- **WHEN** a site package provides its own contact item template in a partial path with a higher priority
- **THEN** every contact, grouped, ungrouped or without role, renders through the site package's template

### Requirement: The content element record is available to templates
The contacts content element SHALL provide its content element record to its
templates, so that an integrator's template can render the content element
header through the core header template. On TYPO3 v14 that header template
requires the record.

#### Scenario: Header rendered through the core header template on TYPO3 v14
- **WHEN** an integrator's list template renders the content element header through the core header template on TYPO3 v14
- **THEN** the page renders without error and shows the header of the content element
