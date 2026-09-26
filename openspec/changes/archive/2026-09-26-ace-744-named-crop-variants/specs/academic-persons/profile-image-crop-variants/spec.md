## Purpose

Defines the crop variants an editor gets in the image cropper for the profile
image, so templates can request them by name.

## ADDED Requirements

### Requirement: The profile image offers three crop variants
The image cropper of the profile image SHALL offer the crop variants
`default` with a free ratio, `square` with the ratio 1:1 and `portrait` with
the ratio 3:4, in this order, on TYPO3 v13 and v14.

#### Scenario: Editor crops a profile image
- **WHEN** an editor opens the image cropper for a profile image
- **THEN** the cropper offers the variants default, square and portrait

### Requirement: Existing crop data keeps its meaning
The `default` variant SHALL keep the ratios TYPO3 offers without a crop
configuration, so a crop area stored before this change is shown and applied
unchanged.

#### Scenario: Profile image cropped before the change
- **WHEN** an editor opens a profile image that was cropped with the free
  default crop before the change
- **THEN** the default variant shows the stored crop area
- **AND** the frontend renders the image with that crop area
