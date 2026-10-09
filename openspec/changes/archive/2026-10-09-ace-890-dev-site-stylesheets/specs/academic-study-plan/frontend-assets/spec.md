## MODIFIED Requirements

### Requirement: The element brings its assets by default
A page that carries a study plan element SHALL load the element's script when
its switch is not configured, and SHALL NOT load a stylesheet of the element in
any configuration. A page without the element SHALL NOT load the script.

#### Scenario: Default configuration
- **WHEN** a page carries a study plan element and the site configures
  nothing
- **THEN** the page loads the study plan script and no stylesheet of the
  element

#### Scenario: Page without the element
- **WHEN** a page of the same site carries no study plan element
- **THEN** the page does not load the study plan script

#### Scenario: A leftover stylesheet setting
- **WHEN** a site still configures the stylesheet setting that earlier
  development versions of 3.0 offered
- **THEN** the page loads the study plan script and no stylesheet of the
  element, as without the setting

### Requirement: The switch works without site sets
An installation that includes the static template instead of the site set
SHALL be able to switch off the script through the TypoScript constant of the
same name as the site setting, and SHALL get no stylesheet of the element
either way.

#### Scenario: Static template installation
- **WHEN** a site includes the static template and sets the script constant
  to off
- **THEN** the page does not load the study plan script

#### Scenario: Static template without an override
- **WHEN** a site includes the static template and configures nothing
- **THEN** the page loads the study plan script and no stylesheet of the
  element

## RENAMED Requirements

- FROM: `### Requirement: The switches work without site sets`
- TO: `### Requirement: The switch works without site sets`

## REMOVED Requirements

### Requirement: The stylesheet can be switched off
**Reason**: The element ships no stylesheet any more, so there is nothing to
switch off. The site styles the element.
**Migration**: Remove the site setting or the TypoScript constant of the
stylesheet and style the element in the site package, starting from the
stylesheet of the development instances.
