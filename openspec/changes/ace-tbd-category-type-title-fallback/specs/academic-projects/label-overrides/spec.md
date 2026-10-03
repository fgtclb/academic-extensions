## ADDED Requirements

### Requirement: A category type without a label of the extension is labelled with its registered title

Wherever the project page, the project card or the filter of the project list names a
category type, the system SHALL use the label `sys_category.projects.<identifier>`
of the extension when the extension or the site provides one. When neither
does, the system SHALL use the title the type is registered with, translated
into the language of the page when the title is a translation reference and
shown as written otherwise. This SHALL apply on TYPO3 v13 and v14.

#### Scenario: A project type on the project page

- **WHEN** a project registers a type for the group `projects` with the title
  "Funding body" and provides no label for it
- **THEN** the categories of the project page show the type as "Funding body"

#### Scenario: A project type in the filter

- **WHEN** the project list offers that type as a filter
- **THEN** its select is labelled "Funding body"

#### Scenario: A label of the site wins

- **WHEN** a site sets the label of that type through `_LOCAL_LANG`
- **THEN** the project list shows that label, not the registered title
