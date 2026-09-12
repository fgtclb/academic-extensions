## Purpose

Gives every academic extension and every project one overridable place that
renders an image responsively, with named presets, crop variants, a placeholder
and optional metadata.

## ADDED Requirements

### Requirement: The shared image partial renders a responsive picture
The shared image partial of academic_base SHALL offer the presets `card`,
`detail`, `logo` and `teaser`. Rendering it with a raster image and a
preset SHALL produce a `<picture>` element with one webp source per
breakpoint of that preset, and a fallback image in the original format that
loads lazily and states its width and height. This applies on TYPO3 v13 and
v14.

#### Scenario: Card preset for a JPEG image
- **WHEN** a template renders the partial with a JPEG image and the preset
  `card`
- **THEN** the output is a `<picture>` with webp sources for the card
  breakpoints and a lazily loading JPEG fallback image

#### Scenario: Teaser preset without an upstream consumer
- **WHEN** a project template renders the partial with a JPEG image and the
  preset `teaser`
- **THEN** the output is a `<picture>` with webp sources for the teaser
  breakpoints, although no upstream template uses that preset

#### Scenario: Integrator changes the preset widths
- **WHEN** a project overrides the shared partial through its own partial root
  path and changes the `card` breakpoints
- **THEN** every academic template that renders the `card` preset uses the
  project breakpoints

### Requirement: The requested crop variant is applied
The partial SHALL apply the crop variant it is given to every source and to
the fallback image, and SHALL use the variant `default` when none is given.

#### Scenario: Named crop variant
- **WHEN** the partial is rendered with the crop variant `portrait` for an
  image that has a `portrait` crop area
- **THEN** every source and the fallback image show that crop area

#### Scenario: Crop variant without a crop area
- **WHEN** the requested crop variant has no crop area on the image
- **THEN** the image is rendered uncropped

### Requirement: SVG images are passed through
The partial SHALL render an SVG image as a single image of the original file,
without webp sources and without processing.

#### Scenario: SVG logo
- **WHEN** the partial is rendered with an SVG image and the preset `logo`
- **THEN** the output is one image referencing the original SVG file

### Requirement: A missing image renders the placeholder or nothing
The partial SHALL render the given placeholder as an image when there is no
image, and SHALL render nothing when there is neither an image nor a
placeholder.

#### Scenario: No image with placeholder
- **WHEN** the partial is rendered without an image and with a placeholder
  path
- **THEN** the output is an image of the placeholder

#### Scenario: No image and no placeholder
- **WHEN** the partial is rendered without an image and without a placeholder
- **THEN** the output is empty

### Requirement: Caption and copyright are opt-in
The partial SHALL render the image description as a caption only when the
caption is requested. It SHALL render the copyright only when the copyright is
requested and the file carries one. A missing copyright field SHALL render
nothing and raise no error.

#### Scenario: Caption and copyright requested
- **WHEN** the caption and the copyright are requested for an image with a
  description and a copyright
- **THEN** a caption shows the description and the copyright

#### Scenario: Installation without file metadata extension
- **WHEN** the copyright is requested on an installation without the core
  file metadata extension
- **THEN** no copyright is shown and the page renders without error
