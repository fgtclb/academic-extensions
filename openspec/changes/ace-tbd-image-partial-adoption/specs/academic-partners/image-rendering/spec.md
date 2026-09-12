## Purpose

Defines how academic_partners renders partner and partnership images in its
lists and on the partner page.

## ADDED Requirements

### Requirement: Partner page images render as responsive pictures
The partner page template SHALL render its image as a responsive picture with
the `detail` preset. This applies on TYPO3 v13 and v14.

#### Scenario: Partner page
- **WHEN** a visitor opens a partner page with an image
- **THEN** the page shows the image as a `<picture>` with the detail
  breakpoints

### Requirement: Partner logos stay uncropped
Partner list items and partnership list and teaser items SHALL render the
partner image, which is the partner logo, with the `logo` preset. It applies
no crop and passes an SVG file through unprocessed, on TYPO3 v13 and v14.

#### Scenario: Partner list
- **WHEN** a visitor opens a partner list whose partner page has a raster
  logo
- **THEN** the partner item shows the logo uncropped, in its own ratio

#### Scenario: SVG logo in a partner list
- **WHEN** a partner list shows a partner whose image is an SVG file
- **THEN** the item shows the original SVG file

#### Scenario: SVG logo in a partnership list
- **WHEN** a partnership list shows a partner whose image is an SVG file
- **THEN** the item shows the original SVG file
