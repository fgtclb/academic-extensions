# academic-persons/frontend-assets Specification

## Purpose
Defines what the views of profiles, the public profile and the lists, bring to
a page besides their markup, and what they leave to the stylesheet of the
site.

## Requirements

### Requirement: The public profile brings no stylesheet
A page that carries the detail view of a profile SHALL load the script of the
view and SHALL NOT load a stylesheet of the extension. The site styles the
view through its classes.

#### Scenario: A page with the detail view
- **WHEN** a visitor opens the detail view of a profile
- **THEN** the page loads the script of the view and no stylesheet of the
  extension

### Requirement: The profile lists bring no stylesheet
A page that carries a list, a card, a selected profiles or a selected
contracts element of the extension SHALL NOT load a stylesheet of the
extension. The site styles the lists through their classes.

#### Scenario: A page with a profile list
- **WHEN** a visitor opens a page with the list of profiles
- **THEN** the page lists the profiles and loads no stylesheet of the
  extension
