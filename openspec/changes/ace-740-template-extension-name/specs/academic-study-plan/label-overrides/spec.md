## Purpose

Lets an integrator change the labels the study plan content element shows,
through TypoScript, on every supported core version.

## ADDED Requirements

### Requirement: Labels of the study plan read the overrides of the extension

Every label the study plan content element renders SHALL read a label a site
sets through `_LOCAL_LANG` under `plugin.tx_academicstudyplan`, and SHALL show
the label of the extension's language file without one. This SHALL apply on
TYPO3 v12 and v13 alike. The element is not a plugin, so no path of a plugin
applies to it.

#### Scenario: Label set for the extension on TYPO3 v13

- **WHEN** a site sets the label of the credit points under
  `plugin.tx_academicstudyplan._LOCAL_LANG`
- **THEN** the study plan shows that label

#### Scenario: No override

- **WHEN** a site sets no label
- **THEN** the study plan shows the label of the extension's language file

#### Scenario: The underscored path is not read

- **WHEN** a site sets the label of the credit points only under
  `plugin.tx_academic_study_plan._LOCAL_LANG`
- **THEN** the study plan shows the label of the extension's language file
