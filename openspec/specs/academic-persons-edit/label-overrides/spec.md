# academic-persons-edit/label-overrides Specification

## Purpose
Lets an integrator change the labels the frontend profile editor shows, through
TypoScript, on every supported core version.

## Requirements

### Requirement: Labels of a plugin read the overrides of the extension and of the plugin

Every label a plugin of the extension renders - labels of the templates and
partials, the flash messages, and the options of the select fields, except the
words before a single year - SHALL read a label a site sets through
`_LOCAL_LANG` under `plugin.tx_academicpersonsedit` for every plugin of the
extension, and under `plugin.tx_academicpersonsedit_<plugin>` for one plugin.
A label set for the plugin SHALL win over one set for the extension. Without
either, the label of the extension's language file SHALL be shown. This SHALL
apply on TYPO3 v12 and v13 alike.

#### Scenario: Label set for the whole extension

- **WHEN** a site sets the label of a profile field under
  `plugin.tx_academicpersonsedit._LOCAL_LANG`
- **THEN** the profile editor shows that label

#### Scenario: Label set for one plugin

- **WHEN** a site sets the label of a profile field under
  `plugin.tx_academicpersonsedit_profileediting._LOCAL_LANG`
- **THEN** the profile editor shows that label on TYPO3 v12 and v13

#### Scenario: The plugin wins over the extension

- **WHEN** a site sets the field label both for the extension and for the plugin
- **THEN** the profile editor shows the label set for the plugin

#### Scenario: No override

- **WHEN** a site sets no label
- **THEN** the profile editor shows the label of the extension's language file

#### Scenario: The underscored path is not read

- **WHEN** a site sets the label of a profile field only under
  `plugin.tx_academic_persons_edit._LOCAL_LANG`
- **THEN** the profile editor shows the label of the extension's language file

### Requirement: The words before a single year read the overrides of academic_persons

The words the profile editor shows before the year of a profile information
entry that has only a start year or only an end year come from the language
file of academic_persons, and SHALL read a label a site sets through
`_LOCAL_LANG` under `plugin.tx_academicpersons`, on TYPO3 v12 and v13 alike,
and not the overrides of the profile editor. Without one, the label of that
language file SHALL be shown.

#### Scenario: "since" set for academic_persons

- **WHEN** a site sets the word shown before a start year under
  `plugin.tx_academicpersons._LOCAL_LANG`
- **THEN** the profile information list of the profile editor shows that word
