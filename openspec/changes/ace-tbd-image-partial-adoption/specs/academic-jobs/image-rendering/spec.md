## Purpose

Defines how academic_jobs renders the job image in its job list.

## ADDED Requirements

### Requirement: Job images render as responsive pictures
Job list items SHALL render the job image as a responsive picture with the
`card` preset and the free-ratio `default` crop variant, so an employer logo
is not cut. An SVG job image SHALL be passed through unprocessed. This applies
on TYPO3 v13 and v14.

#### Scenario: Job list with an image
- **WHEN** a visitor opens a job list and a listed job has an image
- **THEN** the job item shows the image as a `<picture>` with webp sources

#### Scenario: SVG employer logo as job image
- **WHEN** a listed job has an SVG file as its image
- **THEN** the job item shows the original SVG file

#### Scenario: Job without an image
- **WHEN** a listed job has no image
- **THEN** the job item shows no image, as before
