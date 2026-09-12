## Purpose

Defines the crop variants an editor gets in the image cropper for the media of
partner pages, which serves as the partner logo and therefore keeps the free
default crop.

## ADDED Requirements

### Requirement: Partner page media offers only the default crop
The image cropper of the page media of a partner page SHALL offer only the
`default` variant TYPO3 offers without a crop configuration, on TYPO3 v13 and
v14.

#### Scenario: Editor crops a partner page image
- **WHEN** an editor opens the image cropper for the media of a partner page
- **THEN** the cropper offers the default variant only, with the free ratio

### Requirement: Partner logos keep the free ratio
The `default` variant of partner page media SHALL keep the free ratio, so a
logo is not forced into a fixed ratio.

#### Scenario: Logo in a partner list
- **WHEN** a partner list shows a partner logo that was never cropped
- **THEN** the logo is shown uncropped in its own ratio
