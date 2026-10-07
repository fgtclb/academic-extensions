## MODIFIED Requirements

### Requirement: The interaction relies on documented data attributes
The study plan SHALL provide the category filter, the highlighting of
modules, the opening and closing of semesters and the module dialogs for any
markup that carries the documented data attributes, whatever its class names
and element structure. A module SHALL open a dialog of its own plan whenever
its plan holds the dialog it names, also when the same plan is shown more
than once on the page or another dialog of the page carries the same id.

#### Scenario: Override with its own classes
- **WHEN** an integrator overrides the module markup with other class names
  and keeps the documented data attributes
- **THEN** filtering, highlighting, semester toggling and the module dialogs
  work, by mouse and by keyboard

#### Scenario: The same plan twice on one page
- **WHEN** an editor places a study plan on a page and shows the same plan a
  second time through an "Insert records" element
- **THEN** activating a module in either copy opens the dialog in that copy,
  by mouse and by keyboard, and closing it returns to that copy

#### Scenario: The first copy is hidden
- **WHEN** the same plan is shown twice on a page and the theme hides the first
  copy, in a tab or an accordion
- **THEN** activating a module in the visible copy opens a visible dialog, and
  closing it leaves the page usable

#### Scenario: Another dialog carries the same id
- **WHEN** another dialog of the page, before the study plan, carries the id
  of one of the plan's dialogs
- **THEN** activating that module opens the plan's own dialog

#### Scenario: Dialogs rendered outside the plan
- **WHEN** an integrator's override renders the module dialogs outside the
  plan's container
- **THEN** activating a module still opens the dialog it names

## ADDED Requirements

### Requirement: A plan inside another content element has dialog ids of its own
A study plan rendered through an "Insert records" element SHALL render dialog
ids that differ from those of the same plan elsewhere on the page. A
study plan placed on the page itself SHALL render the dialog ids it rendered
before this change.

#### Scenario: The same plan on the page and in two "Insert records" elements
- **WHEN** an editor places a study plan on a page and shows it again through
  two "Insert records" elements on the same page
- **THEN** the page holds three copies of each dialog, each with an id no
  other dialog of the page carries, and every module names the dialog of its
  own copy

#### Scenario: A plan on the page alone
- **WHEN** a study plan is placed on a page without any "Insert records"
  element
- **THEN** its dialog ids are the same as before this change

#### Scenario: A translated page
- **WHEN** the page and its "Insert records" element are translated
- **THEN** the translated page has no repeated dialog id either

#### Scenario: A template override without the new ids
- **WHEN** an integrator's override of the module or dialog part renders the
  dialog ids as before this change
- **THEN** the dialogs of every copy still open in their own copy
