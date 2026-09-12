## Purpose

Defines what a project page of `academic_projects` shows, on both page object
types an integrator can use, and which of its sections an integrator can
replace one by one.

## ADDED Requirements

### Requirement: The project heading falls back to the page title
A project page SHALL show the project title as its heading, and the page
title when the project title is empty. This SHALL hold for a FLUIDTEMPLATE
and a PAGEVIEW page object.

#### Scenario: Project title empty on a PAGEVIEW page object
- **WHEN** a project page without project title is rendered by a PAGEVIEW page object
- **THEN** the heading shows the page title

#### Scenario: Project title filled
- **WHEN** a project page has the project title "Clean water"
- **THEN** the heading shows "Clean water" on both page object types

### Requirement: The project page shows the page subtitle
A project page SHALL show the page subtitle below the heading when the editor
filled it, and SHALL show nothing in its place when the field is empty. This
SHALL hold for a FLUIDTEMPLATE and a PAGEVIEW page object.

#### Scenario: Subtitle filled
- **WHEN** an editor fills the subtitle of a project page with "Funded until 2027"
- **THEN** the page shows "Funded until 2027" below the heading on both page object types

#### Scenario: Subtitle empty
- **WHEN** the subtitle of a project page is empty
- **THEN** the page shows no subtitle element

### Requirement: Integrators replace one section of the project page
The project page SHALL be composed of the sections header, media,
categories, facts and content, and an integrator SHALL be able to replace
each section alone through a partial path with a higher priority, leaving the
other sections as shipped.

#### Scenario: Integrator replaces the facts section
- **WHEN** a site package provides its own project page facts section in a partial path with a higher priority
- **THEN** the project page renders the site package's facts and the shipped header, media, categories and content sections

### Requirement: The main content column renders on both page object types
The project page SHALL render the content elements of its main column with a
FLUIDTEMPLATE page object and with a PAGEVIEW page object, on TYPO3 v13 and
v14.

#### Scenario: FLUIDTEMPLATE page object
- **WHEN** a project page with two content elements in its main column is rendered by a FLUIDTEMPLATE page object
- **THEN** both content elements render after the facts

#### Scenario: PAGEVIEW page object with a main content area
- **WHEN** the same page is rendered by a PAGEVIEW page object that provides the main content area
- **THEN** both content elements render after the facts

### Requirement: The project page keeps its output
Apart from the subtitle and the heading fallback on PAGEVIEW, a project page
without integrator overrides SHALL render the same sections in the same order
as before the change.

#### Scenario: Page without subtitle and without overrides
- **WHEN** a project page with project title and without subtitle is rendered by a FLUIDTEMPLATE page object and no integrator override exists
- **THEN** the heading, short description, image, categories, facts and content render in the same order and markup as before

### Requirement: The project page renders without the content-load set
A project page SHALL render its main column content on a site that includes
only the project component sets, without any site-wide content object from
another set. This applies to TYPO3 v13 and v14 alike.

#### Scenario: Site with component sets only
- **WHEN** a visitor requests a project page with one content element in its main column on a site that includes only the project list site set
- **THEN** the page is delivered with HTTP status 200 and contains the content element

#### Scenario: Site with the aggregate set
- **WHEN** a visitor requests the same page on a site that includes the aggregate site set `fgtclb/academic-projects`
- **THEN** the page renders the same content

### Requirement: The project content load override is no longer shipped
The projects extension SHALL NOT offer a site set or a static template that
redefines `styles.content.getContent`, and its aggregate site set SHALL NOT
define that object. This applies to TYPO3 v13 and v14 alike.

#### Scenario: Choosing a set or a static template
- **WHEN** an integrator lists the site sets or the static templates of the projects extension
- **THEN** no content-load set and no content load override template is offered

#### Scenario: Aggregate set without a site definition of the object
- **WHEN** a site includes the aggregate site set `fgtclb/academic-projects` and nothing else defines `styles.content.getContent`
- **THEN** that object is undefined for the pages of the site and project pages still render their main column content
