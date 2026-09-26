## Purpose

Lets an integrator change the labels the partner list, map, partnership elements
and partner pages show, through TypoScript, on every supported core version.

## ADDED Requirements

### Requirement: Labels of a plugin read the overrides of the extension and of the plugin

Every label a plugin of the extension renders - labels of the templates and
partials, category type labels, the options of the sorting select and the
country of a partner's address - SHALL read a label a site sets through
`_LOCAL_LANG` under `plugin.tx_academicpartners` for every plugin of the
extension, and under `plugin.tx_academicpartners_<plugin>` for one plugin. A
label set for the plugin SHALL win over one set for the extension. Without
either, the label of the extension's language file SHALL be shown. This SHALL
apply on TYPO3 v12 and v13 alike.

#### Scenario: Label set for the whole extension

- **WHEN** a site sets the "Sorting field" label under
  `plugin.tx_academicpartners._LOCAL_LANG`
- **THEN** the partner list shows that label

#### Scenario: Label set for one plugin

- **WHEN** a site sets the "Sorting field" label under
  `plugin.tx_academicpartners_list._LOCAL_LANG`
- **THEN** the partner list shows that label on TYPO3 v12 and v13

#### Scenario: The plugin wins over the extension

- **WHEN** a site sets the sorting field both for the extension and for the
  plugin
- **THEN** the partner list shows the label set for the plugin

#### Scenario: No override

- **WHEN** a site sets no label
- **THEN** the partner list shows the label of the extension's language file

#### Scenario: The underscored path is not read

- **WHEN** a site sets the "Sorting field" label only under
  `plugin.tx_academic_partners._LOCAL_LANG`
- **THEN** the partner list shows the label of the extension's language file

### Requirement: Labels of the partner page read the overrides of the extension

Every label the page template of the partner page renders SHALL read a label a
site sets through `_LOCAL_LANG` under `plugin.tx_academicpartners`, on TYPO3 v12
and v13 alike. No plugin renders the page, so no path of a plugin applies to it.

#### Scenario: Label of the partner page set for the extension

- **WHEN** a site sets the "Address" label under
  `plugin.tx_academicpartners._LOCAL_LANG`
- **THEN** the partner page shows that label on TYPO3 v12 and v13
