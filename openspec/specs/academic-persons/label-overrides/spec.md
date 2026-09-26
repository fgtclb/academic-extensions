# academic-persons/label-overrides Specification

## Purpose
Lets an integrator change the labels the profile lists, selections, cards and
profile details show, through TypoScript, on every supported core version.

## Requirements

### Requirement: Labels of a plugin read the overrides of the extension and of the plugin

Every label a plugin of the extension renders - labels of the templates and
partials, and labels whose key comes from a variable - SHALL read a label a
site sets through `_LOCAL_LANG` under `plugin.tx_academicpersons` for every
plugin of the extension, and under `plugin.tx_academicpersons_<plugin>` for
one plugin. A label set for the plugin SHALL win over one set for the
extension. Without either, the label of the extension's language file SHALL be
shown. This SHALL apply on TYPO3 v12 and v13 alike.

#### Scenario: Label set for the whole extension

- **WHEN** a site sets the message shown for a list without profiles under
  `plugin.tx_academicpersons._LOCAL_LANG`
- **THEN** the profile list shows that label

#### Scenario: Label set for one plugin

- **WHEN** a site sets the message shown for a list without profiles under
  `plugin.tx_academicpersons_list._LOCAL_LANG`
- **THEN** the profile list shows that label on TYPO3 v12 and v13

#### Scenario: The plugin wins over the extension

- **WHEN** a site sets the message shown for a list without profiles both for
  the extension and for the plugin
- **THEN** the profile list shows the label set for the plugin

#### Scenario: No override

- **WHEN** a site sets no label
- **THEN** the profile list shows the label of the extension's language file

#### Scenario: The underscored path is not read

- **WHEN** a site sets the message shown for a list without profiles only under
  `plugin.tx_academic_persons._LOCAL_LANG`
- **THEN** the profile list shows the label of the extension's language file
