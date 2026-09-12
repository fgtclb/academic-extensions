## Purpose

Defines the crop variants an editor gets in the image cropper for the media of
program pages.

## ADDED Requirements

### Requirement: Program page media offers three crop variants
The image cropper of the page media of a program page SHALL offer the crop
variants `default` with a free ratio, `landscape` with the ratio 16:9 and
`portrait` with the ratio 3:4, in this order, on TYPO3 v13 and v14. The
default variant SHALL keep the ratios TYPO3 offers without a crop
configuration.

#### Scenario: Editor crops a program page image
- **WHEN** an editor opens the image cropper for the media of a program page
- **THEN** the cropper offers the variants default, landscape and portrait

#### Scenario: Standard page media is unchanged
- **WHEN** an editor opens the image cropper for the media of a standard page
- **THEN** the cropper offers what TYPO3 offers without this change
