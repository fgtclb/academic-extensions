## Purpose

Lets an integrator change the labels the job list, job details and job form
show, through TypoScript, on every supported core version.

## ADDED Requirements

### Requirement: Labels of a plugin read the overrides of the extension and of the plugin

Every label a plugin of the extension renders - labels of the templates and
partials, labels whose key comes from a job property or form field, and the
options of the job type and employment type selects - SHALL read a label a site
sets through `_LOCAL_LANG` under `plugin.tx_academicjobs` for every plugin of
the extension, and under `plugin.tx_academicjobs_<plugin>` for one plugin. A
label set for the plugin SHALL win over one set for the extension. Without
either, the label of the extension's language file SHALL be shown. This SHALL
apply on TYPO3 v13 and v14 alike.

#### Scenario: Label set for the whole extension on TYPO3 v13

- **WHEN** a site on TYPO3 v13 sets the label of a job type option under
  `plugin.tx_academicjobs._LOCAL_LANG`
- **THEN** the job form shows that label, as on TYPO3 v14

#### Scenario: Label set for one plugin

- **WHEN** a site sets the label of a job type option under
  `plugin.tx_academicjobs_newjobform._LOCAL_LANG`
- **THEN** the job form shows that label on TYPO3 v13 and v14

#### Scenario: The plugin wins over the extension

- **WHEN** a site sets the job type option both for the extension and for the
  plugin
- **THEN** the job form shows the label set for the plugin

#### Scenario: No override

- **WHEN** a site sets no label
- **THEN** the job form shows the label of the extension's language file

#### Scenario: The underscored path is not read

- **WHEN** a site on TYPO3 v13 sets the label of a job type option only under
  `plugin.tx_academic_jobs._LOCAL_LANG`
- **THEN** the job form shows the label of the extension's language file
