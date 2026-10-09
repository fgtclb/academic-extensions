# academic-persons-edit/frontend-assets Specification

## Purpose
Defines what the profile editor brings to a page besides its markup, and what
it leaves to the stylesheet of the site.

## Requirements

### Requirement: The profile editor brings no stylesheet
A page that carries the profile editing plugin SHALL NOT load a stylesheet of
the extension, neither on the list of the profiles nor in the editor. The site
styles the plugin through its classes.

#### Scenario: The list of the profiles
- **WHEN** a logged in profile owner opens the page with the profile editing
  plugin
- **THEN** the page lists the profiles and loads no stylesheet of the
  extension

#### Scenario: The editor
- **WHEN** the owner opens the editor of a profile
- **THEN** the page loads the script of the editor and no stylesheet of the
  extension
