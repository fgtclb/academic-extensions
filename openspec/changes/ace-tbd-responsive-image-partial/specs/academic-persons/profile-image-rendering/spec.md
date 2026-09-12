## Purpose

Defines how academic_persons shows the profile image in profile cards and in
the public profile detail, and what a visitor sees for a profile without an
image.

## ADDED Requirements

### Requirement: Profile images render as responsive pictures
The profile card SHALL render the profile image as a responsive picture with
the `card` preset, and the public profile detail SHALL render it with the
`detail` preset. The detail image SHALL keep the profile title and names as
its alternative text. This applies on TYPO3 v13 and v14.

#### Scenario: Profile list with an image
- **WHEN** a visitor opens a page with a profile list and a listed profile has
  an image
- **THEN** its card shows the image as a `<picture>` with webp sources

#### Scenario: Profile detail with an image
- **WHEN** a visitor opens the detail page of a profile with an image
- **THEN** the image is a `<picture>` whose fallback image carries the title
  and names of the profile as alternative text

#### Scenario: Contacts for pages
- **WHEN** a contacts-for-pages content element lists a profile with an image
- **THEN** its card shows the image exactly as the profile list does

### Requirement: A profile card without an image shows the placeholder
The profile card SHALL show the placeholder image academic_persons ships for a
profile without an image, as the default value of the default placeholder
setting of the persons plugins. An integrator SHALL be able to replace the
placeholder through that setting, or switch it off by setting it to an empty
value, in which case the card shows no image as before. The public profile
detail SHALL show no image for such a profile.

#### Scenario: Profile without an image
- **WHEN** a listed profile has no image
- **THEN** its card shows the neutral placeholder

#### Scenario: Integrator disables the placeholder
- **WHEN** the integrator sets the default placeholder setting to an empty
  value
- **THEN** the card of a profile without an image shows no image

#### Scenario: Image field not selected for the card
- **WHEN** the content element limits the shown fields and does not include
  the profile image
- **THEN** the card shows neither the image nor the placeholder
