## MODIFIED Requirements

### Requirement: The profile editor brings no stylesheet
A page that carries the profile editing plugin SHALL NOT load a stylesheet of
the extension, neither on the list of the profiles nor in the editor. The
editor SHALL load its script unless the integrator switches it off through the
site setting or the TypoScript constant of the same name. Switched off, the
editor SHALL render the same markup and does nothing until a script of the
site drives it. The site styles the plugin through its classes. This SHALL
apply on TYPO3 v13 and v14.

#### Scenario: The list of the profiles
- **WHEN** a logged in profile owner opens the page with the profile editing
  plugin
- **THEN** the page lists the profiles and loads no stylesheet of the
  extension

#### Scenario: The editor
- **WHEN** the owner opens the editor of a profile on a site that configures
  nothing, through the site set or the static template
- **THEN** the page loads the script of the editor and no stylesheet of the
  extension

#### Scenario: The editor with the script switched off
- **WHEN** the site setting or the constant for the script of the extension is
  off and the owner opens the editor of a profile
- **THEN** the page does not load the script of the editor and renders the
  same editor markup
