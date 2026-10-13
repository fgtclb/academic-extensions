# academic-persons/plugin-action-context Specification

## Purpose
Defines what an event listener can rely on from the plugin action context the
persons plugins hand to their events.

## Requirements

### Requirement: The persons context reads the site and the language without failing
The context of a persons plugin event SHALL provide the site and the site
language of the request it was built from, and SHALL provide none, without an
error, when the request carries no site or no site language, or carries
something else under those names, on TYPO3 v12 and v13.

#### Scenario: Profile page title on a page of a site
- **WHEN** a listener of the profile title placeholder event reads the
  language from the context while the detail plugin sets the page title
- **THEN** it receives the language the page is rendered in

#### Scenario: Context built outside any site
- **WHEN** the context is built from a request that carries the placeholder
  TYPO3 uses for "no site", or a value of another kind under the language's
  name
- **THEN** reading the site or the language gives none and raises no error
