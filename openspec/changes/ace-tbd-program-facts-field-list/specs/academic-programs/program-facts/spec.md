## Purpose

Lets integrators decide which facts about a study program are shown on the
program page, in the program details content element and on the program card
of the list, and in which order.

## ADDED Requirements

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

### Requirement: An empty facts list keeps the current output
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
category type fact.

#### Scenario: Credit points fact
- **WHEN** a program with 180 credit points is shown and the setting lists
  `creditPoints`
- **THEN** the fact shows an icon, the credit points label and 180

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
