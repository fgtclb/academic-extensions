# academic-persons/frontend-assets Specification

## Purpose
Defines what the views of profiles, the public profile and the lists, bring to
a page besides their markup, and what they leave to the stylesheet of the
site.

## Requirements

### Requirement: The public profile brings no stylesheet
A page that carries the detail view of a profile SHALL load the script of the
view unless the integrator switches it off through the site setting or the
TypoScript constant of the same name, and SHALL NOT load a stylesheet of the
extension in any configuration. Switched off, the view SHALL render the same
markup, with the entries of the profile folded until a script of the site
opens them. The site styles the view through its classes. This SHALL apply on
TYPO3 v13 and v14.

#### Scenario: A page with the detail view
- **WHEN** a visitor opens the detail view of a profile on a site that
  configures nothing, through the site set or the static template
- **THEN** the page loads the script of the view and no stylesheet of the
  extension

#### Scenario: Script switched off in the site settings
- **WHEN** the site setting for the script of the extension is off
- **THEN** the page does not load the script of the view and renders the same
  profile, its entries folded

#### Scenario: Script switched off through the constant
- **WHEN** a site that includes the static template sets the constant for the
  script of the extension to off
- **THEN** the page does not load the script of the view and renders the same
  profile

### Requirement: The profile lists bring no stylesheet
A page that carries a list, a card, a selected profiles or a selected
contracts element of the extension SHALL NOT load a stylesheet of the
extension. The site styles the lists through their classes.

#### Scenario: A page with a profile list
- **WHEN** a visitor opens a page with the list of profiles
- **THEN** the page lists the profiles and loads no stylesheet of the
  extension
