## ADDED Requirements

### Requirement: Reading the site and the language never fails
The plugin action context SHALL provide the site and the site language of the
request it was built from, and SHALL provide none, without an error, when the
request carries no site or no site language, or carries something else under
those names, on TYPO3 v12 and v13. A request outside any site, for which
TYPO3 stores a placeholder that is not a real site, counts as carrying no
site.

#### Scenario: Plugin rendered on a page of a site
- **WHEN** a listener reads the site and the language from the context of a
  plugin rendered on a page that belongs to a site
- **THEN** it receives that site and the language the page is rendered in

#### Scenario: Request outside any site
- **WHEN** a listener reads the site from a context whose request carries the
  placeholder TYPO3 uses for "no site"
- **THEN** it receives none and no error is raised

#### Scenario: Something else stored under the language's name
- **WHEN** a listener reads the language from a context whose request carries
  a value of another kind under that name
- **THEN** it receives none and no error is raised
