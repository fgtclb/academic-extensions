## Purpose

Defines how academic_programs renders program images in its lists and on the
program page.

## ADDED Requirements

### Requirement: Program images render as responsive pictures
Program list items SHALL render their image as a responsive picture with the
`card` preset, and the program page template SHALL render its image with the
`detail` preset. This applies on TYPO3 v13 and v14.

#### Scenario: Program list
- **WHEN** a visitor opens a program list and a listed program page has an
  image
- **THEN** the program item shows the image as a `<picture>` with webp sources

#### Scenario: Program page
- **WHEN** a visitor opens a program page with an image
- **THEN** the page shows the image as a `<picture>` with the detail
  breakpoints
