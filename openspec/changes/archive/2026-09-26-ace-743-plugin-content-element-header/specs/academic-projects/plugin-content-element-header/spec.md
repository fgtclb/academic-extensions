## Purpose

Defines who renders the header of the plugins of `academic_projects`, and how
often it appears.

## ADDED Requirements

### Requirement: The plugin header renders once by default
Without configuration, the project list content elements SHALL NOT render a
header of their own; the header and the subheader an editor enters SHALL appear
exactly once, rendered by the content element layout, for every header layout
except "Hidden", which shows none. This applies to TYPO3 v13 and v14 alike.

#### Scenario: Header on a site whose layout renders it
- **WHEN** an editor sets a header with a subheader and the header layout "Layout 2", on a site using the content element layout of EXT:fluid_styled_content
- **THEN** the page shows the header once as a heading and the subheader once

#### Scenario: Hidden header
- **WHEN** the editor sets the header layout to "Hidden"
- **THEN** no heading is rendered for the content element

### Requirement: A site can let the plugins render the header
An integrator SHALL be able to switch on, per site, that the project list
plugins render the header and the subheader of their content element
themselves, above their output, for sites whose content element layout does
not render it. Switched on, the header SHALL render for every header layout
except "Hidden", including "Default". This applies to TYPO3 v13 and v14 alike.

#### Scenario: Site layout without a header
- **WHEN** the switch is on, the site's content element layout renders no header, and an editor sets a header with the header layout "Default"
- **THEN** the page shows the header once, as a heading inside the plugin output

#### Scenario: Hidden header with the switch on
- **WHEN** the switch is on and the editor sets the header layout to "Hidden"
- **THEN** no heading is rendered for the content element
