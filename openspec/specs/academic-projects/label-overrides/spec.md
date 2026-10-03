# academic-projects/label-overrides Specification

## Purpose
Lets an integrator change the labels the project lists and project pages show,
through TypoScript, on every supported core version.

## Requirements

### Requirement: Labels of a plugin read the overrides of the extension and of the plugin

Every label a plugin of the extension renders - labels of the templates and
partials, category type and active state labels, and the options of the sorting
select - SHALL read a label a site sets through `_LOCAL_LANG` under
`plugin.tx_academicprojects` for every plugin of the extension, and under
`plugin.tx_academicprojects_<plugin>` for one plugin. A label set for the plugin
SHALL win over one set for the extension. Without either, the label of the
extension's language file SHALL be shown. This SHALL apply on TYPO3 v13 and v14
alike.

#### Scenario: Label set for the whole extension on TYPO3 v13

- **WHEN** a site on TYPO3 v13 sets the "Sorting field" label under
  `plugin.tx_academicprojects._LOCAL_LANG`
- **THEN** the project list shows that label, as on TYPO3 v14

#### Scenario: Label set for one plugin

- **WHEN** a site sets the "Sorting field" label under
  `plugin.tx_academicprojects_projectlist._LOCAL_LANG`
- **THEN** the project list shows that label on TYPO3 v13 and v14

#### Scenario: The plugin wins over the extension

- **WHEN** a site sets the sorting field both for the extension and for the
  plugin
- **THEN** the project list shows the label set for the plugin

#### Scenario: No override

- **WHEN** a site sets no label
- **THEN** the project list shows the label of the extension's language file

#### Scenario: The underscored path is not read

- **WHEN** a site on TYPO3 v13 sets the "Sorting field" label only under
  `plugin.tx_academic_projects._LOCAL_LANG`
- **THEN** the project list shows the label of the extension's language file

### Requirement: Labels of the project page read the overrides of the extension

Every label the page template of the project page renders SHALL read a label a
site sets through `_LOCAL_LANG` under `plugin.tx_academicprojects`, on TYPO3 v13
and v14 alike. No plugin renders the page, so no path of a plugin applies to it.

#### Scenario: Label of the project page set for the extension

- **WHEN** a site sets the "Runtime" label under
  `plugin.tx_academicprojects._LOCAL_LANG`
- **THEN** the project page shows that label on TYPO3 v13 and v14

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
