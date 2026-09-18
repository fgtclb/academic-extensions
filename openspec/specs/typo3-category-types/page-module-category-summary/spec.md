# typo3-category-types/page-module-category-summary Specification

## Purpose
Gives an extension that owns a page type one way to show the categories of such
a page in the page module, so the summary is implemented, labelled and
overridable in one place instead of once per extension.

## Requirements

### Requirement: An extension can add a category summary to the page module

An extension that registers a page type of its own SHALL be able to have the
page module show, above the content grid, the categories a page of that type
carries, grouped by category type. This SHALL hold on TYPO3 v12 and v13.

#### Scenario: A page of the claimed type

- **WHEN** an editor opens a page of the type the extension claims, and that
  page carries categories of the extension's category group
- **THEN** the page module lists them, grouped by category type

#### Scenario: A page of another type

- **WHEN** an editor opens a page of any other type, even one carrying
  categories of that same group
- **THEN** no summary is shown and the page module is unchanged

#### Scenario: A page the editor may not read

- **WHEN** the backend user has no read access to the page the request
  addresses
- **THEN** no summary is shown, and nothing tells them the page exists

#### Scenario: A category group no active extension registers

- **WHEN** the extension that registered the category group is not installed
- **THEN** no summary is shown and the page module still renders

### Requirement: Every type of the group is listed, with its registered title

The summary MUST list every category type registered for the group, including
the ones the page carries no category of, and MUST label each with the title
that type was registered with — including a type an integrator adds and one
whose title is a literal string rather than a label reference.

#### Scenario: A type the page has no category of

- **WHEN** a page carries no category of one of the group's types
- **THEN** that type is listed with a "not set" note

#### Scenario: A type an integrator added

- **WHEN** an integrator registers an additional type in the group and gives it
  a title that is not a label reference
- **THEN** the summary lists that type with exactly that title

#### Scenario: A hidden category

- **WHEN** a page carries a category that is switched off
- **THEN** the summary lists it in its type's row and marks it as hidden, while
  the categories that are not hidden are not marked

### Requirement: Integrators can override the summary template

The summary template SHALL be replaceable through page TSconfig, under the
package name of this extension, without replacing any core backend template.

#### Scenario: Template override

- **WHEN** an integrator registers a template override for the summary in page
  TSconfig
- **THEN** the page module renders the summary with the integrator's template,
  which receives the same rows the shipped template receives
