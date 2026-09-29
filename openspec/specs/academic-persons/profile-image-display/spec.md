# academic-persons/profile-image-display Specification

## Purpose
Defines which crop variant and which placeholder the persons content elements
render for a profile image, as an integrator configures them per site.

## Requirements

### Requirement: Integrators choose the crop variant per view
The system SHALL render the profile image with the crop variant an integrator
configured for the view that shows it: the card setting for the card content
element, the list setting for the list, the list of the list and detail
element, the selected profiles, the selected contracts and the contacts of a
page, and the detail setting for the public profile. Without a configuration
the system MUST use the crop variant `default`, which renders the same crop as
before the change. This applies on TYPO3 v13 and v14.

#### Scenario: Card configured with a square crop
- **WHEN** an integrator sets the card crop variant to `square` and a profile
  image carries a square crop area
- **THEN** the card renders the image cropped to that square area

#### Scenario: The list keeps its crop when only the card is configured
- **WHEN** an integrator sets the card crop variant to `square` and a list
  shows a profile whose image carries a square crop area
- **THEN** the list renders the image with its `default` crop

#### Scenario: List configured with a square crop
- **WHEN** an integrator sets the list crop variant to `square`
- **THEN** a list and the contacts of a page render the image cropped to its
  square area

#### Scenario: Detail configured with a portrait crop
- **WHEN** an integrator sets the detail crop variant to `portrait` and a
  profile image carries a portrait crop area
- **THEN** the public profile renders the image cropped to that area, with
  the widths it had before

#### Scenario: No crop variant configured
- **WHEN** no crop variant is configured
- **THEN** every view renders the image with its `default` crop, as before

#### Scenario: Configured crop variant does not exist
- **WHEN** the configured crop variant name is not defined for the profile
  image
- **THEN** the page renders and the image is shown without a crop

### Requirement: A gender-specific placeholder wins over the default one
The system SHALL render, for a listed profile without an image, the
placeholder configured for the profile's gender where one is configured, and
the default placeholder otherwise. The placeholder SHALL carry an empty
alternative text, because it shows nobody and the name is shown next to it.
The public profile SHALL still show no image for such a profile.

#### Scenario: Placeholder for the profile's gender
- **WHEN** a profile with gender `ms` has no image and placeholders are
  configured for `default` and `ms`
- **THEN** the `ms` placeholder is rendered

#### Scenario: No placeholder for the profile's gender
- **WHEN** a profile with gender `diverse` has no image and only a `default`
  placeholder is configured
- **THEN** the `default` placeholder is rendered

#### Scenario: Profile without a gender
- **WHEN** a profile without a gender has no image and placeholders are
  configured for `default` and `ms`
- **THEN** the `default` placeholder is rendered

#### Scenario: Default placeholder switched off
- **WHEN** the default placeholder is empty and a placeholder is configured
  for `ms`
- **THEN** a profile with gender `ms` shows the `ms` placeholder, and a
  profile with another gender shows no image

#### Scenario: Contacts of a page
- **WHEN** a contacts content element lists a profile with gender `ms`
  without an image and a placeholder is configured for `ms`
- **THEN** the contact shows the `ms` placeholder, as a list does

### Requirement: A placeholder is an extension resource
The system SHALL accept an extension resource path as placeholder value, for
the default and for every gender placeholder. A configured placeholder file
that does not exist MUST make the rendering fail with an error instead of
being left out silently.

#### Scenario: Placeholder from the site package
- **WHEN** an integrator sets a placeholder to an extension resource path of
  the site package, and a profile without an image is listed
- **THEN** the list item renders that file

#### Scenario: Placeholder file missing
- **WHEN** the configured placeholder path names a file that does not exist,
  and a profile without an image is listed
- **THEN** the rendering fails with an error
