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
extension's language file SHALL be shown. This SHALL apply on TYPO3 v12 and v13
alike.

#### Scenario: Label set for the whole extension

- **WHEN** a site sets the "Sorting field" label under
  `plugin.tx_academicprojects._LOCAL_LANG`
- **THEN** the project list shows that label

#### Scenario: Label set for one plugin

- **WHEN** a site sets the "Sorting field" label under
  `plugin.tx_academicprojects_projectlist._LOCAL_LANG`
- **THEN** the project list shows that label on TYPO3 v12 and v13

#### Scenario: The plugin wins over the extension

- **WHEN** a site sets the sorting field both for the extension and for the
  plugin
- **THEN** the project list shows the label set for the plugin

#### Scenario: No override

- **WHEN** a site sets no label
- **THEN** the project list shows the label of the extension's language file

#### Scenario: The underscored path is not read

- **WHEN** a site sets the "Sorting field" label only under
  `plugin.tx_academic_projects._LOCAL_LANG`
- **THEN** the project list shows the label of the extension's language file

### Requirement: Labels of the project page read the overrides of the extension

Every label the page template of the project page renders SHALL read a label a
site sets through `_LOCAL_LANG` under `plugin.tx_academicprojects`, on TYPO3 v12
and v13 alike. No plugin renders the page, so no path of a plugin applies to it.

#### Scenario: Label of the project page set for the extension

- **WHEN** a site sets the "Runtime" label under
  `plugin.tx_academicprojects._LOCAL_LANG`
- **THEN** the project page shows that label on TYPO3 v12 and v13
