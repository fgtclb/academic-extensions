## Purpose

Defines what a partner page of `academic_partners` shows, on both page object
types an integrator can use, and which of its sections an integrator can
replace one by one.

## ADDED Requirements

### Requirement: The partner page shows the page subtitle
A partner page SHALL show the page subtitle below the heading when the editor
filled it, and SHALL show nothing in its place when the field is empty. This
SHALL hold for a FLUIDTEMPLATE and a PAGEVIEW page object.

#### Scenario: Subtitle filled
- **WHEN** an editor fills the subtitle of a partner page with "Partner university since 2019"
- **THEN** the page shows "Partner university since 2019" below the heading

#### Scenario: Subtitle empty
- **WHEN** the subtitle of a partner page is empty
- **THEN** the page shows no subtitle element

#### Scenario: Subtitle on a PAGEVIEW page object
- **WHEN** the site renders pages with a PAGEVIEW page object and the subtitle is filled
- **THEN** the page shows the subtitle below the heading

### Requirement: Integrators replace one section of the partner page
The partner page SHALL be composed of the sections header, media,
categories, address and content, and an integrator SHALL be able to replace
each section alone through a partial path with a higher priority, leaving the
other sections as shipped.

#### Scenario: Integrator replaces the header section
- **WHEN** a site package provides its own partner page header section in a partial path with a higher priority
- **THEN** the partner page renders the site package's header and the shipped media, categories, address and content sections

### Requirement: The main content column renders on both page object types
The partner page SHALL render the content elements of its main column with a
FLUIDTEMPLATE page object and with a PAGEVIEW page object, on TYPO3 v13 and
v14.

#### Scenario: FLUIDTEMPLATE page object
- **WHEN** a partner page with two content elements in its main column is rendered by a FLUIDTEMPLATE page object
- **THEN** both content elements render after the address

#### Scenario: PAGEVIEW page object with a main content area
- **WHEN** the same page is rendered by a PAGEVIEW page object that provides the main content area
- **THEN** both content elements render after the address

### Requirement: The partner page keeps its output
Apart from the subtitle, a partner page without integrator overrides SHALL
render the same sections in the same order as before the change.

#### Scenario: Page without subtitle and without overrides
- **WHEN** a partner page without subtitle is rendered and no integrator override exists
- **THEN** the heading, image, categories, address and content render in the same order and markup as before

### Requirement: The partner page renders without the content-load set
A partner page SHALL render its main column content on a site that includes
only the partner component sets, without any site-wide content object from
another set. This applies to TYPO3 v13 and v14 alike.

#### Scenario: Site with component sets only
- **WHEN** a visitor requests a partner page with one content element in its main column on a site that includes only the partner list site set
- **THEN** the page is delivered with HTTP status 200 and contains the content element

#### Scenario: Site with the aggregate set
- **WHEN** a visitor requests the same page on a site that includes the aggregate site set `fgtclb/academic-partners`
- **THEN** the page renders the same content

### Requirement: The partner content load override is no longer shipped
The partners extension SHALL NOT offer a site set or a static template that
redefines `styles.content.getContent`, and its aggregate site set SHALL NOT
define that object. This applies to TYPO3 v13 and v14 alike.

#### Scenario: Choosing a set or a static template
- **WHEN** an integrator lists the site sets or the static templates of the partners extension
- **THEN** no content-load set and no content load override template is offered

#### Scenario: Aggregate set without a site definition of the object
- **WHEN** a site includes the aggregate site set `fgtclb/academic-partners` and nothing else defines `styles.content.getContent`
- **THEN** that object is undefined for the pages of the site and partner pages still render their main column content
