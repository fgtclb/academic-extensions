## Purpose

Defines which stylesheet and script the study plan content element brings to
a page, and how an integrator switches either of them off.

## ADDED Requirements

### Requirement: The element brings its assets by default
A page that carries a study plan element SHALL load the element's stylesheet
and its script when neither switch is configured. A page without the element
SHALL load neither.

#### Scenario: Default configuration
- **WHEN** a page carries a study plan element and the site configures
  nothing
- **THEN** the page loads the study plan stylesheet and the study plan script

#### Scenario: Page without the element
- **WHEN** a page of the same site carries no study plan element
- **THEN** the page loads neither of them

### Requirement: The stylesheet can be switched off
When the integrator switches the stylesheet off, a page carrying the element
SHALL NOT load the study plan stylesheet and SHALL still load the script.

#### Scenario: Stylesheet switched off in the site settings
- **WHEN** the site setting for the stylesheet is off
- **THEN** the page loads the study plan script and not the stylesheet

### Requirement: The script can be switched off
When the integrator switches the script off, a page carrying the element
SHALL NOT load the study plan script and SHALL still render the element's
markup.

#### Scenario: Script switched off in the site settings
- **WHEN** the site setting for the script is off
- **THEN** the page contains the study plan markup but does not load the
  script

### Requirement: The switches work without site sets
An installation that includes the static template instead of the site set
SHALL be able to switch off either asset through TypoScript constants of the
same names as the site settings.

#### Scenario: Static template installation
- **WHEN** a site includes the static template and sets the stylesheet
  constant to off
- **THEN** the page does not load the study plan stylesheet
