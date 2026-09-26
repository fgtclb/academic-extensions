# academic-bite-jobs/label-overrides Specification

## Purpose
Lets an integrator change the labels the b-ite job list shows, through
TypoScript, on every supported core version.

## Requirements

### Requirement: Labels of a plugin read the overrides of the extension and of the plugin

Every label a plugin of the extension renders - labels of the templates and
partials, including the column headings of the table view - SHALL read a label a
site sets through `_LOCAL_LANG` under `plugin.tx_academicbitejobs` for every
plugin of the extension, and under `plugin.tx_academicbitejobs_<plugin>` for one
plugin. A label set for the plugin SHALL win over one set for the extension.
Without either, the label of the extension's language file SHALL be shown. This
SHALL apply on TYPO3 v13 and v14 alike.

#### Scenario: Label set for the whole extension on TYPO3 v13

- **WHEN** a site on TYPO3 v13 sets the message shown without jobs under
  `plugin.tx_academicbitejobs._LOCAL_LANG`
- **THEN** the b-ite job list shows that label, as on TYPO3 v14

#### Scenario: Label set for one plugin

- **WHEN** a site sets the message shown without jobs under
  `plugin.tx_academicbitejobs_list._LOCAL_LANG`
- **THEN** the b-ite job list shows that label on TYPO3 v13 and v14

#### Scenario: The plugin wins over the extension

- **WHEN** a site sets the empty list message both for the extension and for the
  plugin
- **THEN** the b-ite job list shows the label set for the plugin

#### Scenario: No override

- **WHEN** a site sets no label
- **THEN** the b-ite job list shows the label of the extension's language file

#### Scenario: The underscored path is not read

- **WHEN** a site on TYPO3 v13 sets the message shown without jobs only under
  `plugin.tx_academic_bite_jobs._LOCAL_LANG`
- **THEN** the b-ite job list shows the label of the extension's language file
