# academic-projects/project-page Specification

## Purpose
Defines what a site visitor sees on a page of the project page type, inside which
page layout it renders, and which of its sections an integrator can replace.

## Requirements

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

### Requirement: The project page renders inside the page layout of the site
A project page SHALL render inside the page layout the site package provides,
`Default` unless the integrator names another one through a setting, on a
FLUIDTEMPLATE and on a PAGEVIEW page object, on TYPO3 v13 and v14. A site
package without a layout `Default` SHALL get the project page without the
frame of the site instead of an error. A site package that registers its own
paths at the key 100 of a PAGEVIEW page object SHALL keep its layouts and
partials on project pages.

#### Scenario: Site package with a layout `Default`
- **WHEN** a visitor opens a project page on a site whose package renders the header and footer of the site in its layout `Default`
- **THEN** the project content is shown between the header and the footer of the site

#### Scenario: Integrator names another layout
- **WHEN** the integrator sets the project page layout to `Wide`, through the site setting or the constant, and the site package has that layout
- **THEN** the project page renders inside the layout `Wide`

#### Scenario: Site package without a layout `Default`
- **WHEN** a visitor opens a project page on a site whose package ships no layout `Default`
- **THEN** the page shows the project content without the frame of the site and without an error

#### Scenario: PAGEVIEW site package at the key 100
- **WHEN** a PAGEVIEW site package registers its paths at the key 100 and renders its footer through a partial of its own
- **THEN** a project page renders that footer

### Requirement: The project heading falls back to the page title
A project page SHALL show the project title as its heading, and the page
title when the project title is empty, on a FLUIDTEMPLATE and on a PAGEVIEW
page object.

#### Scenario: Project title empty on a PAGEVIEW page object
- **WHEN** a project page without project title is rendered by a PAGEVIEW page object
- **THEN** the heading shows the page title

#### Scenario: Project title filled
- **WHEN** a project page has the project title "Clean water"
- **THEN** the heading shows "Clean water" on both page object types

### Requirement: The project page shows the page subtitle
A project page SHALL show the page subtitle below the heading and above the
short description when the editor filled it, and SHALL show nothing in its
place when the field is empty, on a FLUIDTEMPLATE and on a PAGEVIEW page
object.

#### Scenario: Subtitle filled
- **WHEN** an editor fills the subtitle of a project page with "Funded until 2027"
- **THEN** the page shows "Funded until 2027" below the heading on both page object types

#### Scenario: Subtitle empty
- **WHEN** the subtitle of a project page is empty
- **THEN** the page shows no subtitle element

#### Scenario: PAGEVIEW site package with a variable `data` of its own
- **WHEN** a PAGEVIEW site package assigns a variable `data` of its own and a visitor opens a project page with a subtitle
- **THEN** the page shows the heading and the subtitle of the page, not an error

### Requirement: Integrators replace one section of the project page
The project page SHALL be composed of the sections header, media,
categories, facts and content, and an integrator SHALL be able to replace
each section alone through a partial path with a higher priority, leaving the
other sections as shipped. An integrator's own template of the whole page
SHALL keep rendering.

#### Scenario: Integrator replaces the facts section
- **WHEN** a site package provides its own project page facts section in a partial path with a higher priority
- **THEN** the project page renders the site package's facts and the shipped header, media, categories and content sections

#### Scenario: Integrator replaces the whole template
- **WHEN** a site package provides its own project page template without a layout
- **THEN** the project page renders that template

### Requirement: The project page renders its main column without the content-load set
A project page SHALL render the content elements of its main column below the
facts, in their manual order, in the language of the page, on a FLUIDTEMPLATE
and on a PAGEVIEW page object, on a site that includes no content-load set and
defines no `styles.content.getContent`. Content elements of other columns and
hidden ones SHALL NOT render. The integrator SHALL be able to change what the
content shows for project pages alone.

#### Scenario: Main column on both page object types
- **WHEN** a project page with two content elements in its main column is rendered by a FLUIDTEMPLATE or a PAGEVIEW page object
- **THEN** both content elements render after the facts, in their manual order

#### Scenario: Translated project page
- **WHEN** a visitor opens the translation of a project page
- **THEN** the translated content elements render instead of the default language ones

#### Scenario: Site on site sets only
- **WHEN** a visitor opens a project page on a site that depends on the aggregate set `fgtclb/academic-projects` or on one of its component sets
- **THEN** the page is delivered and shows the content of its main column

### Requirement: The project content-load override is no longer shipped
The projects extension SHALL NOT offer a site set or a static template that
redefines `styles.content.getContent`, and its aggregate site set and
aggregate static template SHALL NOT define that object. A site configuration
that still depends on the removed set SHALL fail, as TYPO3 fails every site
that depends on an unavailable set.

#### Scenario: Choosing a set or a static template
- **WHEN** an integrator lists the site sets or the static templates of the projects extension
- **THEN** no content-load set and no content load override template is offered

#### Scenario: Site still naming the removed set
- **WHEN** a site configuration depends on `fgtclb/academic-projects-content-load`
- **THEN** TYPO3 does not deliver the site
