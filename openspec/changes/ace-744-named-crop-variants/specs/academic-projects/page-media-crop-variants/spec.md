## Purpose

Defines the crop variants an editor gets in the image cropper for the media of
project pages.

## ADDED Requirements

### Requirement: Project page media offers three crop variants
The image cropper of the page media of a project page SHALL offer the crop
variants `default` with a free ratio, `landscape` with the ratio 16:9 and
`portrait` with the ratio 3:4, in this order, on TYPO3 v13 and v14. The
default variant SHALL keep the ratios TYPO3 offers without a crop
configuration.

#### Scenario: Editor crops a project page image
- **WHEN** an editor opens the image cropper for the media of a project page
- **THEN** the cropper offers the variants default, landscape and portrait

#### Scenario: The project title limit stays
- **WHEN** an editor edits a project page after the change
- **THEN** the page title keeps its limit of 60 characters
