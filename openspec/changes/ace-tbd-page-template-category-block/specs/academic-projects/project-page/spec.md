## Purpose

Defines what a site visitor sees on a page of the project page type, starting
with the categories assigned to that page.

## ADDED Requirements

### Requirement: Project pages list their categories by type

A page of the project page type SHALL list the categories assigned to it,
grouped by category type, with the translated label of each type followed by
the titles of its categories. A category type without an assigned category
MUST NOT produce an entry. This applies to TYPO3 v13 and v14 alike.

#### Scenario: Project page with an assigned category

- **WHEN** a visitor opens a project page that has one category of a
  registered project category type assigned
- **THEN** the page shows the label of that category type and the title of
  the category

#### Scenario: Project page without categories

- **WHEN** a visitor opens a project page that has no category assigned
- **THEN** the page shows no category list
