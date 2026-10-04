## ADDED Requirements

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
