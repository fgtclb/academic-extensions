# academic-bite-jobs/content-element-header Specification

## Purpose
Defines who renders the header of the job list of `academic_bite_jobs`, and how
often it appears.

## Requirements

### Requirement: The header of the job list renders once by default
Without configuration, the job list content element SHALL NOT render a header
of its own; the header and the subheader an editor enters SHALL appear exactly
once, rendered by the content element layout, for every header layout except
"Hidden", which shows none. The plugin output SHALL contain no header element.
This applies to TYPO3 v13 and v14 alike.

#### Scenario: Explicit header layout on a site whose layout renders the header
- **WHEN** an editor sets the header "Open positions" with a subheader and the header layout "Layout 2" on a job list content element, on a site using the content element layout of EXT:fluid_styled_content
- **THEN** the page shows "Open positions" once as a heading and the subheader once

#### Scenario: Header layout "Default"
- **WHEN** an editor sets a header with the header layout "Default"
- **THEN** the page shows the header once, and the plugin output contains no header element

#### Scenario: Hidden header
- **WHEN** an editor sets the header layout "Hidden"
- **THEN** the page shows no header

### Requirement: A site can let the job list render the header
An integrator SHALL be able to switch on, per site, that the job list renders
the header and the subheader of its content element itself, above its output,
for sites whose content element layout does not render it. Switched on, the
header SHALL render for every header layout except "Hidden", including
"Default". This applies to TYPO3 v13 and v14 alike.

#### Scenario: Site layout without a header
- **WHEN** the switch is on, the site's content element layout renders no header, and an editor sets a header with the header layout "Default" on a job list content element
- **THEN** the page shows the header once, as a heading inside the plugin output

#### Scenario: Hidden header with the switch on
- **WHEN** the switch is on, the site's content element layout renders no header, and an editor sets the header layout "Hidden"
- **THEN** the page shows no header
