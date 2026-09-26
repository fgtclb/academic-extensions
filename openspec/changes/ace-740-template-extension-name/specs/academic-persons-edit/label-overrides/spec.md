## Purpose

Lets an integrator change the labels the frontend profile editor shows, through
TypoScript, on every supported core version.

## ADDED Requirements

### Requirement: Labels of a plugin read the overrides of the extension and of the plugin

Every label a plugin of the extension renders - labels of the templates and
partials, help texts, and the field, option, section and summary labels the
editor puts together itself, except the types of contact entries - SHALL read a
label a site sets through `_LOCAL_LANG` under `plugin.tx_academicpersonsedit`
for every plugin of the extension, and under
`plugin.tx_academicpersonsedit_<plugin>` for one plugin. A label set for the
plugin SHALL win over one set for the extension. Without either, the label of
the extension's language file SHALL be shown. This SHALL apply on TYPO3 v13 and
v14 alike.

#### Scenario: Label set for the whole extension on TYPO3 v13

- **WHEN** a site on TYPO3 v13 sets the label of a profile field under
  `plugin.tx_academicpersonsedit._LOCAL_LANG`
- **THEN** the profile editor shows that label, as on TYPO3 v14

#### Scenario: Label set for one plugin

- **WHEN** a site sets the label of a profile field under
  `plugin.tx_academicpersonsedit_profileediting._LOCAL_LANG`
- **THEN** the profile editor shows that label on TYPO3 v13 and v14

#### Scenario: The plugin wins over the extension

- **WHEN** a site sets the field label both for the extension and for the plugin
- **THEN** the profile editor shows the label set for the plugin

#### Scenario: No override

- **WHEN** a site sets no label
- **THEN** the profile editor shows the label of the extension's language file

#### Scenario: The underscored path is not read

- **WHEN** a site on TYPO3 v13 sets the label of a profile field only under
  `plugin.tx_academic_persons_edit._LOCAL_LANG`
- **THEN** the profile editor shows the label of the extension's language file

#### Scenario: Literal help text of a form field

- **WHEN** a site configures the help text of a contract, contact or timeline field as
  literal text rather than as a label reference
- **THEN** the document or contact form of the profile editor opens and shows that text as
  it is, on TYPO3 v13 and v14

### Requirement: The types of contact entries read the overrides of academic_persons

The labels of the types of an address, an email address and a phone number come
from the configuration of academic_persons, and SHALL read a label a site sets
through `_LOCAL_LANG` under `plugin.tx_academicpersons`, on TYPO3 v13 and v14
alike, and not the overrides of the profile editor. Without one, the configured
label SHALL be shown.

#### Scenario: Type of an address set for academic_persons

- **WHEN** a site sets a label for the address type "Business" under
  `plugin.tx_academicpersons._LOCAL_LANG`
- **THEN** the type select of the address form of the profile editor shows that
  label
