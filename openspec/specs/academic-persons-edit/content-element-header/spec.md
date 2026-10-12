# academic-persons-edit/content-element-header Specification

## Purpose
Defines who renders the header of the profile editing content element of
`academic_persons_edit`, and how often it appears on each of its views.

## Requirements

### Requirement: The header of the profile editing plugin renders once by default
Without configuration, the profile editing content element SHALL NOT render a
header of its own on any of its views, the profile list, the profile, the
edit forms and the image form among them. The header and the subheader an
editor enters SHALL appear exactly once, rendered by the content element
layout, for every header layout except "Hidden", which shows none. The plugin
output SHALL contain no header element. This applies to TYPO3 v12 and v13
alike.

#### Scenario: Explicit header layout on a site whose layout renders the header
- **WHEN** an editor sets the header "My profile" with a subheader and the header layout "Layout 2" on a profile editing content element, on a site using the content element layout of EXT:fluid_styled_content, and a logged in frontend user opens the page
- **THEN** the page shows "My profile" once as a heading and the subheader once

#### Scenario: A view behind the profile list
- **WHEN** the same frontend user follows the link from the profile list to the view of the profile
- **THEN** that view shows the header once as well

#### Scenario: Header layout "Default"
- **WHEN** an editor sets a header with the header layout "Default"
- **THEN** the page shows the header once, and the plugin output contains no header element

#### Scenario: Hidden header
- **WHEN** an editor sets the header layout "Hidden"
- **THEN** the page shows no header

### Requirement: A site can let the profile editing plugin render the header
An integrator SHALL be able to switch on, per site, that the profile editing
plugin renders the header and the subheader of its content element itself,
above its output on every view, for sites whose content element layout does
not render it. Switched on, the header SHALL render for every header layout
except "Hidden", including "Default". This applies to TYPO3 v12 and v13
alike.

#### Scenario: Site layout without a header
- **WHEN** the switch is on, the site's content element layout renders no header, and an editor sets a header with the header layout "Default" on a profile editing content element
- **THEN** the page shows the header once, as a heading inside the plugin output

#### Scenario: Hidden header with the switch on
- **WHEN** the switch is on, the site's content element layout renders no header, and an editor sets the header layout "Hidden"
- **THEN** the page shows no header
