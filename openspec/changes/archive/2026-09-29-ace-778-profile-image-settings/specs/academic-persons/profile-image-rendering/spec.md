## MODIFIED Requirements

### Requirement: A profile card without an image shows the placeholder
The profile card SHALL show the placeholder image academic_persons ships for a
profile without an image, as the default value of the default placeholder
setting of the persons plugins. An integrator SHALL be able to replace the
placeholder through that setting, or switch it off by setting it to an empty
value, in which case the card shows no image as before, unless a placeholder
is configured for the profile's gender. The public profile detail SHALL show
no image for such a profile.

#### Scenario: Profile without an image
- **WHEN** a listed profile has no image
- **THEN** its card shows the neutral placeholder

#### Scenario: Integrator disables the placeholder
- **WHEN** the integrator sets the default placeholder setting to an empty
  value and configures no placeholder for the profile's gender
- **THEN** the card of a profile without an image shows no image

#### Scenario: Image field not selected for the card
- **WHEN** the content element limits the shown fields and does not include
  the profile image
- **THEN** the card shows neither the image nor the placeholder
