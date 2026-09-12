## Purpose

Defines how the profile list, selected-profiles and selected-contracts plugins
of `academic_persons` render the view mode an editor chooses, and how visitors
switch between the modes an integrator allows.

## ADDED Requirements

### Requirement: The editor's default view mode is rendered

The system SHALL render the default view mode chosen in the list,
list-and-detail, selected-profiles and selected-contracts plugins. "list"
SHALL render the tile grid as before. "table" SHALL render a table with one
row per profile, or per contract in the selected-contracts plugin, and a header
row. This applies on TYPO3 v13 and v14.

#### Scenario: Editor chooses the table

- **WHEN** an editor sets the default view mode of a list plugin to "table"
  and a visitor opens the page
- **THEN** a table is shown with the columns Name, Position, E-mail, Phone and
  Room and one row per profile

#### Scenario: Existing content element

- **WHEN** a content element saved before this change has the default "list"
- **THEN** the tile grid is shown as before

### Requirement: Integrators configure the table columns

The system SHALL take the table columns and their order from a site setting.
Its default SHALL be name, position, e-mail addresses, phone numbers and room.

#### Scenario: Integrator reduces the columns

- **WHEN** the integrator sets the columns to name and e-mail addresses
- **THEN** the table has exactly these two columns in this order

### Requirement: Visitors switch between allowed modes

When the editor enables the visitor switch, the system SHALL offer one link per
allowed mode and render the mode the visitor chose. The active mode SHALL be
marked as current for assistive technology. When the switch is disabled, the
system SHALL render the default mode and ignore a requested mode.

#### Scenario: Visitor switches to the table

- **WHEN** the switch is enabled and a visitor follows the "Table" link
- **THEN** the table is shown and the "Table" link is marked as current

#### Scenario: Switch disabled

- **WHEN** the switch is disabled and the request asks for the table
- **THEN** the default mode is shown

### Requirement: Only allowed modes are rendered

The system MUST render only modes listed in the allowed modes site setting. A
requested mode outside that list SHALL fall back to the default mode, without
an error.

#### Scenario: Unknown mode as a query argument

- **WHEN** a request asks for the mode "slider" through a query argument, and
  the mode is not allowed
- **THEN** the default mode is shown and the page renders without an error

### Requirement: A chosen view mode has a speaking URL

With the shipped route enhancer of the list or list-and-detail plugin
imported, the URL of a list in a view mode other than the default SHALL
contain a view mode segment followed by the mode's name, also together with
a page number or a letter, and SHALL resolve to that list. The list in its
default mode SHALL keep its URL without a view mode segment. An allowed mode
the shipped segment does not know SHALL remain reachable through a URL with
query parameters.

#### Scenario: Speaking URL of the table

- **WHEN** a visitor follows the "Table" link of a list whose default mode is
  the tile grid
- **THEN** the URL ends with the view mode segment followed by "table"
- **AND** it resolves to the list rendered as a table

#### Scenario: Table on page 2

- **WHEN** a visitor pages through the table to page 2
- **THEN** the URL carries the view mode segment and the page segment, and
  resolves to page 2 rendered as a table

#### Scenario: Unknown mode in a speaking URL

- **WHEN** a visitor requests the view mode segment followed by a name the
  route does not know
- **THEN** the site answers with its page-not-found response

#### Scenario: Project mode unknown to the shipped segment

- **WHEN** an integrator allowed the project mode "contact" without adding it
  to the route configuration, and a visitor switches to it
- **THEN** the list is shown in that mode under a URL with query parameters

### Requirement: The chosen mode survives list navigation

The system SHALL keep the visitor's chosen mode in the pagination links and in
the letter navigation links of the same list.

#### Scenario: Visitor pages through the table

- **WHEN** a visitor chose the table and follows the link to page 2
- **THEN** page 2 is shown as a table

### Requirement: Integrators can add a view mode

The system SHALL render a mode an integrator adds without a template override.
The integrator adds an item for the default view mode through page TSconfig,
provides a partial named after the mode, and adds the mode to the allowed
modes.

#### Scenario: Project adds a contact mode

- **WHEN** an integrator adds the mode "contact" with its partial and allows
  it, and an editor chooses it as default
- **THEN** the list renders the integrator's partial for its profiles

### Requirement: The card plugin is not affected

The system SHALL keep the view mode fields hidden in the card plugin and SHALL
render the card as tiles.

#### Scenario: Card plugin

- **WHEN** an editor edits a card plugin
- **THEN** no view mode field is offered, and the card renders as before
