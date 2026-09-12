## Purpose

Defines when a contract of `academic_persons` is shown on the public site,
depending on its "Show this contract online?" flag and on the integrator's
choice to honour that flag.

## ADDED Requirements

### Requirement: The publish flag is honoured unless the integrator opts out

The system SHALL honour the contract publish flag in the public views by
default. It SHALL offer a site setting that switches this off. While the
setting is off, the system SHALL show every contract regardless of its flag,
as before 3.0. This applies on TYPO3 v13 and v14.

#### Scenario: Setting left at its default

- **WHEN** a profile has one contract marked for online display and one that
  is not, and the integrator did not change the setting
- **THEN** the list, card, selected-profiles and detail output show only the
  contract marked for online display

#### Scenario: Setting switched off

- **WHEN** the integrator switched the setting off and a profile has one
  contract marked for online display and one that is not
- **THEN** the list, card, selected-profiles and detail output show both
  contracts

### Requirement: Unpublished contracts are left out of profile views

While the setting is on, the system MUST NOT render a contract that is not
marked for online display, nor its position, e-mail addresses, phone numbers,
addresses, location, room or office hours. This applies to the list,
list-and-detail, card and selected-profiles plugins and to the position and
contact blocks of the detail view. The profile itself SHALL still be shown.

#### Scenario: Detail view with one unpublished contract

- **WHEN** the setting is on and a visitor opens the detail view of a profile
  with a published contract and an unpublished contract
- **THEN** the contact block shows the e-mail address of the published
  contract only

#### Scenario: Profile without a published contract

- **WHEN** the setting is on and none of a profile's contracts is published
- **THEN** the list still shows the profile, without contract data

### Requirement: The selected-contracts plugin respects the flag

While the setting is on, the system MUST leave out a contract that is not
marked for online display from the selected-contracts plugin, even when an
editor selected it.

#### Scenario: Editor selected an unpublished contract

- **WHEN** the setting is on and an editor selected two contracts, one of them
  unpublished
- **THEN** the plugin renders only the published contract

### Requirement: The upgrade does not publish contracts on its own

The system MUST NOT change the publish flag of existing contracts during the
upgrade. Marking existing contracts as published SHALL be a step the
integrator takes deliberately.

#### Scenario: Upgrade without further action

- **WHEN** an installation with contracts never marked for online display is
  upgraded and the integrator takes no further step
- **THEN** those contracts keep their flag and are not shown on the public
  site
