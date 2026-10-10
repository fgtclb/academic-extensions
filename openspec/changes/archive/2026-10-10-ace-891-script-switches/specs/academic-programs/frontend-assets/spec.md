## Purpose

Defines which scripts the program list and the program finder bring to a page,
on TYPO3 v13 and v14, and how an integrator switches them off for a site that
brings its own.

## ADDED Requirements

### Requirement: The program list and finder load their scripts unless switched off
A page that carries a program list with a sorting or a filter form SHALL load
the script that updates the list in place, and a page that carries a program
finder with a form SHALL load the script that narrows its options, unless the
integrator switches the scripts of the extension off through the site setting
or the TypoScript constant of the same name. Switched off, the page SHALL load
neither script, and both forms SHALL render the same markup and stay usable by
submitting them with their buttons. This SHALL apply on TYPO3 v13 and v14.

#### Scenario: A site that configures nothing
- **WHEN** a visitor opens a page with the program finder and a page with the
  program list on a site that configures nothing, through the site set or the
  static template
- **THEN** the finder page loads the finder script and the list page loads the
  list script

#### Scenario: Switched off in the site settings
- **WHEN** the site setting for the scripts of the extension is off
- **THEN** neither page loads a script of the extension, and the list form and
  the finder form are submitted with their buttons

#### Scenario: Switched off through the constant
- **WHEN** a site that includes the static template sets the constant for the
  scripts of the extension to off
- **THEN** neither page loads a script of the extension and both render the
  same forms
