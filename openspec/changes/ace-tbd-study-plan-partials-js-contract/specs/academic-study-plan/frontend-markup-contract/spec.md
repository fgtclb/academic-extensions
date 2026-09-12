## Purpose

Defines the markup the study plan script relies on, how an integrator
replaces part of the study plan markup without losing its interaction, and the
optional collapsible filter.

## ADDED Requirements

### Requirement: The interaction relies on documented data attributes
The study plan SHALL provide the category filter, the highlighting of
modules, the opening and closing of semesters and the module dialogs for any
markup that carries the documented data attributes, whatever its class names
and element structure.

#### Scenario: Override with its own classes
- **WHEN** an integrator overrides the module markup with other class names
  and keeps the documented data attributes
- **THEN** filtering, highlighting, semester toggling and the module dialogs
  work, by mouse and by keyboard

### Requirement: One part can be overridden on its own
The study plan markup SHALL be split into separately overridable parts for
the filter, a semester, a module and a module dialog, so that overriding one
part leaves the others upstream.

#### Scenario: Only the module part is overridden
- **WHEN** an integrator overrides only the module part
- **THEN** the filter, the semesters and the dialogs render from the
  extension and the interaction still works

#### Scenario: The module element itself is the dialog trigger
- **WHEN** an integrator's module override marks the module element itself as
  the dialog trigger instead of an element inside it
- **THEN** activating that module opens the dialog of the same module, and no
  other module's dialog

### Requirement: Markup of 3.0 keeps working until 4.0
Markup that identifies its parts only by the class names of version 3.0 SHALL
keep working in every 3.x release.

#### Scenario: A 3.0 template override without data attributes
- **WHEN** an installation overrides the template with the class-based markup
  of 3.0
- **THEN** filtering, highlighting, semester toggling and the dialogs work as
  before

### Requirement: The filter can be collapsed
When the integrator switches the collapsible filter on, the study plan SHALL
render the category filter collapsed behind a toggle button that states
whether it is expanded and works by keyboard. With the switch off, which is
the default, the filter SHALL render expanded as before.

#### Scenario: Collapsible filter switched on
- **WHEN** the site setting for the collapsible filter is on and a visitor
  activates the toggle with the keyboard
- **THEN** the filter expands and the toggle reports itself as expanded

#### Scenario: Default configuration
- **WHEN** the site configures nothing
- **THEN** the filter renders expanded and no toggle is shown

### Requirement: The default output is unchanged
Without an override and with the default settings, the study plan SHALL show
visitors the same content and behave the same by mouse and keyboard as before
this change.

#### Scenario: Upgrade without an override
- **WHEN** an installation without a study plan override updates
- **THEN** visitors see the same study plan and its interaction is unchanged
