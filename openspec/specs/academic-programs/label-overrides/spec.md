# academic-programs/label-overrides Specification

## Purpose
Lets an integrator change the labels the program list, program details and
program pages show, through TypoScript, on every supported core version.

## Requirements

### Requirement: Labels of a plugin read the overrides of the extension and of the plugin

Every label a plugin of the extension renders - labels of the templates and
partials, the labels of the program facts, and the options of the sorting
select - SHALL read a label a site sets through `_LOCAL_LANG` under
`plugin.tx_academicprograms` for every plugin of the extension, and under
`plugin.tx_academicprograms_<plugin>` for one plugin. A label set for the
plugin SHALL win over one set for the extension. Without either, the label of
the extension's language file SHALL be shown. This SHALL apply on TYPO3 v13
and v14 alike.

#### Scenario: Label set for the whole extension on TYPO3 v13

- **WHEN** a site on TYPO3 v13 sets the label of the degree fact under
  `plugin.tx_academicprograms._LOCAL_LANG`
- **THEN** the program list shows that label, as on TYPO3 v14

#### Scenario: Label set for one plugin

- **WHEN** a site sets the label of the degree fact under
  `plugin.tx_academicprograms_programlist._LOCAL_LANG`
- **THEN** the program list shows that label on TYPO3 v13 and v14

#### Scenario: The plugin wins over the extension

- **WHEN** a site sets the degree fact both for the extension and for the plugin
- **THEN** the program list shows the label set for the plugin

#### Scenario: No override

- **WHEN** a site sets no label
- **THEN** the program list shows the label of the extension's language file

#### Scenario: The underscored path is not read

- **WHEN** a site on TYPO3 v13 sets the label of the degree fact only under
  `plugin.tx_academic_programs._LOCAL_LANG`
- **THEN** the program list shows the label of the extension's language file

### Requirement: Labels of the program page read the overrides of the extension

Every label the page template of the program page renders SHALL read a label a
site sets through `_LOCAL_LANG` under `plugin.tx_academicprograms`, on TYPO3 v13
and v14 alike. No plugin renders the page, so no path of a plugin applies to it.

#### Scenario: Label of the program page set for the extension

- **WHEN** a site sets the "Back to the list" link under
  `plugin.tx_academicprograms._LOCAL_LANG`
- **THEN** the program page shows that label on TYPO3 v13 and v14

### Requirement: A category type without a label of the extension is labelled with its registered title

Wherever the program page, the program details element, the program card, the
filter of the program list or the program finder names a category type, the
system SHALL use the label `sys_category.programs.<identifier>` of the
extension when the extension or the site provides one. When neither does, the
system SHALL use the title the type is registered with, translated into the
language of the page when the title is a translation reference and shown as
written otherwise. This SHALL apply on TYPO3 v13 and v14.

#### Scenario: A project type in the facts

- **WHEN** a project registers the type `teaching_form` for the group
  `programs` with the title "Form of teaching", adds it to the facts and
  provides no label for it
- **THEN** the program page shows the fact as "Form of teaching" with the
  categories of the program

#### Scenario: A translated title

- **WHEN** the registered title of a project type is a translation reference
  with a German translation
- **THEN** the German program list labels its filter select with the German
  title

#### Scenario: A label of the site wins

- **WHEN** a site sets `sys_category.programs.teaching_form` through
  `_LOCAL_LANG` for the program list
- **THEN** the program list shows that label, not the registered title

#### Scenario: Shipped types keep their labels

- **WHEN** a list shows the shipped type `degree`
- **THEN** it is labelled as before, from the language file of the extension

#### Scenario: The finder names a project type

- **WHEN** a program finder offers a project type without a label of the
  extension
- **THEN** its select is labelled with the registered title of the type
