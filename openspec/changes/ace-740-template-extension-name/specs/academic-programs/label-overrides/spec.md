## Purpose

Lets an integrator change the labels the program list, program details and
program pages show, through TypoScript, on every supported core version.

## ADDED Requirements

### Requirement: Labels of a plugin read the overrides of the extension and of the plugin

Every label a plugin of the extension renders - labels of the templates and
partials, the labels of the categories and attributes of a program, and the
options of the sorting select - SHALL read a label a site sets through
`_LOCAL_LANG` under `plugin.tx_academicprograms` for every plugin of the
extension, and under `plugin.tx_academicprograms_<plugin>` for one plugin. A
label set for the plugin SHALL win over one set for the extension. Without
either, the label of the extension's language file SHALL be shown. This SHALL
apply on TYPO3 v12 and v13 alike.

#### Scenario: Label set for the whole extension

- **WHEN** a site sets the label of the degree under
  `plugin.tx_academicprograms._LOCAL_LANG`
- **THEN** the program list shows that label

#### Scenario: Label set for one plugin

- **WHEN** a site sets the label of the degree under
  `plugin.tx_academicprograms_programlist._LOCAL_LANG`
- **THEN** the program list shows that label on TYPO3 v12 and v13

#### Scenario: The plugin wins over the extension

- **WHEN** a site sets the label of the degree both for the extension and for
  the plugin
- **THEN** the program list shows the label set for the plugin

#### Scenario: No override

- **WHEN** a site sets no label
- **THEN** the program list shows the label of the extension's language file

#### Scenario: The underscored path is not read

- **WHEN** a site sets the label of the degree only under
  `plugin.tx_academic_programs._LOCAL_LANG`
- **THEN** the program list shows the label of the extension's language file

### Requirement: Labels of the program page read the overrides of the extension

Every label the page template of the program page renders SHALL read a label a
site sets through `_LOCAL_LANG` under `plugin.tx_academicprograms`, on TYPO3
v12 and v13 alike. No plugin renders the page, so no path of a plugin applies
to it.

#### Scenario: Label of the program page set for the extension

- **WHEN** a site sets the label of a program attribute under
  `plugin.tx_academicprograms._LOCAL_LANG`
- **THEN** the program page shows that label on TYPO3 v12 and v13
