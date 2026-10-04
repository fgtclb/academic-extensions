# academic-base/frontend-icons Specification

## Purpose
Lets every active extension and site package register the icons a frontend
page shows, separately from the icons of the TYPO3 backend, replace an icon
of another extension, render an icon of that registry in a Fluid template
with the markup TYPO3 renders for its own icons, and hand icons of that
registry to frontend JavaScript.

## Requirements

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

### Requirement: The icon API for frontend JavaScript is a supported public API

The JSON icon map, the icon endpoint and the JavaScript icon factory SHALL be
documented as public API of `academic_base`: in the icon chapter of its
manual, on its extension points page and in a Feature changelog entry of 3.0
each. The tag name and arguments of the view helper and the JSON it renders,
the path, parameters, answers and caching of the endpoint, and the exports of
the module SHALL change only in a major release, with a Breaking changelog
entry. The PHP classes behind them SHALL NOT be public API. This applies on
TYPO3 v13 and v14.

#### Scenario: An integrator considers the API for a site package

- **WHEN** an integrator reads the frontend JavaScript section of the icon
  chapter of the `academic_base` manual
- **THEN** it describes the JSON icon map, the icon endpoint and the
  JavaScript icon factory as supported, versioned with the extension

#### Scenario: The extension points page lists the API

- **WHEN** an integrator reads the extension points page of the
  `academic_base` manual
- **THEN** it names the view helper `frontendIconMap` by tag name and
  arguments, the icon endpoint with its parameters, answer and caching, and
  the module `@fgtclb/academic-base/frontend/icons.js` with its exports
- **AND** it names none of the PHP classes behind them

#### Scenario: A change that breaks the API

- **WHEN** a release changes the JSON icon map, the icon endpoint or the
  JavaScript icon factory in a way existing code notices
- **THEN** that release is a major release, and the changelog of
  `academic_base` carries a Breaking entry with the migration

### Requirement: Only icons of the frontend registry are served to JavaScript

The JSON icon map and the icon endpoint SHALL serve the same icons with the
same markup, and only icons of the frontend icon registry: what a
`Configuration/FrontendIcons.php` or a listener registers there, the icons of
category types and category groups among them. An icon only the backend icon
registry knows, a core icon or a backend record icon, SHALL never be served,
whatever identifier a template or a request names. Of the frontend icons,
neither the `default-not-found` placeholder nor an icon whose provider does
not inline an SVG file, a bitmap, a sprite or a font icon, SHALL be served. An
identifier SHALL only be served when it consists of at most 100 lowercase
letters, digits, `_`, `.` and `-` and starts with a letter, a digit or `_`. An
identifier that is not served SHALL be left out of the answer rather than
answered with the placeholder, so a script can tell a missing icon from a
drawn one. The markup of a served icon SHALL be the inline markup a frontend
template renders for it, sanitised as far as its provider sanitises: the
`currentColor` provider of `academic_base` on TYPO3 v13 and v14, the SVG
provider of TYPO3 on TYPO3 v14 only. This applies on TYPO3 v13 and v14.

#### Scenario: A shared icon replaced by a site package

- **WHEN** a site package replaces `tx-academicbase-action-add` in its
  `Configuration/FrontendIcons.php` and a script asks for that icon
- **THEN** the answer is the inline markup of the site package's file, the
  same markup a frontend template renders inline for the identifier

#### Scenario: A backend icon is asked for

- **WHEN** a script asks for `actions-add`, an icon of the backend icon
  registry of TYPO3
- **THEN** the answer does not contain `actions-add`

#### Scenario: A bitmap icon and an unknown identifier

- **WHEN** a script asks for a frontend icon registered with a bitmap file, for
  `default-not-found` and for an identifier nothing registers
- **THEN** none of the three is in the answer
- **AND** the answer carries no markup of the not-found drawing

#### Scenario: A category type icon

- **WHEN** a script asks for the identifier of a category type icon,
  `category_types.<group>.<type>`, or of a category group icon,
  `category_types_group.<group>`
- **THEN** the answer carries its inline markup

### Requirement: A template hands icons to JavaScript as a JSON icon map

A frontend template SHALL be able to render the icons it names into a JSON
data block of the page, an element `<script type="application/json"
data-academic-icons>` whose content is a JSON object of identifier to inline
markup, in the order the template names them, each identifier once. The
browser neither executes such an element nor checks it against the Content
Security Policy. The JSON SHALL escape `<`, `>`, `&` and both quotes, so no
markup of an icon can end the element. The size SHALL default to `small`, be
one of `default`, `small`, `medium`, `large` and `mega`, and be stated on the
element as `data-academic-icons-size`. Any other size SHALL make the template
fail with an error naming the sizes. On request the element SHALL name the
icon endpoint below the base of the site language the page is rendered in as
`data-academic-icons-url`, and the current version token of the served icons
as `data-academic-icons-version`. Outside a site it SHALL name neither. This
applies on TYPO3 v13 and v14.

#### Scenario: Two shared icons on a page

- **WHEN** a template names `tx-academicbase-action-add` and
  `tx-academicbase-action-delete` for the JSON icon map
- **THEN** the page carries one JSON data block whose object holds the inline
  markup of both icons, in that order

#### Scenario: The markup of an icon inside the element

- **WHEN** the JSON icon map carries the inline SVG markup of an icon
- **THEN** the content of the element holds no `<` and no `>`, the markup is
  escaped inside the JSON strings, and the element ends only after the map

#### Scenario: The endpoint of a German page

- **WHEN** a template asks the JSON icon map to name the endpoint on a page
  of the site language with the base `https://example.com/de/`
- **THEN** the element names `https://example.com/de/_academic/icons.json`
  and the version token of the served icons

### Requirement: The icon endpoint answers icons below every site

`academic_base` SHALL answer `_academic/icons.json` below the base of every
site and site language with a JSON object of identifier to inline markup for
the icons named in the query parameter `i`, comma separated, in the order
asked for, leaving out what is not served. The query parameter `s` SHALL
choose the size, `small` by default, out of `default`, `small`, `medium`,
`large` and `mega`. A request without an identifier, with more than 32
identifiers, with an identifier that does not have the shape of a served one
or with another size SHALL be answered with `400`. A method other than `GET`
and `HEAD` SHALL be answered with `405`, and `HEAD` with the headers of `GET`
and no body. The endpoint SHALL answer before a frontend user session is
read, so it SHALL never start a session or send a cookie. A site in
maintenance mode SHALL answer it with `503`, like every page. Any other path,
and the same path outside a site, SHALL be handled as before. A page with the
slug `_academic/icons.json` can therefore no longer be reached below a site
base. This applies on TYPO3 v13 and v14.

#### Scenario: A script asks for two icons

- **WHEN** a script requests
  `https://example.com/_academic/icons.json?i=tx-academicbase-action-add,tx-academicbase-info-phone&s=medium`
- **THEN** the answer is `200` with `Content-Type: application/json` and an
  object holding the medium size inline markup of both icons, in that order

#### Scenario: A malformed request

- **WHEN** a script requests the endpoint with 33 identifiers, with
  `TX-ACADEMICBASE-ACTION-ADD` or with `s=huge`
- **THEN** the answer is `400` and names no icon

#### Scenario: A visitor with a session cookie

- **WHEN** a visitor whose browser carries a frontend session cookie requests
  the endpoint
- **THEN** the answer carries no `Set-Cookie` header

#### Scenario: A site in maintenance

- **WHEN** the site is in maintenance mode and a script requests the endpoint
- **THEN** the answer is `503` and carries no icon

### Requirement: The answer of the icon endpoint is cacheable

The answer of the icon endpoint SHALL be public. When the query parameter `v`
carries the current version token of the served icons, the answer SHALL be
cacheable for a year as immutable, with any other token or none for five
minutes. Every answer SHALL carry an `ETag` of its body, and a request with a
matching `If-None-Match` SHALL be answered with `304`. The version token SHALL
change whenever the markup of a served icon can change: with the served
identifiers and their registrations, the modification time of their source
files, the modification time of the class files of their providers and of the
classes those extend, the TYPO3 version and, in composer mode, the content of
`composer.lock`. It SHALL not change with the loading order of the packages.
This applies on TYPO3 v13 and v14.

#### Scenario: A page rendered with the current token

- **WHEN** a script requests the endpoint with the version token the JSON icon
  map of the page names
- **THEN** the answer carries `Cache-Control: public, max-age=31536000, immutable`

#### Scenario: An outdated token

- **WHEN** a script requests the endpoint with a token from before a
  deployment that changed an icon file
- **THEN** the answer carries `Cache-Control: public, max-age=300` and the
  current markup

#### Scenario: Revalidation

- **WHEN** a client repeats a request with the `ETag` of the earlier answer in
  `If-None-Match`
- **THEN** the answer is `304` without a body

### Requirement: A JavaScript icon factory serves frontend modules

`academic_base` SHALL publish the JavaScript module
`@fgtclb/academic-base/frontend/icons.js` in its import map. A module of a
package that names `academic_base` in the dependencies of its import map SHALL
be able to ask an icon factory of that module for the markup or for a new
element of an icon by its identifier and size, and to fetch several icons
ahead of time. The factory SHALL answer from the JSON icon maps of the page
first, a map added to the page later included, and SHALL ask the icon endpoint
a map names only for the rest. The icons asked for at the same time, before
the module waits for any of them, SHALL go out as one request per endpoint and
size, sorted by identifier and split into requests of at most 32 identifiers,
without cookies, so the same icons always make the same request whatever order
they were asked for in. Every icon SHALL be asked for once per page however
many factories ask for it. An identifier that does not have the shape of a
served one SHALL be rejected without a request, so it cannot fail the icons
asked for with it. An identifier the endpoint leaves out SHALL be rejected and
not asked for again, unless a JSON icon map that reaches the page later carries
it, which then answers it. The icons of a request that fails or is not
answered within ten seconds SHALL be rejected and asked for again on the next
call. This applies on TYPO3 v13 and v14.

#### Scenario: An icon the page carries

- **WHEN** the page carries a JSON icon map with `tx-academicbase-action-add`
  and a module asks the factory for that icon
- **THEN** the factory answers its markup without a request

#### Scenario: Icons the page does not carry

- **WHEN** a module asks the factory for `tx-academicbase-action-edit` and
  `tx-academicbase-action-delete` at the same time, and the page carries
  neither
- **THEN** the factory sends one request to the endpoint the JSON icon map
  names, without cookies, and answers both icons from it

#### Scenario: The same icons in another order

- **WHEN** one module asks for `tx-academicbase-action-edit` and
  `tx-academicbase-action-delete`, and a module on another page asks for the
  same two icons in the other order
- **THEN** both requests have the same URL, so the second one can be answered
  from the cache of the browser or a proxy

#### Scenario: A refused icon in a map added later

- **WHEN** the endpoint left out `example-badge`, and markup loaded into the
  page later carries a JSON icon map with `example-badge`
- **THEN** the factory answers that icon from the map the next time a module
  asks for it

#### Scenario: One malformed identifier among others

- **WHEN** a module asks for two served icons and for `Not An Icon` at the
  same time
- **THEN** the malformed identifier is rejected, the request names only the
  two served icons, and both are answered

#### Scenario: The endpoint is unreachable

- **WHEN** the request for an icon fails
- **THEN** the icon is rejected, and the next time a module asks for it the
  factory sends a new request
