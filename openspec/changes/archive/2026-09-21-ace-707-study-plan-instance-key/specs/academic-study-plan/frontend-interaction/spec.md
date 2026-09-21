## Purpose

Defines which study plans of a page the script drives.

## ADDED Requirements

### Requirement: Every study plan of a page is interactive
When a page renders more than one study plan, the extension SHALL provide the
category filter, the highlighting, the semester accordion and the module
dialogs for every one of them, independently of the others.

#### Scenario: The same study plan rendered twice on one page
- **WHEN** a page renders the same study plan content element twice, so that
  both carry the same identifier
- **THEN** both are interactive, and filtering in one of them leaves the other
  unchanged

#### Scenario: A template override without the identifier
- **WHEN** an installation overrides the template so that the study plan
  carries no identifier at all, and a page renders two of them
- **THEN** both are interactive
