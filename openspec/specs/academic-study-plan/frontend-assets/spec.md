# academic-study-plan/frontend-assets Specification

## Purpose
Defines which script the study plan content element brings to a page, how an
integrator switches it off, and that the element brings no stylesheet.

## Requirements

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

### Requirement: The script can be switched off
When the integrator switches the script off, a page carrying the element
SHALL NOT load the study plan script and SHALL still render the element's
markup.

#### Scenario: Script switched off in the site settings
- **WHEN** the site setting for the script is off
- **THEN** the page contains the study plan markup but does not load the
  script

### Requirement: The filter shows nothing until a script builds it
The filter list item the element renders is a template rather than a control,
so a page on which no script runs SHALL show no filter button at all. This
holds whether the script was switched off, failed to load or was never
shipped, and it does not depend on the stylesheet.

#### Scenario: No script runs on the page
- **WHEN** a page carries the element and no script runs on it
- **THEN** the page shows no filter button, and in particular none carrying
  the placeholder text of the template item

#### Scenario: The shipped script runs
- **WHEN** a page carries the element and the shipped script runs
- **THEN** the page shows one filter button per category its modules carry,
  all of them visible

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
