# academic-partners/partner-page Specification

## Purpose
Defines what a site visitor sees on a page of the partner page type, inside which
page layout it renders, and which of its sections an integrator can replace.

## Requirements

### Requirement: Partner pages list their categories by type

A page of the partner page type SHALL list the categories assigned to it,
grouped by category type, with the translated label of each type followed by
the titles of its categories. A category type without an assigned category
MUST NOT produce an entry. This applies to TYPO3 v13 and v14 alike.

#### Scenario: Partner page with an assigned category

- **WHEN** a visitor opens a partner page that has one category of a
  registered partner category type assigned
- **THEN** the page shows the label of that category type and the title of
  the category

#### Scenario: Partner page without categories

- **WHEN** a visitor opens a partner page that has no category assigned
- **THEN** the page shows no category list

### Requirement: The partner page renders inside the page layout of the site
A partner page SHALL render inside the page layout the site package provides,
`Default` unless the integrator names another one through a setting, on a
FLUIDTEMPLATE and on a PAGEVIEW page object, on TYPO3 v13 and v14. A site
package without a layout `Default` SHALL get the partner page without the
frame of the site instead of an error. A site package that registers its own
paths at the key 100 of a PAGEVIEW page object SHALL keep its layouts and
partials on partner pages.

#### Scenario: Site package with a layout `Default`
- **WHEN** a visitor opens a partner page on a site whose package renders the header and footer of the site in its layout `Default`
- **THEN** the partner content is shown between the header and the footer of the site

#### Scenario: Integrator names another layout
- **WHEN** the integrator sets the partner page layout to `Wide`, through the site setting or the constant, and the site package has that layout
- **THEN** the partner page renders inside the layout `Wide`

#### Scenario: Site package without a layout `Default`
- **WHEN** a visitor opens a partner page on a site whose package ships no layout `Default`
- **THEN** the page shows the partner content without the frame of the site and without an error

#### Scenario: PAGEVIEW site package at the key 100
- **WHEN** a PAGEVIEW site package registers its paths at the key 100 and renders its footer through a partial of its own
- **THEN** a partner page renders that footer

### Requirement: The partner page shows the page subtitle
A partner page SHALL show the page subtitle below the heading when the editor
filled it, and SHALL show nothing in its place when the field is empty, on a
FLUIDTEMPLATE and on a PAGEVIEW page object.

#### Scenario: Subtitle filled
- **WHEN** an editor fills the subtitle of a partner page with "Partner university since 2019"
- **THEN** the page shows "Partner university since 2019" below the heading on both page object types

#### Scenario: Subtitle empty
- **WHEN** the subtitle of a partner page is empty
- **THEN** the page shows no subtitle element

#### Scenario: PAGEVIEW site package with a variable `data` of its own
- **WHEN** a PAGEVIEW site package assigns a variable `data` of its own and a visitor opens a partner page with a subtitle
- **THEN** the page shows the heading and the subtitle of the page, not an error

### Requirement: Integrators replace one section of the partner page
The partner page SHALL be composed of the sections header, media,
categories, address and content, and an integrator SHALL be able to replace
each section alone through a partial path with a higher priority, leaving the
other sections as shipped. An integrator's own template of the whole page
SHALL keep rendering.

#### Scenario: Integrator replaces the header section
- **WHEN** a site package provides its own partner page header section in a partial path with a higher priority
- **THEN** the partner page renders the site package's header and the shipped media, categories, address and content sections

#### Scenario: Integrator replaces the whole template
- **WHEN** a site package provides its own partner page template without a layout
- **THEN** the partner page renders that template

### Requirement: The partner page renders its main column without the content-load set
A partner page SHALL render the content elements of its main column below the
address, in their manual order, in the language of the page, on a FLUIDTEMPLATE
and on a PAGEVIEW page object, on a site that includes no content-load set and
defines no `styles.content.getContent`. Content elements of other columns and
hidden ones SHALL NOT render. The integrator SHALL be able to change what the
content shows for partner pages alone.

#### Scenario: Main column on both page object types
- **WHEN** a partner page with two content elements in its main column is rendered by a FLUIDTEMPLATE or a PAGEVIEW page object
- **THEN** both content elements render after the address, in their manual order

#### Scenario: Translated partner page
- **WHEN** a visitor opens the translation of a partner page
- **THEN** the translated content elements render instead of the default language ones

#### Scenario: Site on site sets only
- **WHEN** a visitor opens a partner page on a site that depends on the aggregate set `fgtclb/academic-partners` or on one of its component sets
- **THEN** the page is delivered and shows the content of its main column

### Requirement: The partner content-load override is no longer shipped
The partners extension SHALL NOT offer a site set or a static template that
redefines `styles.content.getContent`, and its aggregate site set and
aggregate static template SHALL NOT define that object. A site configuration
that still depends on the removed set SHALL fail, as TYPO3 fails every site
that depends on an unavailable set.

#### Scenario: Choosing a set or a static template
- **WHEN** an integrator lists the site sets or the static templates of the partners extension
- **THEN** no content-load set and no content load override template is offered

#### Scenario: Site still naming the removed set
- **WHEN** a site configuration depends on `fgtclb/academic-partners-content-load`
- **THEN** TYPO3 does not deliver the site

### Requirement: Category type icons are the frontend icons of the type
Wherever the partner page, the partner card of the partner list, the
partnerships list or the partnerships teaser shows a category type with its
icon, the icon SHALL be the frontend icon of that type: the frontend icon
declared for the type in the category type configuration when it declares
one, its icon otherwise, and a drawing a site package registers for that type
in its frontend icons (`Configuration/FrontendIcons.php`) in place of either.
The backend SHALL keep showing the declared icon of the type. The icon SHALL
keep the wrapper markup it had before, with the identifier
`category_types.partners.<type>`. The four shipped types SHALL show a Font
Awesome Free solid drawing, inlined and drawn in the colour of the surrounding
text, the collaboration type the shared partnership drawing of the academic
base extension. This SHALL hold on TYPO3 v13 and v14.

#### Scenario: A type with a frontend icon of its own
- **WHEN** a site package declares a type of the group `partners` with an icon
  and a separate frontend icon, and a partner carries a category of that type
- **THEN** the partner page, the partner card, the partnerships list and the
  partnerships teaser show the frontend icon for that type
- **AND** the category type select of a category record shows the icon

#### Scenario: A shipped type without a frontend icon
- **WHEN** a partner carries a category of the shipped type `partner_type` and
  no site package replaces its icon
- **THEN** the four places show the icon the extension declares for the
  partner type, inlined and in the text colour

#### Scenario: A site package replaces a shipped type icon for the frontend
- **WHEN** a site package registers its own drawing for
  `category_types.partners.region` in its frontend icons
- **THEN** the four places show that drawing for the region
- **AND** the backend keeps showing the declared icon of the region

#### Scenario: Site styles keep applying
- **WHEN** a site styles a category type icon by its wrapper element, its
  `icon-category_types.partners.<type>` class or its `data-identifier`
  attribute
- **THEN** the style applies as before
