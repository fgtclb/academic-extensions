## Purpose

Defines which parts of the `academic_persons` profile item and profile list an
integrator can override individually, and which output the shipped templates
render.

## ADDED Requirements

### Requirement: The shipped item and list output stays the same

With no override in place, the system SHALL render the profile item and list
markup of the list, list-and-detail, card, selected-profiles and
selected-contracts plugins as before, keeping every existing CSS class. The
only exception is the academic title in the name. This applies on TYPO3 v13 and
v14.

#### Scenario: Default list output

- **WHEN** a visitor opens a grouped list of profiles without academic titles
- **THEN** the group headers, the items, the contract rows, the images and
  the pagination render with the same text, links and classes as before

### Requirement: The name shows the academic title

The system SHALL render the profile name in list items as the academic title
followed by first, middle and last name, and SHALL omit empty parts without
leaving extra spaces.

#### Scenario: Profile with a title

- **WHEN** a profile has the title "Prof. Dr." and the names "Anna Beispiel"
- **THEN** the item heading reads "Prof. Dr. Anna Beispiel"

### Requirement: Parts of the item can be overridden on their own

The system SHALL render the detail link, the name, the image and the contracts
of a profile item as separate partials. An integrator who overrides one of them
through the partial root paths SHALL see the override in every plugin that
renders profile items, without overriding the item itself.

#### Scenario: Override of the name partial

- **WHEN** an integrator overrides only the name partial
- **THEN** the list, list-and-detail, card, selected-profiles and
  selected-contracts plugins render the overridden name
- **AND** the rest of each item is unchanged

#### Scenario: Override reaches the contacts for pages plugin

- **WHEN** the integrator adds the same partial root path to the contacts for
  pages plugin
- **THEN** its items render the overridden name as well

### Requirement: Parts of the list can be overridden on their own

The system SHALL render the group header, the item grid, the result count and
the empty state of the profile list as separate partials. The shipped result
count SHALL render nothing.

#### Scenario: Override of the empty state

- **WHEN** an integrator overrides only the empty state partial and a list has
  no profiles
- **THEN** the overridden empty state is shown instead of "No profiles found"

#### Scenario: Result count as an override hook

- **WHEN** an integrator overrides the result count partial to print the
  number of profiles
- **THEN** the list shows that number, and without the override it shows none

### Requirement: Existing overrides of the entry partials keep working

The system MUST keep the arguments of the profile item and the list, pagination
and letter navigation partials, so that an existing override of one of them
renders as before.

#### Scenario: Project overrides the whole item

- **WHEN** a project overrides the profile item partial with its own copy
- **THEN** its copy is rendered with the same arguments as before

### Requirement: The detail page can be passed explicitly

The system SHALL let a template that renders a profile item pass the detail
page to link to. When it is passed, the passed page SHALL be used instead of the
plugin's detail page setting.

#### Scenario: Item rendered outside the persons plugins

- **WHEN** a template without persons plugin settings renders a profile item
  and passes page 42 as the detail page
- **THEN** the name links to the profile's detail view on page 42
