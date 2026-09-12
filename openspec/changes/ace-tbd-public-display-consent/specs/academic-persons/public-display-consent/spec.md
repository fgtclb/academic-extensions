## Purpose

Defines how a profile records consent to public display, and which profiles
the persons plugins show when an integrator requires that consent.

## ADDED Requirements

### Requirement: Profiles record consent to public display
Every profile SHALL carry a consent to public display that editors can switch
in the backend. It SHALL be off for new and for existing profiles, and it
SHALL apply to all languages of the profile alike. This applies on TYPO3 v13
and v14.

#### Scenario: Existing profile after the update
- **WHEN** an editor opens a profile that existed before the update
- **THEN** its consent to public display is off

#### Scenario: Translated profile
- **WHEN** an editor switches the consent on for the default language of a
  profile
- **THEN** every translation of that profile is treated as consenting

### Requirement: Plugins and sites can require consent
Every persons plugin SHALL offer an option to show only profiles with consent
to public display, and the site SHALL offer a setting that requires consent
in every persons plugin. Both SHALL be off by default. Consent SHALL be
required where either of the two is on. With both off, the plugins MUST show
the same profiles as before the update.

#### Scenario: Option off
- **WHEN** a list plugin has the option off, the site setting is off, and a
  profile has no consent
- **THEN** the profile is listed as before

#### Scenario: Site setting on
- **WHEN** the site setting is on, a list plugin has the option off, and the
  storage folder holds one consenting and one non-consenting profile
- **THEN** the list shows only the consenting profile

### Requirement: Lists and selections omit profiles without consent
Where consent is required, the list, listanddetail and card plugins SHALL
list only
consenting profiles, and their counts and pagination SHALL reflect that. The
selected profiles plugin SHALL omit selected profiles without consent, and
the selected contracts plugin SHALL omit contracts of profiles without
consent.

#### Scenario: List with one consenting profile
- **WHEN** the option is on and the storage folder holds one consenting and
  one non-consenting profile
- **THEN** the list shows only the consenting profile

#### Scenario: Selected contracts
- **WHEN** the option is on and an editor selected a contract of a profile
  without consent
- **THEN** that contract is not rendered

#### Scenario: Hidden records shown
- **WHEN** the option is on and hidden records are shown as well
- **THEN** a hidden profile without consent is still omitted

### Requirement: A detail page requires consent
Where consent is required, the detail view SHALL answer with the site's
page-not-found response for a profile without consent.

#### Scenario: Detail of a non-consenting profile
- **WHEN** the option is on and a visitor requests the detail page of a
  profile without consent
- **THEN** the site answers with its page-not-found response
