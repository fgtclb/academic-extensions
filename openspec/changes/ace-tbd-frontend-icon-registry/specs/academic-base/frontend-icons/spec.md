## Purpose

Lets every active extension and site package register the icons a frontend
page shows, separately from the icons of the TYPO3 backend, replace an icon
of another extension, and render an icon of that registry in a Fluid template
with the markup TYPO3 renders for its own icons.

## ADDED Requirements

### Requirement: An extension registers frontend icons in its own file

Every active extension and site package SHALL be able to register frontend
icons in `Configuration/FrontendIcons.php`, which returns an array of icon
identifiers to their configuration in the format of `Configuration/Icons.php`:
the icon provider and its options, such as the source file. Every icon
provider TYPO3 ships and the `currentColor` icon provider of `academic_base`
SHALL be usable. An entry without a provider SHALL get the provider TYPO3
derives from the source file: the SVG provider for a file ending in `svg`, the
bitmap provider for every other file. An entry with neither a provider nor a
source file SHALL be ignored. An entry whose provider is not an icon provider
SHALL make the frontend fail with an error that names the icon and the
requirement, instead of rendering the page without it. A file that does not
return an array SHALL be ignored. This applies on TYPO3 v13 and v14.

#### Scenario: A site package registers an icon

- **WHEN** a site package registers `site-download` with the `currentColor`
  provider and an SVG file in its `Configuration/FrontendIcons.php`, and a
  frontend template renders `site-download`
- **THEN** the page shows the SVG file inlined, marked with the identifier
  `site-download`

#### Scenario: An entry without a provider

- **WHEN** an extension registers an icon with only a source file ending in
  `.svg`
- **THEN** the icon renders as the SVG provider of TYPO3 renders that file

#### Scenario: A provider that is not one

- **WHEN** an extension registers an icon whose provider is a class that is
  not an icon provider
- **THEN** the frontend answers with an error that names that icon

### Requirement: A later package replaces an icon

The files SHALL be read in the loading order of the packages, and a package
loaded later SHALL replace the whole configuration of an identifier an earlier
package registered. A site package that depends on an extension SHALL
therefore replace that extension's frontend icons by registering the same
identifiers. This applies on TYPO3 v13 and v14.

#### Scenario: A site package replaces an icon of an extension

- **WHEN** an extension registers `example-phone` and a site package that
  depends on it registers `example-phone` with another file
- **THEN** every frontend template that renders `example-phone` shows the file
  of the site package

### Requirement: An extension contributes frontend icons from code

An extension SHALL be able to contribute frontend icons from code through an
event of `academic_base`, dispatched while the frontend icon registry is built.
An entry in a `Configuration/FrontendIcons.php` SHALL win over a contributed
icon of the same identifier, whatever the loading order of the two packages.
A contributed icon whose provider is not an icon provider SHALL be rejected
with an error that names the icon. This applies on TYPO3 v13 and v14.

#### Scenario: An icon contributed from code

- **WHEN** a listener of the event contributes `example-generated` with the
  `currentColor` provider and a frontend template renders it
- **THEN** the page shows that icon

#### Scenario: A file entry replaces a contributed icon

- **WHEN** a listener contributes `example-generated` and a site package
  registers `example-generated` in its `Configuration/FrontendIcons.php`
- **THEN** the page shows the file of the site package

### Requirement: The frontend registry and the backend registry are separate

A frontend template that renders an icon of the frontend registry SHALL see
only the icons of that registry. An icon registered only in
`Configuration/Icons.php`, or only by TYPO3 itself, SHALL be unknown to it,
and an icon registered only in `Configuration/FrontendIcons.php` SHALL be
unknown to the TYPO3 backend. An icon a site shows in both SHALL be registered
in both files. A template that keeps rendering icons of the backend registry
SHALL render exactly as before. This applies on TYPO3 v13 and v14.

#### Scenario: A backend icon in a frontend template

- **WHEN** an extension registers `example-record` only in its
  `Configuration/Icons.php`, and a frontend template renders it from the
  frontend registry
- **THEN** the page shows the not-found placeholder instead

#### Scenario: A frontend icon in the backend

- **WHEN** an extension registers `example-button` only in its
  `Configuration/FrontendIcons.php` and the TYPO3 backend renders
  `example-button`
- **THEN** the backend shows its own not-found icon

#### Scenario: Nothing has moved yet

- **WHEN** a site updates to this version and changes nothing
- **THEN** every frontend page renders its icons as before

### Requirement: A frontend icon renders like an icon of TYPO3

A Fluid template SHALL be able to render an icon of the frontend registry by
its identifier, with the options TYPO3 offers in Fluid for its own icons and
the same values and defaults: the size (default `small`), an overlay icon, the
state (default `default`), the alternative markup, for example `inline`, and a
title. The markup SHALL be the one TYPO3 renders in Fluid on the same core
version for an icon registered with the same provider and options: a `span`
with the classes `t3js-icon icon icon-size-<size> icon-state-<state>
icon-<identifier>`, the attributes `data-identifier="<identifier>"` and
`aria-hidden="true"`, an inner `span` with the class `icon-markup` holding the
markup of the provider, and the overlay icon after it. A title SHALL appear
only on the icon rendered with it, also when the same icon is rendered several
times on one page. This applies on TYPO3 v13 and v14.

#### Scenario: The same icon from both registries

- **WHEN** an icon is registered with the same provider and file in
  `Configuration/Icons.php` and in `Configuration/FrontendIcons.php`, and one
  template renders it from each registry with the same options
- **THEN** both renderings are the same string

#### Scenario: Inline markup

- **WHEN** a template renders an icon of the SVG provider with the
  alternative markup `inline`
- **THEN** the inner `span` holds the inlined SVG file rather than an image

#### Scenario: A title on one of two icons

- **WHEN** a template renders `example-phone` with the title "Call" and then
  again without a title
- **THEN** only the first carries `title="Call"`

### Requirement: An unknown identifier renders a visible placeholder

An identifier the frontend registry does not know SHALL render the
`default-not-found` drawing of TYPO3, with the identifier `default-not-found`
in its classes and its `data-identifier`, and without the identifier that was
asked for, as TYPO3 does for its own icons. The same SHALL apply to an
unknown overlay. `academic_base` SHALL register the placeholder in its own
`Configuration/FrontendIcons.php`, so a site package SHALL be able to replace
it like any other icon. This applies on TYPO3 v13 and v14.

#### Scenario: A typo in a template

- **WHEN** a frontend template renders the unregistered identifier
  `example-fone`
- **THEN** the page shows the red not-found drawing marked
  `data-identifier="default-not-found"`
- **AND** the markup does not contain `example-fone`

#### Scenario: A site package replaces the placeholder

- **WHEN** a site package registers `default-not-found` in its
  `Configuration/FrontendIcons.php` with a file of its own
- **THEN** an unknown identifier renders that file

### Requirement: The registry is cached with the system caches

The frontend icon registry SHALL be built once and kept until the system
caches are flushed or the set of active packages changes. A change to a
`Configuration/FrontendIcons.php`, or to what a listener contributes, SHALL
take effect after the system caches are flushed. Warming up the system caches
SHALL build the registry, so the first frontend request after a deployment
does not. This applies on TYPO3 v13 and v14.

#### Scenario: An integrator replaces an icon file entry

- **WHEN** an integrator changes the source of an entry in the
  `Configuration/FrontendIcons.php` of the site package and flushes the system
  caches
- **THEN** the next frontend request renders the new file

#### Scenario: Warming up the caches

- **WHEN** an integrator flushes the caches and runs `typo3 cache:warmup`
- **THEN** the frontend icon registry is built before the first frontend
  request
