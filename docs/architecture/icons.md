# Icons

What an icon of the academic extensions is called, where its file lives, where
it comes from, how it is registered and rendered, and how the set is checked.
Where a count is quoted with a command next to it, that count is the output of
the command, run over the repository at the commit that last touched this page
— re-run it rather than adjusting the number by hand.

## The rules in short

- Identifiers are `tx-<extension key without underscores>-<group>-<name>`,
  files `Resources/Public/Icons/<group>/<name>.svg`, unless it shares the file
  of another identifier.
- An icon that means the same in several extensions — an action, a state, the
  glyph in front of a piece of information — exists once, in `academic_base`.
  Look there before adding one.
- Every icon is a **Font Awesome Free solid** icon in the house format. Never
  Font Awesome Pro, never another set.
- Every icon is registered with `CurrentColorSvgIconProvider`. A category type
  asks for it with `inlineIcon: true`.
- A renamed identifier is renamed, not aliased: 3.0 ships no deprecated
  identifiers, and the extension documents old → new in one `Breaking-*.rst`.
- A frontend template renders an icon with
  `<core:icon identifier="…" alternativeMarkupIdentifier="inline" />`.
- An icon is `1em` × `1em`, everywhere. No stylesheet enlarges an icon box to
  make up for the glyph being smaller than its box.
- A content element names one identifier in its CType item, its
  `typeicon_classes` entry and its wizard entry.
- The backend JavaScript icon API does not work in the frontend. Frontend
  TypeScript takes markup the server rendered: a `<template>` it clones, the
  JSON icon map of `<ab:frontendIconMap>`, or the icon endpoint
  `<site base>/_academic/icons.json`, the last two through the icon factory
  `@fgtclb/academic-base/frontend/icons.js` and all three internal and
  experimental — see [JavaScript](#javascript).

## Identifiers

### The scheme

`tx-<extension key without underscores>-<group>-<name>`, everything lowercase,
the name in kebab case: `tx-academicbase-action-move-up`,
`tx-academicpersonsedit-plugin-profile-editing`. The groups:

| Group     | For                                                | Lives in                       |
|-----------|----------------------------------------------------|--------------------------------|
| `action`  | something a control does: add, edit, move up, save | `academic_base`                |
| `state`   | a state a control shows: visible, hidden           | `academic_base`                |
| `info`    | the glyph in front of a piece of information       | `academic_base`                |
| `record`  | the icon of a TCA record type (`typeicon_classes`) | the extension of the table     |
| `plugin`  | a content element: the CType and its wizard entry  | the extension of the plugin    |
| `doktype` | a page type                                        | the extension of the page type |

**Why this shape.** Every part of it answers a property of the icon registry
that holds on both core versions:

- **A duplicate registration wins silently.** Every package's
  `Configuration/Icons.php` is merged into one array with `array_merge()` in
  `AbstractServiceProvider::configureIcons()`, in the order of the active
  packages, and `IconRegistry::registerIcon()` assigns without looking. Nothing
  throws and nothing is logged, and an `Icons.php` entry overrides even a core
  identifier. The extension key is the only token that is unique across an
  installation, so it is in every identifier.
- **The key is written without underscores**, the way core derives the
  `tx_<key>` prefix of table names (`ExtensionManagementUtility::getCN()`).
  Written dashed it would be ambiguous in this family: `academic_persons` with
  `edit-print` and `academic_persons_edit` with `print` would both read
  `academic-persons-edit-print`. Without underscores the key is exactly the
  second segment.
- **`tx-` keeps us out of core's namespace.** Core names its icons
  `<category>-<name>` — `actions-`, `content-`, `apps-` and so on — and adds new
  ones with every release, so an `actions-*` name that is free today may be
  taken tomorrow. `tx-` is not a core category.
- **The identifier becomes markup.** `Icon::wrappedIcon()` emits it as the CSS
  class `icon-<identifier>` and as `data-identifier`. `[a-z0-9-]` gives a class
  a selector can name without escaping — no dots, no underscores, no camel case
  — and the group keeps an `info-contract` apart from a `record-contract` of
  the same name.

### Category type identifiers

`category_types.<group>.<type>` is derived by `EXT:category_types`
(`CategoryType::getIconIdentifier()`) from the `Configuration/CategoryTypes.yaml`
of whichever extension declares the type. It stays as it is. What the scheme
governs there is the file the `icon:` key points at, which follows the file
layout below.

### Renames

The consolidation renamed every identifier the extensions registered through
`Configuration/Icons.php`, and registers none of the old ones as deprecated. The registry could:
an `Icons.php` entry takes a `deprecated` key (Feature #98130, since 12.0), and
rendering such an identifier raises `E_USER_DEPRECATED`. It was left out on
purpose, because it does not carry over what matters. A template of ours emits
the new identifier either way, so a project's `.icon-<old>` selector and its
override of an old identifier in its own `Icons.php` stop working at the same
moment, deprecation or not. What an integrator has to change is in the
`Breaking-*.rst` changelog entry of each extension.

## Files

### Layout

`Resources/Public/Icons/<group>/<name>.svg`, lowercase kebab case. The groups of
the identifiers are directories; so are `category-type` and `category-group`
for the files a `CategoryTypes.yaml` names. Next to them:

- `Extension.svg` — the extension manager and TER icon, read as a file, never
  registered. It is kept in every extension.
- `LICENSE-font-awesome.txt` — the attribution notice, see [Licence](#licence).
- `BackendLayout.png` (partners, programs, projects) — the backend layout
  preview, not an icon.

Several identifiers may share one file, and that includes files of another
extension: every package in `packages/fgtclb/` requires `fgtclb/academic-base`,
so an `EXT:academic_base/Resources/Public/Icons/…` source always resolves. A
record icon that is the same glyph as a shared info icon points at the shared
file rather than copying it.

### Where an icon belongs

The shared set in `academic_base` is the default. An action, a state or an
information glyph goes there, even when only one extension uses it today —
the second one will, and a copy in each extension is how the set drifted apart
before 3.0. What only makes sense for one extension — its record types, its
plugins, its page types and its category types — belongs to that extension.

The shared set registers every glyph it ships, also where nothing of ours names
the identifier. `tx-academicbase-info-department`, `-info-information`,
`-info-partnership` and `-info-role` are named by no template, TCA or TSconfig
of the academic extensions; they stay registered on purpose, as part of the
public API of the set: a project can render them and override them. Their files
are also the drawings of record and category type icons of other extensions,
which register identifiers of their own for them — an override of the shared
identifier therefore does not reach those.

The shared set is listed with the meaning of every icon in the
[`academic_base` manual](../../packages/fgtclb/academic-base/Documentation/Icons/Index.rst).

### Font Awesome Free solid, and nothing else

Every icon comes from `@fortawesome/fontawesome-free`, the **solid** style of
the fixed width set `svgs-full/solid/`. The shipped files are from version
7.3.1. One source, one style and one grid keep a row of icons in one visual
language: regular exists in Free for a small subset only, and mixing it with
solid, or an outline set with a filled one, is what the icons looked like
before 3.0. The fixed width grid (`viewBox="0 0 640 640"` for every icon) keeps
icons of different natural widths at one scale, where a natural width viewBox
would draw a narrow glyph larger than a wide one in the same square box.

Font Awesome **Pro** is never used: it is a commercial licence and does not
cover shipping its files in a package anyone can download. Neither is a brand
icon of the Free set, which is a trademark of its owner and only to be used
for the brand it represents.

### The house format

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" width="1em" height="1em" fill="currentColor"><!--! Font Awesome Free 7.3.1 by @fontawesome … --><path d="…"/></svg>
```

- `viewBox="0 0 640 640"`, as shipped in `svgs-full/`;
- `width="1em" height="1em"`, so the icon follows the font size in the
  frontend — see [Sizing](#sizing);
- `fill="currentColor"` on the root element, so the icon takes the colour of
  the text around it; the paths carry `d` only;
- no `id`, no `class`, no `style`, no `<style>`: the markup is inlined, maybe
  many times in one document, where an `id` must be unique and a style rule is
  global;
- Font Awesome's attribution comment stays in the file. It never reaches the
  page — see [the provider](#a-comment-does-not-reach-the-markup) — but it
  keeps the attribution attached to a file copied out of the package, and Font
  Awesome asks for it to be kept.

### Adding an icon

1. Look for it in the shared set first.
2. Pick the icon on fontawesome.com (Free, solid) and take its file from the
   package, which is on npm:
   <https://registry.npmjs.org/@fortawesome/fontawesome-free/-/fontawesome-free-7.3.1.tgz>,
   `package/svgs-full/solid/<name>.svg`. Use the canonical name, not one of
   the aliases of older versions that the package ships as duplicate files.
3. Normalise it into the house format. Run from the unpacked tarball, this
   produced the 36 shared files byte for byte:

   ```bash
   { sed -e 's#^<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">#<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" width="1em" height="1em" fill="currentColor">#' \
         -e 's#<path fill="currentColor" #<path #g' \
         package/svgs-full/solid/plus.svg; echo; } \
     > packages/fgtclb/academic-base/Resources/Public/Icons/action/add.svg
   ```

4. List the file with its Font Awesome name in the extension's
   `Resources/Public/Icons/LICENSE-font-awesome.txt`, creating the notice from
   the one in `academic_base` if the extension has none yet.
5. Register it — see [Registration](#registration) — and add the identifier to
   the extension's icon test.

### Licence

Font Awesome Free icons are licensed CC BY 4.0, which requires attribution
wherever the icons are shared: creator, copyright notice, licence and its URI
with its disclaimer of warranties, a link to the material, and an indication of
what was modified. The comment in
each file does not do that on a rendered page, because the sanitiser removes
it. Every extension that ships Font Awesome files therefore carries
`Resources/Public/Icons/LICENSE-font-awesome.txt`: version, creator, copyright,
licence URI, the modifications (the `fill` hoisted to the root element,
`width`/`height` added, the file renamed) and every file it covers with its Font
Awesome name. It is one notice per extension rather than one for the
repository because every package is split out into a repository of its own and
is released on its own; a notice at the root of this repository would ship with
none of them. An extension that only points at files of `academic_base` ships no
Font Awesome file and needs no notice.

Every extension that ships Font Awesome files names them in a section
"Third-party icons" of its manual, in the same wording, and its `README.md`
points at the notice in its licence section — the manual is where an
integrator looks, the README is what the split repository shows.

## Registration

### Always `CurrentColorSvgIconProvider`

Every icon is registered in the extension's `Configuration/Icons.php` with
`FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider`, one
explicit entry per identifier:

```php
'tx-academicbase-action-add' => [
    'provider' => CurrentColorSvgIconProvider::class,
    'source' => 'EXT:academic_base/Resources/Public/Icons/action/add.svg',
],
```

The provider inlines the file in the default markup as well as in the `inline`
alternative, so the icon follows the text colour wherever it is rendered — in
the record list, the page tree and FormEngine in both backend colour schemes,
and on a frontend page. The core `SvgIconProvider` renders the default markup
as an `<img>`, which keeps the colours of its file. See
[The two markups](#the-two-markups).

Where an identifier is consumed:

- `ctrl.typeicon_classes` of a TCA table, and the `icon` key of a content
  element registration in `Configuration/TCA/Overrides/tt_content.php`. That
  key has to be a registered identifier: `addPlugin()` and
  `TcaManipulator::addRecordType()` write it verbatim into
  `ctrl.typeicon_classes`, `IconRegistry::registerTCAIcons()` registers
  `ctrl.iconfile` and nothing else, and an unregistered value silently renders
  `default-not-found`.
- The `iconIdentifier` of the new content element wizard entry in page TSconfig,
  which is the same identifier as the CType's TCA icon.
- `<core:icon>` in backend partials (the page layout partials of the academic
  page types) and in frontend templates.

### Category types: `inlineIcon: true`

`EXT:category_types` registers `category_types.<group>.<type>` on
`BootCompletedEvent`
([`Classes/ServiceProvider.php`](../../packages/fgtclb/typo3-category-types/Classes/ServiceProvider.php),
`addIcons()`), asking `IconRegistry::detectIconProvider()` for the provider,
which knows bitmap versus SVG by file extension and nothing else. It gives an
SVG `CurrentColorSvgIconProvider` only when the type says `inlineIcon: true` next
to its `icon:`, because the registrar also serves site packages this repository
never sees, and inlining a file not drawn for it is not its decision to take.
Every category type of the academic extensions sets the flag.

```bash
grep -c "inlineIcon: true" packages/fgtclb/*/Configuration/CategoryTypes.yaml
```

### Overriding an icon in a project

A project replaces an icon everywhere by registering the same identifier again,
in the `Configuration/Icons.php` of its site package:

```php
'tx-academicbase-info-phone' => [
    'provider' => CurrentColorSvgIconProvider::class,
    'source' => 'EXT:my_sitepackage/Resources/Public/Icons/phone.svg',
],
```

The later registration wins, and later means later in the order of the active
packages, which is the dependency order. The site package therefore has to
depend on the extension that registers the icon — `require` in its
`composer.json` and `depends` in its `ext_emconf.php` — or its entry may be
merged first and be overwritten. A dependency on any academic extension implies
one on `academic_base`. The override is a complete entry: provider and source
both come from it. It takes effect after the caches are flushed.

A `category_types.*` identifier cannot be overridden that way: the registrar
runs on `BootCompletedEvent`, after every `Icons.php` has been merged, and
registers over it.

### Registration today

```bash
grep -c "'provider'" packages/fgtclb/*/Configuration/Icons.php
grep -rh "'provider' =>" packages/fgtclb/*/Configuration/Icons.php \
  | sed "s/.*=> *//" | sort | uniq -c
grep -ohE "^    '[^']+' =>" packages/fgtclb/*/Configuration/Icons.php \
  | grep -vcE "'tx-[a-z0-9]+-(action|state|info|record|plugin|doktype)-"
```

The first lists the registrations per package, the second the providers they
use, the third counts the identifiers that do not follow the scheme. Today they
print 68 registrations in ten packages, all 68 with
`CurrentColorSvgIconProvider`, and `0`. The groups per package:

```bash
grep -ohE "^    'tx-[a-z0-9]+-[a-z]+-" packages/fgtclb/*/Configuration/Icons.php | sort | uniq -c
```

| Package                  | Registrations | Groups                        | Drawn by a file of `academic_base` |
|--------------------------|---------------|-------------------------------|------------------------------------|
| `academic-base`          | 36            | 17 action, 2 state, 17 info   | all 36                             |
| `academic-persons`       | 13            | 9 record, 4 plugin            | 5 record icons                     |
| `academic-persons-edit`  | 1             | 1 plugin                      | —                                  |
| `academic-jobs`          | 2             | 1 record, 1 plugin            | —                                  |
| `academic-bite-jobs`     | 1             | 1 plugin                      | —                                  |
| `academic-contact4pages` | 3             | 2 record, 1 plugin            | the role record icon               |
| `academic-partners`      | 4             | 2 record, 1 plugin, 1 doktype | both record icons                  |
| `academic-programs`      | 2             | 1 plugin, 1 doktype           | —                                  |
| `academic-projects`      | 2             | 1 plugin, 1 doktype           | —                                  |
| `academic-study-plan`    | 4             | 3 record, 1 plugin            | —                                  |

```bash
grep -c "'source' => 'EXT:academic_base/" packages/fgtclb/*/Configuration/Icons.php
```

The 68 registrations name 55 distinct files. Where two identifiers of one
extension share a file, it is the content element and the record or page type
it is about: `tx-academicjobs-record-job` and `-plugin-jobs`, the contact
record and the content element of `academic-contact4pages`, and the page type
and the content element of partners, programs and projects. The frontend
glyphs of `academic-persons`, `academic-persons-edit`, `academic-jobs` and
`academic-study-plan` are not registered by them at all: their templates render
the shared `tx-academicbase-*` identifiers.

### Content element and page type icons

A content element names its icon in three places, and all three are the same
identifier: the `icon` of its CType item in
`Configuration/TCA/Overrides/tt_content.php`, the
`tt_content.ctrl.typeicon_classes` entry that `addPlugin()` or
`TcaManipulator::addContentElementPlugin()` derives from it, and the
`iconIdentifier` of its new content element wizard entry in page TSconfig. The
page module shows the second, the wizard the third, so an identifier that
differs in one of them shows two icons for one element. A page type does the
same with its `doktype` select item and `pages.ctrl.typeicon_classes`. Every
extension with a content element asserts that its type icon and its wizard
entry name the one identifier, and `academic-persons`, `academic-persons-edit`,
`academic-partners`, `academic-programs` and `academic-projects` assert the
CType item as well. The
wizard assertion sits in `Tests/Functional/Imaging/`, or next to the wizard
registration it checks: `SiteSet/SiteSetDeliveryTest.php` in
`academic-partners` and `academic-persons-edit`,
`TsConfig/NewContentElementWizardRegistrationTest.php` in `academic-bite-jobs`.

## Rendering

### Frontend templates

```html
<core:icon identifier="tx-academicbase-info-phone" alternativeMarkupIdentifier="inline" />
```

`core` is a global Fluid namespace on both core versions — through
`SYS.fluid.namespaces` of `cms-core/Configuration/DefaultConfiguration.php` on
v13, and through `cms-core/Configuration/Fluid/Namespaces.php` on v14 — so no
`xmlns` declaration is needed. With `CurrentColorSvgIconProvider` the argument
changes nothing, since both markups are the inlined file. It is there for the
project that overrides an icon with the core `SvgIconProvider`: that provider
inlines only for `inline`, and without the argument the frontend would get an
`<img>` that no longer follows the text colour. Keep an existing `size`
argument; it does not size an inlined SVG, see below.

A stylesheet addresses an icon through the class `icon-<identifier>`, for
example `.icon-tx-academicbase-action-expand`, or through
`[data-identifier="…"]`.

### Where the templates render icons

```bash
for d in packages/fgtclb/*/; do
  printf '%s %s %s %s\n' "$(grep -rho --include='*.html' '<core:icon' "$d"Resources/Private | wc -l)" \
    "$(grep -rl --include='*.html' '<core:icon' "$d"Resources/Private | wc -l)" \
    "$(grep -rho --include='*.html' 'alternativeMarkupIdentifier="inline"' "$d"Resources/Private | wc -l)" "$d"
done | grep -v '^0 '
```

It prints the `<core:icon>` sites, the files holding them and the sites that
ask for `inline`, per package:

| Package                 | Sites | Files | `inline` | What they render                                                                |
|-------------------------|-------|-------|----------|---------------------------------------------------------------------------------|
| `academic-persons-edit` | 35    | 13    | 35       | 16 shared `tx-academicbase-action-*` and `-state-*` identifiers                 |
| `academic-persons`      | 6     | 2     | 6        | `tx-academicbase-info-{email,phone,location,room}`, `-action-{expand,collapse}` |
| `academic-study-plan`   | 3     | 1     | 3        | `tx-academicbase-action-{expand,collapse,close}`                                |
| `academic-jobs`         | 1     | 1     | 1        | the job property glyphs, `tx-academicbase-info-*`                               |
| `academic-partners`     | 6     | 5     | 0        | `category_types.partners.*`, two of the sites in a backend partial              |
| `academic-programs`     | 4     | 3     | 0        | `category_types.programs.*`, two of the sites in a backend partial              |
| `academic-projects`     | 4     | 3     | 0        | `category_types.projects.*`, two of the sites in a backend partial              |

The one site of `academic-jobs` is the partial
`Resources/Private/Partials/Job/PropertyIcon.html`, which `Job/Item.html`,
`Job/Information.html` and `Job/Contact.html` render for each property they
show. It holds an explicit map from the Extbase property name to the identifier,
and a property that is not in the map renders no icon at all. The identifiers
were constructed from the property name before 3.0 (`<prefix>-{property}`),
which made every property of an overridden list a lookup that could only fail
silently.

The category type sites of partners, programs and projects do without the
`inline` argument. For a type with `inlineIcon: true` both markups are the
inlined file, and a `category_types.*` identifier cannot be overridden through
`Configuration/Icons.php` — see [Overriding an icon in a
project](#overriding-an-icon-in-a-project) — so the argument would change
nothing there. The backend page layout partials of the three page types also
render the core `overlay-hidden` for a hidden category.

### The two markups

An `Icon` carries two markups, both prepared by the provider in
`prepareIconMarkup()`: the **default markup**, which `Icon::render()` and
`<core:icon>` emit unless told otherwise, and the **`inline` alternative**,
which `<core:icon … alternativeMarkupIdentifier="inline">` or
`$icon->render('inline')` selects. Either is wrapped in the same
`<span class="t3js-icon icon …" data-identifier="…"><span class="icon-markup">…</span></span>`.

| Provider                                                               | Default markup               | `inline` markup   |
|------------------------------------------------------------------------|------------------------------|-------------------|
| core `TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider`             | `<img src="…" width height>` | the file, inlined |
| `FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider` | the file, inlined            | the file, inlined |

An `<img>` is opaque to CSS: it keeps the colours of its file whatever the
backend colour scheme or the frontend theme says. An inlined `<svg>` drawn in
`currentColor` takes the colour of the surrounding text — in the backend that is
`--icon-color-primary: currentColor` on `.icon`, defined in `backend.css` on both
cores. The record list and the page tree ask for `inline` themselves; FormEngine
record headers, inline and file reference headers, the page module and the
styleguide take the default markup, which is where the core provider shows an
`<img>`.

### Sizing

In the backend, `backend.css` sizes the inlined element through
`.icon img, .icon svg { width: 100%; height: 100% }` on both cores. A frontend
page has no such rule unless the site ships one, `icon-size-*` is styled in the
backend and admin panel stylesheets only, and the `size` argument sets the
`width`/`height` of an `<img>` and nothing on an inlined SVG. An inlined
`<svg viewBox>` without a size of its own fills its container. Both pipelines
keep `width` and `height` — v14's `toInlineMarkup()` drops only `xmlns` and
`version` — so every file carries `width="1em" height="1em"` and follows the font
size the way the text around it does. Inside `.icon` the backend overrides the
two attributes, so they cost a backend-only icon nothing.

**One size: `1em`, everywhere, and no local compensation.** The fixed width
grid centres every glyph in its 640 unit box. The larger extent of a glyph is
typically 448 to 576 units — 512, 0.8 of the box, most often — and a wide
glyph can use the full 640 of the width. A `1em` icon is therefore visibly
smaller than a glyph that fills its box edge to edge, and that is intended: it is the same for every icon of the set, so a row of icons
and a line of text line up. A stylesheet of an extension does not enlarge an
icon box to make up for it. Where a layout sizes the box of an icon, it sizes it
for the text it stands next to, as it would for any other icon.

**Why the size has to be in the file.** An inline `<svg>` with a `viewBox` and
no `width`/`height` has no intrinsic size. It fills its container, and a
container that is sized by its content collapses it to 0 px: a flex item such
as the semester header of the study plan accordion, or a shrink-to-fit
`<button>` such as the close button of its dialog. That is how the study plan
showed its fold-out and close controls 0 px wide before 3.0 — the files it
shipped for them carried a `viewBox` only. The files of
the set carry `width="1em" height="1em"`, and `academic-study-plan` in
addition sizes `.academic-study-plan .icon svg` to `1em` × `1em` in its
stylesheet. That rule is a guard for a project that overrides one of the
shared identifiers with a file without a size, not an enlargement. Any other
template that puts an inlined icon into a flex item or a button relies on the
file carrying its size.

### JavaScript

`@typo3/backend/icons.js` — `Icons.getIcon()` and the `<typo3-backend-icon>`
element built on it — cannot be used on a frontend page, on either core. It
fetches the markup from `TYPO3.settings.ajaxUrls.icons`, and `PageRenderer`
emits `ajaxUrls` only for a backend request (`getApplicationType() === 'BE'`); the
route behind it is the backend AJAX route `/ajax/icons`, which is not among the
public routes of `BackendUserAuthenticator` and answers only a logged-in backend
user. The module itself resolves through the import map and fails at its first
request.

Frontend TypeScript therefore never builds icon markup itself. It takes markup
the server rendered, with the provider of the icon and with a project's
override, in one of three ways:

| Way                                       | Request     | Use it when                                                                                                                                            |
|-------------------------------------------|-------------|--------------------------------------------------------------------------------------------------------------------------------------------------------|
| A `<template>` the module clones          | none        | the icon is part of a larger piece of markup the module stamps out — a prototype row with its buttons, a list item                                     |
| The JSON icon map, `ab:frontendIconMap`   | none        | the module picks an icon by its identifier at runtime, out of a set the template can name in advance                                                   |
| The icon endpoint, `_academic/icons.json` | one per set | the identifier is not known when the page is rendered — it comes from data the module loads, or from a choice of the visitor; through the icon factory |

The `<template>` is the first choice wherever the markup around the icon is
Fluid's anyway. Fluid renders the icon with
`<core:icon … alternativeMarkupIdentifier="inline" />` into the `<template>`
element, and the module clones it — the prototypes of the profile editor in
`academic_persons_edit` are the reference, see
[Where the icons come from](profile-editing-contract.md#where-the-icons-come-from).
A control whose glyph depends on its state renders both icons and toggles
`hidden`.

**Everything below the `<template>` is internal and experimental.** It is
marked `@internal` and may change without a `Breaking-*.rst` until a module
outside the dev-site demonstration uses it; the changelog entry of
`academic_base` is an `Important-*.rst` for that reason. That covers the
ViewHelper, the endpoint with its parameters and its answer, the renderer, the
event and the TypeScript icon factory.

### The JSON icon map

```html
<html xmlns:ab="http://typo3.org/ns/FGTCLB/AcademicBase/ViewHelpers" data-namespace-typo3-fluid="true">
<ab:frontendIconMap identifiers="{0: 'tx-academicbase-action-add', 1: 'tx-academicbase-action-delete'}" />
```

renders one data block:

```html
<script type="application/json" data-academic-icons data-academic-icons-size="small">{"tx-academicbase-action-add":"\u003Cspan class=\u0022t3js-icon …"}</script>
```

The values are exactly what `<core:icon … alternativeMarkupIdentifier="inline" />`
renders for the identifier, the wrapper `<span>` included. `size` takes
`default`, `small` (the default), `medium`, `large` or `mega`; `overlay` is
core's size of an overlay icon and refused. It changes the `icon-size-*` class
of the wrapper and nothing else — an inlined SVG sizes itself, see
[Sizing](#sizing).

A `<script>` with a JSON type is a data block: the browser neither executes it
nor checks it against the page's Content Security Policy, so the element needs
no nonce and the policy no change. The JSON is encoded with `<`, `>`, `&` and
both quotes as `\u` escapes, so no markup in an icon can close the element
early, and invalid UTF-8 is replaced rather than failing the content element.
The module reads it with `JSON.parse(element.textContent)`.

With `endpoint="1"` the element also carries `data-academic-icons-url`, the
[icon endpoint](#the-icon-endpoint) of the current site language, and
`data-academic-icons-version`, the [version token](#the-version-token) of the
icon set. Outside a site both are left out.

The ViewHelper and everything else that hands icons to the frontend go through
[`FrontendIconRenderer`](../../packages/fgtclb/academic-base/Classes/Imaging/FrontendIconRenderer.php)
of `academic_base`, a stateless service: `render(identifiers, size)` answers a
map in the order asked for, `isServable(identifier)` the decision below, and
`getVersion()` the [version token](#the-version-token).

### The icon endpoint

```text
GET <site base>/_academic/icons.json?i=<id>,<id>,…&s=<size>&v=<token>
```

answers `{"<id>": "<markup>", …}` with exactly the markup of the JSON map, for
the identifiers of `i` that may be served, in the order of the request. It is
the PSR-15 middleware
[`FrontendIconEndpoint`](../../packages/fgtclb/academic-base/Classes/Middleware/FrontendIconEndpoint.php)
of `academic_base`, registered for the frontend in
[`Configuration/RequestMiddlewares.php`](../../packages/fgtclb/academic-base/Configuration/RequestMiddlewares.php).
The `<site base>` is the base of any site language, so
`https://example.com/de/_academic/icons.json` and a subfolder installation's
`https://example.com/sub/_academic/icons.json` both work; the middleware
compares the route tail the site resolver leaves, not the path.

| Request               | Answer                                                                                    |
|-----------------------|-------------------------------------------------------------------------------------------|
| `GET` or `HEAD`       | `200`, `application/json`; `HEAD` with the headers of `GET` and no body                   |
| any other method      | `405` with `Allow: GET, HEAD`                                                             |
| `i` missing or empty  | `400`                                                                                     |
| more than 32 in `i`   | `400`                                                                                     |
| a malformed `i` entry | `400` — every entry has to match `\A[a-z0-9_][a-z0-9_.-]{0,99}\z`                         |
| `s` not a size        | `400` — `default`, `small` (the default when `s` is missing), `medium`, `large` or `mega` |
| an identifier refused | left out of the object, which is `{}` when nothing may be served                          |

A `400` or `405` carries `{"error": "…"}` and `Cache-Control: no-store`.

**Caching.** When `v` is the current version token the answer is
`Cache-Control: public, max-age=31536000, immutable`, for any other `v`, or
none, `public, max-age=300`. The token only decides how long the answer may be
kept — the body is the current markup either way. The `ETag` is a hash of the
body, and an `If-None-Match` that names it (also as a weak validator, or `*`)
is answered `304`. So a page that asks with the token it was rendered with gets
an answer a browser and a proxy keep for a year, and a token that no longer
matches — a page served from the page cache after the icons changed — misses
every cache. There is no `Vary`: the answer depends on the URL alone.

The `ETag` only helps the five-minute answers, which a cache revalidates once
they are stale. An `immutable` answer is never revalidated, by a browser or by a
CDN, so the `ETag` cannot catch a change the token does not see: whatever
changes the markup of an icon has to change the token. What it covers, and what
it does not, is under [The version token](#the-version-token).

**The `.json` suffix.** The path ends in `.json` so that it reads as what it
answers. A web server or CDN rule that serves `*.json` as a static file — an
nginx `location ~* \.(…|json)$` with `try_files $uri =404` is a common one —
answers the endpoint with a `404` before TYPO3 sees the request, the same trap
`sitemap.xml` is known for. Such a rule has to pass `_academic/icons.json` on to
`index.php`.

**Its place in the middleware stack**, verified with core's
`DependencyOrderingService` on both core versions:

| After                                 | Because                                                                |
|---------------------------------------|------------------------------------------------------------------------|
| `typo3/cms-frontend/site`             | the `site`, `language` and `routing` attributes, and so the route tail |
| `typo3/cms-frontend/maintenance-mode` | a site in maintenance answers `503` here as well                       |

| Before                                           | Because                                                           |
|--------------------------------------------------|-------------------------------------------------------------------|
| `typo3/cms-frontend/backend-user-authentication` | no backend session is looked up                                   |
| `typo3/cms-frontend/authentication`              | no frontend session, so never a `Set-Cookie` and always cacheable |
| `typo3/cms-frontend/page-resolver`               | the path is not a page and would be answered `404`                |

It lands between `maintenance-mode` and `request-token-middleware` on 13.4.34
and 14.3.6 alike. It never names `typo3/cms-frontend/base-redirect-resolver` or
`typo3/cms-frontend/static-route-resolver`: EXT:redirects puts its own
middleware between the authenticators and those two, and "after
`base-redirect-resolver`, before `authentication`" is a dependency cycle on
both versions. Nor `typo3/cms-frontend/tsfe`, which exists on v13 only. The
answer leaves the stack before the CSP, content length and output compression
middlewares, none of which a JSON answer needs.

**Security.** The endpoint is public and needs no authentication, because it
hands out nothing a page does not render anyway: only allow-listed, registered,
non-deprecated identifiers, rendered by the same provider as in a template.
Apart from what the provider reads to render an icon it only stats files, for
the version token, and never reads one — an identifier is looked up in the
registry, never turned into a path. It touches no database, starts no session,
is bounded to 32 icons per request, and every answer is publicly cacheable. The
URL carries no version and the contract is internal and experimental: its
parameters and its answer may change without a `Breaking-*.rst` until a module
outside the dev-site demonstration uses it.

### The icon factory

`@fgtclb/academic-base/frontend/icons.js`
([source](../../packages/fgtclb/academic-base/Resources/Private/TypeScript/frontend/icons.ts))
reads both for a module, so that a module asks for an icon by its identifier
and does not care where the markup comes from:

```ts
import { IconFactory, Sizes, endpointFrom } from '@fgtclb/academic-base/frontend/icons.js';

const icons = new IconFactory(endpointFrom(root));
button.append(await icons.getIconElement('tx-academicbase-action-add'));
const markup = await icons.getIcon('tx-academicbase-info-phone', Sizes.medium);
await icons.prefetch(['tx-academicbase-action-edit', 'tx-academicbase-action-delete']);
```

| Member                                     | What it does                                                                                  |
|--------------------------------------------|-----------------------------------------------------------------------------------------------|
| `Sizes`, `Size`                            | `default`, `small`, `medium`, `large`, `mega` — an `as const` object and its union, no `enum` |
| `IDENTIFIER_PATTERN`                       | the identifier pattern of the server, for a module that filters its data first                |
| `endpointFrom(root)`                       | the endpoint `root` or the first JSON map inside it names, `null` where there is none         |
| `new IconFactory(endpoint = null)`         | a factory; without an endpoint it answers from the JSON maps of the page only                 |
| `getIcon(identifier, size = small)`        | `Promise<string>`, the markup with its wrapper `<span>`                                       |
| `getIconElement(identifier, size = small)` | `Promise<Element>`, a new element on every call                                               |
| `prefetch(identifiers, size = small)`      | asks for all of them, resolves when each one is settled                                       |

On a miss the factory first reads every JSON icon map of the document it has
not read yet — also one that reached the page later — and only then asks the
endpoint. The calls of one microtask are collected into one request per
endpoint and size, split into requests of at most 32 identifiers, with the
version token of the page. One promise per icon and size is kept for the
lifetime of the page and shared by every factory on it, so an icon is asked for
once. An identifier the endpoint leaves out rejects, and stays rejected; a
failed request rejects and is asked again on the next call.

An identifier that does not match the pattern of the server rejects at once
and is never sent. The endpoint answers a request with one malformed entry with
`400` as a whole, so without that check one bad identifier out of the data a
module loads would fail every icon batched with it — and, since failures are
retried, fail them again on the next render; a comma inside one would even turn
into several entries. `IDENTIFIER_PATTERN` is the pattern of
`FrontendIconRenderer::IDENTIFIER_PATTERN`, `\A…\z` there and `^…$` here,
which is the same thing in JavaScript without the `m` flag. It is written down
twice, and `icons.test.ts` reads the PHP constant out of the source and fails
when the two differ; the same test holds the batch size against
`FrontendIconEndpoint::MAX_IDENTIFIERS`.

A request is sent with `credentials: 'omit'`: the answer does not depend on a
session, and a cookie on the request would make many proxy and CDN setups pass
it to the backend instead of answering from their cache. A request that has no
answer within ten seconds is aborted and counts as failed, so its icons are
asked for again on the next call instead of waiting for the life of the page.

What it deliberately does not take over from `@typo3/backend/icons.js`:
`localStorage` — with an immutable answer the browser cache already holds it,
and web storage would be one more place injected script could plant markup
for later pages; overlays, states and alternative markups, whose styles exist
in the backend only; `DedupeAsyncTask` and the `enum`s, replaced by a `Map` of
promises and `as const` objects, because the module has to stay erasable
TypeScript for the [JavaScript tests](../testing/javascript-tests.md). It is a
rewrite modelled on the backend module, not a copy. Like the rest it is
`@internal`: its exports may change without a `Breaking-*.rst` for now.

The module is in the import map of `academic_base`
(`Configuration/JavaScriptModules.php`, prefix `@fgtclb/academic-base/frontend/`).
The import map of a page carries only the prefixes of the packages the modules
loaded on it declare, so a module of another extension that imports the factory
names `academic_base` in the `dependencies` of its own
`Configuration/JavaScriptModules.php`; without it the specifier does not
resolve in the browser. Its behaviour is covered by
[`academic-base/Tests/JavaScript/icons.test.ts`](../../packages/fgtclb/academic-base/Tests/JavaScript/icons.test.ts).

### Which icons may leave the server

An identifier is served when all four hold, and otherwise it is left out of the
answer — never replaced by the `default-not-found` placeholder:

1. **It matches `\A[a-z0-9_][a-z0-9_.-]{0,99}\z`.** `\A` and `\z`, because `$`
   also matches before a trailing line feed.
2. **It starts with an allowed prefix**: `tx-academic` and `category_types.` by
   default. A project adds the prefix of its own identifiers with a listener
   to `FGTCLB\AcademicBase\Event\ModifyFrontendIconAllowListEvent`
   (`addPrefix()`, `setPrefixes()`, which refuses anything but strings), which
   is dispatched every time the list is needed. The allow-list is a list of
   identifier prefixes and not of source paths on purpose: a project that
   overrides `tx-academicbase-info-phone` in its own `Configuration/Icons.php`
   points it at a file of its own, and a path filter would refuse exactly the
   override the project wants delivered.
3. **It is registered and not deprecated.** `IconRegistry::isDeprecated()` is
   asked before anything reads the registration, because
   `getIconConfigurationByIdentifier()` raises the `E_USER_DEPRECATED` itself.
   `isRegistered()` is asked first: it completes the registry's initialisation,
   which `isDeprecated()` relies on without triggering it.
4. **Its provider inlines an SVG file**: a subclass of core's
   `AbstractSvgIconProvider`, which `CurrentColorSvgIconProvider` and core's
   `SvgIconProvider` are. Refused are `SvgSpriteIconProvider`, which the icons
   of the core icon set are registered with on both versions and which renders
   an `<svg><use>` into a sprite without a size of its own, and
   `BitmapIconProvider`, whose `<img>` needs a URL that cannot be built
   correctly outside a page request.

The icons of the core icon set are therefore refused twice — by their prefix
and by their provider. That is not true of every icon a system extension
registers: EXT:install, EXT:redirects and a few others register icons of their
own with core's `SvgIconProvider`, which is inlined, so a listener adding a
prefix such as `module-` serves `module-install-environment` and its siblings.
They are public files and nothing secret, but the default list does not serve
them, and a listener that widens the list is responsible for what it opens.
Sprite, bitmap and font providers stay refused whatever the list holds.

**Sanitising is the provider's.** The markup is what the registered provider
renders, sanitised as far as that provider sanitises:
`CurrentColorSvgIconProvider` runs core's SVG sanitiser on both core versions,
core's `SvgIconProvider` sanitises its inline markup on v14 only — on v13 it
strips `<script>` elements and nothing else. That is exactly the exposure a
server-rendered `<core:icon … alternativeMarkupIdentifier="inline" />` of the
same icon has, and a provider is chosen by an integrator in a
`Configuration/Icons.php`, never by a visitor. The renderer does not refuse core's `SvgIconProvider` on v13 and
does not sanitise a second time: either would make its markup differ from what
the same icon renders in a template.

### The version token

`FrontendIconRenderer::getVersion()` hashes, over every servable identifier, its
registration (`getIconConfigurationByIdentifier()`) and the modification time of
its `source` file, plus the TYPO3 version and core's
`PackageDependentCacheIdentifier`. It changes when a registration is added,
removed or replaced, when a source file changes on disk, on a TYPO3 update, and
with the package cache identifier: in composer mode a hash of `composer.lock`
and the dev mode, so every update of a package and every change of the
installed set; in classic mode the path, size and modification time of
`PackageStates.php`; in both the project path, so a deployment into a new
release directory as well. It does not read a single SVG.

The package identifier is there for the provider classes. A change to what
`CurrentColorSvgIconProvider` or core's sanitiser makes of a file changes no
registration and no source file, and would otherwise keep the token — and with
it an `immutable` answer in every browser for up to a year. It arrives with an
update of a package, so it arrives with a new `composer.lock`.
`PackageDependentCacheIdentifier` is `@internal` in core, like the
`AbstractSvgIconProvider` the provider extends; the call is marked in the
renderer and has to be re-read on every core update.

What the token still cannot see is rendering code changed in place with none of
those — a file of a provider edited on the server, a patch applied without a
new `composer.lock`. Touching the source files of the affected icons changes
the token then.

The core's own `iconCacheIdentifier` is no substitute: it is derived from the
TYPO3 version and the package list (`PackageDependentCacheIdentifier` again),
and stays the same when an SVG or a `Configuration/Icons.php` is edited.

## The provider

### What it guarantees

**Trust boundary.** Core's own sanitisation differs per core. v14 runs the full
`enshrined/svg-sanitize` pass through `SvgDocumentFactory`. v13 strips `<script>`
elements with a regular expression and re-serialises through `simplexml`, and
that is all: an `onload` or `onclick` attribute, a `javascript:` href and a
`<foreignObject>` pass through untouched. Rendering the file as an `<img>`, which
is what the core provider does for its default markup, made that harmless;
inlining does not. `CurrentColorSvgIconProvider` therefore runs
`SvgSanitizer::sanitizeContent()` itself on v13 — the identical library pass,
from a class that exists with the same signature on 13.4.34 and 14.3.6, both
backed by `enshrined/svg-sanitize` 0.22.0.

That closes a hole, it does not move the boundary. The sanitiser is a filter, not
a guarantee, and it does nothing about the two ways an inlined file interferes
with the page around it: a duplicated `id`, and a `<style>` block, which is
document-global CSS once inlined. The sources stay files an extension ships and
registers in its own `Configuration/Icons.php`, in the house format.

**A file the provider cannot inline yields no markup, and never an error** —
missing, unreadable, empty, not XML, or XML whose root element is not an `<svg>`.
The last one had to be added rather than found: `enshrined/svg-sanitize` throws
a plain `\LogicException` with code 1570870568 out of
`XPath::handleDefaultNamespace()` when the document does not carry exactly one
`<svg>` root, and nothing below the provider catches it. A `<symbol>` fragment or
an `<html>` document saved under an `.svg` name would otherwise take the whole
response with it. The provider catches it in both branches. **TYPO3 v14 core has
the same hole on its own inline path**: `AbstractSvgIconProvider::getInlineSvg()`
catches `InvalidSvgException` only, so core's `SvgIconProvider` still fails that
way for an inline render. Fixing that belongs upstream.

### A comment does not reach the markup

`Sanitizer::cleanUnsafeNodes()` removes every node that is neither an element nor
text, comments included, on both cores since the provider sanitises on v13 too.
Font Awesome's attribution comment therefore stays in the source file and never
reaches the rendered page, which is why the attribution is the notice file — see
[Licence](#licence).

### How the provider is wired, per core version

`AbstractSvgIconProvider` has the same public surface on 13.4.34 and 14.3.6 and
different internals, and that decides two things about the subclass. The parent
is `@internal` on both cores and v14 already rewrote it once; every core bump has
to re-read it before trusting the two points below.

**No constructor.** On v14 the parent gets `SvgDocumentFactory` and
`SvgDocumentService` through `injectSvgDocumentFactory()` /
`injectSvgDocumentService()` setters, which TYPO3's `AutowireInjectMethodsPass`
registers for an autowired service. A subclass constructor would have to
forward what it does not own.

**Not excluded from the container.** `cms-core/Configuration/Services.php`
on v14 tags every `IconProviderInterface` as `icon.provider` and a
`PublicServicePass('icon.provider')` publishes it, so `IconFactory` finds the
provider through `$container->has()` and gets the instance with the setters
called. Excluded from the `resource` load of `academic-base`'s `Services.yaml`,
the provider would be created with `new` instead and the first inline render
on v14 would fail on an uninitialised property. v13's
`cms-core/Configuration/Services.php` has no `icon.provider` tag, so there the
unreferenced private service is dropped at compile time and the bare instance
renders. It needs nothing, because on v13 the provider does not call the
parent's `getInlineSvg()` at all.

**One version switch.** The v14 `getInlineSvg()` resolves an `EXT:` path itself
through `SystemResourceFactory` and sanitises the content through
`SvgDocumentFactory`, so v14 is handed the path unchanged. The v13 one expects an
absolute path and sanitises next to nothing, so `generateInlineMarkup()` takes
the whole v13 branch itself: it resolves the path with
`GeneralUtility::getFileAbsFileName()`, reads the file, runs
`SvgSanitizer::sanitizeContent()` over it and re-serialises the document element
to drop the XML declaration the sanitiser writes. The switch carries a `@todo`
for the v13 support end, like the two `TcaManipulator` switches it is listed next
to in [Core version aware code](core-version-aware-code.md).

## Verification

### Tests

[`academic-base/Tests/Functional/Imaging/SharedIconsTest.php`](../../packages/fgtclb/academic-base/Tests/Functional/Imaging/SharedIconsTest.php)
covers the shared set on both cores. Per identifier: registered with
`CurrentColorSvgIconProvider`, both markups the inlined file, drawn in
`currentColor` with no hardcoded colour, `id` or `<style>`, the house format
(640 grid, `1em`, `fill` on the root, no `style` attribute), and the rendered
icon carrying its own identifier rather than `default-not-found`. For the whole
extension: the registered `tx-academicbase-*` set equals the list in the test,
every identifier follows the scheme with the groups `action`, `state` and `info`,
its file is `Icons/<group>/<name>.svg`, every SVG below `Resources/Public/Icons/`
apart from `Extension.svg` is the source of a registered icon, and every one of
them carries the Font Awesome comment and is listed in the notice.

The assertions live in
[`ColourSchemeAwareIconsTrait`](../../packages-dev/testing-helper/Classes/FunctionalTestCase/ColourSchemeAwareIconsTrait.php);
the extension-wide ones take an extension key, so another extension adopts them
with one test method each — see [Testing helper](../testing/testing-helper.md#colourschemeawareiconstrait).
The identifiers are spelled out per test rather than read back out of
`Configuration/Icons.php`, so a rename has to be made twice instead of silently
agreeing with itself.

Each of the nine other extensions that ship icons runs the same checks from its
`Tests/Functional/Imaging/` tests: the per identifier ones, the house format,
the naming scheme with the groups it uses, no orphaned file and every file in
the notice. `academic-programs` and `academic-projects` exempt their
`category-group/*.svg` from the orphan check, because `EXT:category_types` does
not read the `groups:` icon yet (ACE-364). Each also runs
`assertEveryRecordTypeIconIsColourSchemeAware()`, which walks the TCA instead of
a list. It decides by type what the extension owns: every type of its
`tx_<key without underscores>_*` tables, and the content element and page types
the test passes in. Every owned type has to name an identifier of the
extension's own `tx-<key>-` prefix — a core or a foreign identifier fails, and
so does a missing one, which is what an empty `icon` of a content element
registration leaves behind — and that identifier has to be registered, not
deprecated, on `CurrentColorSvgIconProvider`, inlined in both markups and drawn
in `currentColor`. A table added later is covered as it is; a content element or
page type added later has to be added to the test's list, which is why the
tests also pin the identifier each type names. The job property map is
covered by
[`academic-jobs/Tests/Functional/Templates/PropertyIconPartialTest.php`](../../packages/fgtclb/academic-jobs/Tests/Functional/Templates/PropertyIconPartialTest.php).
The programmatic category type registration is covered by
[`typo3-category-types/Tests/Functional/Imaging/CategoryTypeIconsTest.php`](../../packages/fgtclb/typo3-category-types/Tests/Functional/Imaging/CategoryTypeIconsTest.php)
across the four branches of the registrar, and the provider itself by
[`academic-base/Tests/Functional/Imaging/IconProvider/CurrentColorSvgIconProviderTest.php`](../../packages/fgtclb/academic-base/Tests/Functional/Imaging/IconProvider/CurrentColorSvgIconProviderTest.php)
and the unit test of the same name.

What reaches frontend TypeScript is covered by the unit tests of the identifier
pattern and of the event, by
[`academic-base/Tests/Functional/Imaging/FrontendIconRendererTest.php`](../../packages/fgtclb/academic-base/Tests/Functional/Imaging/FrontendIconRendererTest.php)
— each refusal on its own, the override of a project, the allow-list event and
what changes the version token —, by
[`academic-base/Tests/Functional/ViewHelpers/FrontendIconMapViewHelperTest.php`](../../packages/fgtclb/academic-base/Tests/Functional/ViewHelpers/FrontendIconMapViewHelperTest.php),
which renders the JSON map from fixture templates and parses it back, and by
[`academic-base/Tests/Functional/Middleware/FrontendIconEndpointTest.php`](../../packages/fgtclb/academic-base/Tests/Functional/Middleware/FrontendIconEndpointTest.php),
which requests the endpoint through the whole frontend middleware stack of the
core version under test, below a site root, a `/de/` language and a subfolder
base: every `400` and `405`, `HEAD`, the cache headers, `304` for either
lifetime, pass-through for every other path, and no `Set-Cookie` for a request
that makes the authenticator send one on a page;
`FrontendIconEndpointMaintenanceTest` next to it asserts the `503` of a site in
maintenance. Their icons come from the fixture extension `test_frontend_icons`,
one registration per reason to refuse.

**Keeping a template's icons resolvable.** `<core:icon>` never fails on an
unknown identifier: `IconFactory` answers with the `default-not-found`
placeholder and the identifier that was asked for is gone from the markup, so a
renamed registration or a typo in a template ships silently. A rendering test
of a plugin that shows icons therefore carries two assertions: the page does not
contain `default-not-found`, and it does contain
`data-identifier="<the identifier>"` for the icons the template renders. The
first catches an identifier that no longer resolves, the second a rename the
template did not follow — which the first alone would pass, since the
placeholder replaces the identifier.

### Looking at the set

- **Backend:** the styleguide module (`typo3/cms-styleguide`, installed in both
  development instances and not in the test harness) → Styles → Icons lists
  every registered icon in the default markup, with a search field — `tx-` or
  `category_types` narrows it to ours. Its module parent is `system` on v13 and
  `admin` on v14. Switch the colour scheme in the user menu to check light
  and dark; an icon that stays dark on a dark card is registered with the core
  provider or drawn with a colour of its own.
- **Frontend:** the page `Icons` of the development seed (ACE-594) renders every
  icon of the academic extensions with
  `<core:icon … alternativeMarkupIdentifier="inline" />`, grouped by the
  identifier prefix and group, with the category type icons in a section of
  their own. Each identifier is printed next to its icon, and the icon is
  rendered three times: at 1em inside a line of text, at 2rem, and in the text
  colour of a dark ground — an icon that does not follow `currentColor` stays
  dark in the third column. The list is read from the icon registry when the
  page renders (`IconOverviewProcessor` of `packages-dev/dev-site`: identifiers
  starting with `tx-academic` or `category_types.`), so a new or renamed icon is
  on it without a change to the seed.
- **Frontend JavaScript:** the same page opens with a section "Frontend icon
  API" (ACE-595) that exercises both paths of the [icon factory](#the-icon-factory)
  in the browser. The module `icon-demo.js` of `packages-dev/dev-site` fills a
  first list of five icons from the JSON map rendered next to it, without a
  request, and a second list of five action icons plus the core `actions-add`
  from the endpoint, in one request; each slot ends up with the icon or with
  "not available", and says which in `data-icon-demo-state` (`rendered` or
  `failed`). `actions-add` has to fail. The network panel shows exactly one
  request to `_academic/icons.json`, answered `immutable`.

The frontend page is in both trees of both instances, and its German variant is
`/de/symbole`. The `/` tree shows it in the bootstrap_package theme, the
`/legacy/` tree unstyled — see
[Development instances](../development/instances.md#the-icon-overview-page).

| Instance  | `/` tree                                      | `/legacy/` tree                                      |
|-----------|-----------------------------------------------|------------------------------------------------------|
| `core-13` | <https://core13-academics-v3.ddev.site/icons> | <https://core13-academics-v3.ddev.site/legacy/icons> |
| `core-14` | <https://core14-academics-v3.ddev.site/icons> | <https://core14-academics-v3.ddev.site/legacy/icons> |

## See also

- [Core version aware code](core-version-aware-code.md) — the switch
  convention the provider follows.
- [Dependency injection](dependency-injection.md) — the `resource`/`exclude`
  load the provider stays inside.
- [Testing helper](../testing/testing-helper.md) — the icon assertions of
  `ColourSchemeAwareIconsTrait`.
- [Fixture extensions](../testing/fixture-extensions.md) — the mechanism the
  provider test's icons are registered through.
- [The profile editing contract](profile-editing-contract.md) — the
  `<template>` prototypes frontend icons are cloned from.
