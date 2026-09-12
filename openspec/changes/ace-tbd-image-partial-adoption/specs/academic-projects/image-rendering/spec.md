## Purpose

Defines how academic_projects renders project images in its lists and on the
project page.

## ADDED Requirements

### Requirement: Project images render as responsive pictures
Project list items SHALL render their image as a responsive picture with the
`card` preset, and the project page template SHALL render its image with the
`detail` preset. This applies on TYPO3 v13 and v14.

#### Scenario: Project list
- **WHEN** a visitor opens a project list and a listed project page has an
  image
- **THEN** the project item shows the image as a `<picture>` with webp sources

#### Scenario: Project page
- **WHEN** a visitor opens a project page with an image
- **THEN** the page shows the image as a `<picture>` with the detail
  breakpoints
