## ADDED Requirements

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
