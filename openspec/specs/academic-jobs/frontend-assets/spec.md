# academic-jobs/frontend-assets Specification

## Purpose
Defines which scripts the new job form brings to a page, on TYPO3 v13 and v14,
and how an integrator switches them off for a site that brings its own.

## Requirements

### Requirement: The new job form loads its scripts unless switched off
A page that carries the new job form SHALL load the rich text editor and the
script that configures it, unless the integrator switches the scripts of the
extension off through the site setting or the TypoScript constant of the same
name. Switched off, the page SHALL load neither, and the form SHALL render the
same markup and stay usable with plain text areas. This SHALL apply on TYPO3
v13 and v14.

#### Scenario: A site that configures nothing
- **WHEN** a visitor opens a page with the new job form on a site that
  configures nothing, through the site set or the static template
- **THEN** the page loads the rich text editor and its configuring script

#### Scenario: Switched off in the site settings
- **WHEN** the site setting for the scripts of the extension is off
- **THEN** the page loads neither script, and the description fields are
  plain text areas a visitor can fill in and submit

#### Scenario: Switched off through the constant
- **WHEN** a site that includes the static template sets the constant for the
  scripts of the extension to off
- **THEN** the page loads neither script and renders the same form
