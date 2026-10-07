# academic-programs/program-facts Specification

## Purpose
Lets integrators decide which facts about a study program are shown on the
program page, in the program details content element and on the program card
of the list, and in which order.

## Requirements

### Requirement: Integrators configure the ordered facts list
The system SHALL offer a site setting listing the facts of the program page
and the program details content element in display order. An item SHALL be
either the identifier of a category type of the `programs` group or one of
the built-in facts `creditPoints`, `jobProfile`, `performanceScope` and
`prerequisites`. This SHALL hold on TYPO3 v13 and v14.

#### Scenario: Configured order on the program page
- **WHEN** the setting is `creditPoints,degree` and a program carries 180
  credit points, the degree "Bachelor of Science" and the location "Campus A"
- **THEN** the program page shows credit points first, then the degree, and
  no location

#### Scenario: Configured order in the details content element
- **WHEN** the setting is `creditPoints,degree` and the details content
  element renders the same program
- **THEN** it shows credit points first, then the degree, and no location

#### Scenario: Unknown identifier
- **WHEN** the setting names an identifier that is neither a registered type
  of the group nor a built-in fact
- **THEN** that item is skipped and the other facts render in order

### Requirement: An empty facts list keeps the current facts
With the facts setting empty, the program page SHALL show every category type
of the `programs` group in the category type order followed by the four
built-in facts, and the details content element SHALL show every category
type of the group in the category type order, as before.

#### Scenario: No configuration on the program page
- **WHEN** the facts setting is empty
- **THEN** the program page shows the category types of the group followed by
  credit points, job profile, performance scope and prerequisites

#### Scenario: No configuration in the details content element
- **WHEN** the facts setting is empty
- **THEN** the details content element shows the category types of the group
  and no built-in fact

### Requirement: Category type facts follow the category type order
Wherever the facts setting does not order the category types itself, the
program page and the program details content element SHALL show the category
type facts in the order the category type configuration of the `programs`
group defines, the same order every other output of category types follows.
A non-empty facts setting SHALL keep the order it states. This SHALL hold on
TYPO3 v13 and v14.

#### Scenario: Type order changed in the category type configuration
- **WHEN** the facts setting is empty
- **AND** the category type configuration orders `location` before `degree`
- **THEN** the program page and the details content element show the
  location before the degree

#### Scenario: Facts setting names the types
- **WHEN** the facts setting is `location,degree`
- **AND** the category type configuration orders `degree` before `location`
- **THEN** the program page shows the location before the degree

### Requirement: A fact without a value is omitted
A listed fact SHALL NOT be rendered when the program has no value for it.
Credit points of 0 count as no value.

#### Scenario: Program without credit points
- **WHEN** the setting lists `creditPoints` and the program has 0 credit
  points
- **THEN** no credit points fact is shown

### Requirement: Credit points are shown as a fact with an icon
The credit points fact SHALL render with its own icon and label, like a
category type fact. The icon SHALL be a frontend icon: it SHALL be shipped in
the frontend icons of the extension only, a site package SHALL replace it by
registering the same identifier in its own frontend icons
(`Configuration/FrontendIcons.php`), and a registration of that identifier in
the backend icons (`Configuration/Icons.php`) SHALL NOT change the facts. The
icon SHALL keep the wrapper markup it had before, with the identifier
`tx-academicprograms-info-credit-points`. This SHALL hold on TYPO3 v13 and
v14.

#### Scenario: Credit points fact
- **WHEN** a program with 180 credit points is shown and the setting lists
  `creditPoints`
- **THEN** the fact shows an icon, the credit points label and 180

#### Scenario: A site package replaces the credit points icon
- **WHEN** a site package registers its own drawing for
  `tx-academicprograms-info-credit-points` in its frontend icons
- **THEN** the program page, the details content element and the program
  card show that drawing for the credit points fact

#### Scenario: A replacement in the backend icons only
- **WHEN** a site package registers its own drawing for
  `tx-academicprograms-info-credit-points` in its backend icons only
- **THEN** the credit points fact keeps the drawing the extension ships

#### Scenario: The backend does not know the icon
- **WHEN** an integrator asks the backend icons of TYPO3 for
  `tx-academicprograms-info-credit-points`
- **THEN** the identifier is not registered there

#### Scenario: Site styles keep applying
- **WHEN** a site styles the icon of the credit points fact by its wrapper
  element, its `icon-tx-academicprograms-info-credit-points` class or its
  `data-identifier` attribute
- **THEN** the style applies as before

### Requirement: Integrators configure the facts of the program card
The system SHALL offer a separate site setting listing the facts of each
program card in the program list, with the same item vocabulary, defaulting
to `degree`.

#### Scenario: Default card
- **WHEN** the card setting is not changed
- **THEN** each program card shows the degree only, as before

#### Scenario: Card with two facts
- **WHEN** the card setting is `degree,standard_period`
- **THEN** each program card shows the degree and then the standard period

### Requirement: Both page integrations receive the setting
The facts setting SHALL take effect on program pages regardless of whether
the site renders pages through a FLUIDTEMPLATE or a PAGEVIEW page object.

#### Scenario: PAGEVIEW site
- **WHEN** a site renders its pages through a PAGEVIEW page object and the
  facts setting is `degree`
- **THEN** the program page shows the degree as its only fact

### Requirement: The facts replace the former categories partial
The program page and the program details content element SHALL render their
facts only through the facts partials. The former categories partial SHALL
no longer be shipped or rendered. This SHALL hold on TYPO3 v13 and v14.

#### Scenario: Site package overriding the former categories partial
- **WHEN** a site package still overrides the former categories partial of
  the programs extension
- **THEN** neither the program page nor the details content element renders
  that override
- **AND** both render their facts through the facts partials

### Requirement: Category type facts show the frontend icon of their type
Wherever the facts of a program are shown, on the program page, in the program
details content element and on the program card, a category type fact SHALL
show the frontend icon of its type: the frontend icon declared for the type in
the category type configuration when it declares one, its icon otherwise, and
a drawing a site package registers for that type in its frontend icons in
place of either. The backend SHALL keep showing the declared icon of the type.
The icon SHALL keep the wrapper markup it had before, with the identifier
`category_types.programs.<type>`. This SHALL hold on TYPO3 v13 and v14.

#### Scenario: A type with a frontend icon of its own
- **WHEN** a site package declares a type of the group `programs` with an icon
  and a separate frontend icon, and the facts of a program list that type
- **THEN** the fact shows the frontend icon
- **AND** the category type select of a category record shows the icon

#### Scenario: A type without a frontend icon
- **WHEN** a type of the group `programs` declares an icon and no frontend
  icon
- **THEN** the fact of that type shows the icon

#### Scenario: A site package replaces a shipped type icon for the frontend
- **WHEN** a site package registers its own drawing for
  `category_types.programs.degree` in its frontend icons
- **THEN** the degree fact shows that drawing on the program page, in the
  details content element and on the program card

### Requirement: Program field facts render as their field is edited

Wherever the facts of a program are shown, on the program page, in the program
details content element and on the program card, the job profile, performance
scope and prerequisites facts SHALL render their value as HTML when their field
has the rich text editor enabled for program pages, and as escaped text with
its line breaks kept when it has not. The decision SHALL follow the field
configuration of the program page type, so that a project switching the rich
text editor off or on, for the field on every page type or for the program
page type only, gets the matching output without overriding a template. The
credit points fact SHALL always render as text. This SHALL hold on TYPO3 v13
and v14.

#### Scenario: Shipped configuration

- **WHEN** the program page type keeps the shipped configuration and a
  program's prerequisites are stored as `<p>Good <strong>maths</strong></p>`
- **THEN** the prerequisites fact shows a paragraph with "maths" in bold, on
  the program page, in the details content element and on a card that lists
  `prerequisites`

#### Scenario: Rich text editor switched off for program pages only

- **WHEN** a site package switches the rich text editor of the job profile off
  for the program page type only, and a program's job profile is stored as
  `Research & teaching` and `<b>Industry</b>` on two lines
- **THEN** the job profile fact shows "Research & teaching", a line break and
  the literal text `<b>Industry</b>`
- **AND** no element of the stored text is interpreted as markup

#### Scenario: Rich text editor switched off for the field

- **WHEN** a site package switches the rich text editor of the performance
  scope field off for every page type
- **THEN** the performance scope fact of a program renders its stored text
  escaped, with its line breaks kept

#### Scenario: Rich text editor switched on again for program pages

- **WHEN** a site package switches the rich text editor of the prerequisites
  field off for every page type and on again for the program page type
- **THEN** the prerequisites fact of a program renders its stored HTML

#### Scenario: Credit points

- **WHEN** a program with 180 credit points is shown and the facts list
  `creditPoints`
- **THEN** the credit points fact shows 180 as text

### Requirement: Rich text facts are marked for the content styles

The element that holds the value of a fact SHALL carry the class
`ce-bodytext` when the fact renders rich text, so that the styles a site gives
to the body text of content elements apply to it. It SHALL NOT carry that class
for a category type fact, for the credit points fact or for a text fact whose
field has no rich text editor. This SHALL hold wherever the facts are shown,
on TYPO3 v13 and v14.

#### Scenario: Rich text fact

- **WHEN** the program page shows the job profile fact with the shipped
  configuration
- **THEN** the value of that fact carries the class `ce-bodytext`

#### Scenario: Category type and credit points facts

- **WHEN** the program page shows the degree and the credit points facts
- **THEN** neither value carries the class `ce-bodytext`

#### Scenario: Text fact without rich text editor

- **WHEN** a site package switches the rich text editor of the prerequisites
  off for the program page type
- **THEN** the value of the prerequisites fact does not carry the class
  `ce-bodytext`
