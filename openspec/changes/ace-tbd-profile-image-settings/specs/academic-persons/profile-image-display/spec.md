## Purpose

Defines which crop, which size and which placeholder the persons plugins
render for a profile image in the list, the card and the detail view.

## ADDED Requirements

### Requirement: Integrators choose the crop variant per view
The system SHALL render the profile image of the list, the card and the detail
view with the crop variant an integrator configured for that view. Without a
configuration the system MUST use the crop variant `default`, which renders
the same crop as before the change, on TYPO3 v13 and v14.

#### Scenario: Card configured with a square crop
- **WHEN** an integrator sets the card crop variant to `square` and a profile
  image carries a square crop area
- **THEN** the card renders the image cropped to that square area

#### Scenario: No crop variant configured
- **WHEN** no crop variant is configured for the list view
- **THEN** the list renders the image with its `default` crop, as before

#### Scenario: Configured crop variant does not exist
- **WHEN** the configured crop variant name is not defined for the profile
  image
- **THEN** the page renders and the image is shown without a crop

### Requirement: Image widths follow the view's image preset
The system SHALL deliver the profile images of the list and the card in the
widths of the card image preset, and the profile image of the detail view no
wider than 1200 pixels. No site setting of the persons plugins SHALL change
these widths.

#### Scenario: Detail view without a configuration
- **WHEN** a visitor opens a profile detail page on an installation without
  image settings
- **THEN** no delivered image source is wider than 1200 pixels

#### Scenario: Card image
- **WHEN** a visitor opens a page with a card plugin showing a profile image
- **THEN** the image sources are those of the card image preset

### Requirement: A placeholder replaces a missing image
The system SHALL render a placeholder image, with the profile name as its
alternative text, where a profile has no image. Without configuration it
SHALL be the neutral placeholder shipped with the extension. When an
integrator sets the default placeholder to an empty value, the system MUST
render no image, as before.

#### Scenario: No placeholder configured
- **WHEN** a profile without an image is listed and the integrator did not
  configure a placeholder
- **THEN** the list item renders the neutral placeholder shipped with the
  extension

#### Scenario: Placeholder configured
- **WHEN** a profile without an image is listed and a default placeholder is
  configured
- **THEN** the list item renders the configured placeholder image

#### Scenario: Placeholder switched off
- **WHEN** a profile without an image is listed and the default placeholder
  is set to an empty value
- **THEN** the list item renders no image element

#### Scenario: Image not among the shown fields
- **WHEN** the card plugin's shown fields exclude the profile image
- **THEN** the card renders neither the image nor the placeholder

### Requirement: A placeholder is an extension resource
The system SHALL accept an extension resource path as placeholder value. A
configured placeholder file that does not exist MUST make the rendering fail
with an error instead of being left out silently.

#### Scenario: Placeholder from the site package
- **WHEN** an integrator sets the default placeholder to an extension
  resource path of the site package, and a profile without an image is listed
- **THEN** the list item renders that file

#### Scenario: Placeholder file missing
- **WHEN** the configured placeholder path names a file that does not exist,
  and a profile without an image is listed
- **THEN** the rendering fails with an error

### Requirement: A gender-specific placeholder wins over the default one
The system SHALL render the placeholder configured for the profile's gender
where one is configured, and the default placeholder otherwise.

#### Scenario: Placeholder for the profile's gender
- **WHEN** a profile with gender `ms` has no image and placeholders are
  configured for `default` and `ms`
- **THEN** the `ms` placeholder is rendered

#### Scenario: No placeholder for the profile's gender
- **WHEN** a profile with gender `diverse` has no image and only a `default`
  placeholder is configured
- **THEN** the `default` placeholder is rendered
