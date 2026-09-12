## Purpose

Defines how a page of the program page type is embedded in the page layout of
the site, and which parts of it an integrator can replace on their own.

## ADDED Requirements

### Requirement: Program pages render inside the site layout

A program page SHALL render its program content inside the page layout the
site package provides, as the section `Main` of the layout named by the site
setting for the program page layout. The setting SHALL default to `Default`.
This applies to TYPO3 v13 and v14 alike, for `FLUIDTEMPLATE` and `PAGEVIEW`
page objects.

#### Scenario: Site package with a Default layout

- **WHEN** a visitor requests a program page of a site whose page layout
  `Default` renders a header, the section `Main` and a footer
- **THEN** the page contains the site header and footer
- **AND** the program content is rendered between them

#### Scenario: Integrator names another layout

- **WHEN** an integrator sets the program page layout setting to `Wide` and
  the site package provides a layout `Wide`
- **THEN** program pages render inside the layout `Wide`

#### Scenario: Static template installation

- **WHEN** a site uses the static templates instead of the site sets
- **THEN** the layout name is configurable through a TypoScript constant with
  the same default

### Requirement: Program page parts are replaceable one by one

A program page SHALL render its header, media, facts and content as separate
parts that an integrator can replace individually, without replacing the page
template.

#### Scenario: Integrator replaces the header

- **WHEN** an integrator provides their own program page header at a
  template path with a higher priority than the extension's
- **THEN** program pages render that header
- **AND** media, facts and content are still rendered by the extension

#### Scenario: Existing page template override

- **WHEN** a site package overrides the whole program page template
- **THEN** program pages render that override as before

### Requirement: Program pages link back to the list

When the site setting for the program list page is set, the program page
header SHALL render a link to that page. Without the setting no link SHALL be
rendered.

#### Scenario: List page configured

- **WHEN** the program list page setting names page 12
- **THEN** the program page header contains a link to page 12

#### Scenario: No list page configured

- **WHEN** the program list page setting is empty
- **THEN** the program page header contains no back link

### Requirement: Site package template paths take precedence

The template, partial and layout paths of the program page type SHALL have a
lower priority than the paths a site package registers above index 50, and
MUST NOT replace a path the site package registers at another index.

#### Scenario: PAGEVIEW site package on index 100

- **WHEN** a `PAGEVIEW` site package registers its own paths at index 100
- **THEN** program pages still resolve the site package's layouts and
  partials

#### Scenario: Integrator override between 51 and 100

- **WHEN** an integrator registers a program page partial override at index
  75
- **THEN** program pages render that override
