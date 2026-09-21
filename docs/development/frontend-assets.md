# Frontend assets

TypeScript and SCSS sources live in the extension they belong to, are compiled
by one build for the whole repository, and the result is committed.

## Layout

Sources sit below `Resources/Private/`, which TYPO3 does not serve, and compile
into the sibling `Resources/Public/`:

```
packages/fgtclb/<extension>/
  Resources/Private/TypeScript/{backend,frontend}/**.ts
  Resources/Private/Scss/{backend,frontend}/**.scss
        ->  Resources/Public/JavaScript/{backend,frontend}/**.js
        ->  Resources/Public/Css/{backend,frontend}/**.css
```

The same applies to `packages-dev/*`. Nothing is required to exist: an extension
without those directories contributes nothing to the build, and adding one is
picked up without touching any configuration. Five extensions carry sources
today: `academic-jobs` ships TypeScript only, and `academic-partners`,
`academic-persons`, `academic-persons-edit` and `academic-study-plan` ship
TypeScript and SCSS. `academic-persons` carries the public profile's
`frontend/profile.ts` and `frontend/profile-detail.scss`, loaded by
`Templates/Profile/Detail.html`, plus the `frontend/sticky-offset.ts` the
editing view of `academic-persons-edit` shares with it through the import map.
`academic-persons-edit` is the largest by a wide margin — nineteen TypeScript
modules, one `_dependencies.d.ts` type declaration and
`frontend/profile-editing.scss`. Count them with
`find packages/fgtclb/academic-persons-edit/Resources/Private/TypeScript -name '*.ts' ! -name '*.d.ts' | wc -l`.

The `backend/` and `frontend/` split is a convention rather than a mechanism —
the build mirrors whatever directory structure it finds. Keeping the two apart
matters because a TYPO3 import map maps a prefix, so a backend module can be
kept off a frontend page by mapping only the `frontend/` prefix.

A file whose name starts with an underscore is a **partial**: it is reached
through `@use` and never becomes an entry point of its own. That is the sass
convention, and the build applies it to TypeScript as well.

## The build

One script, `Build/esbuild.mjs`, driven by npm scripts and run in a container:

| Suite               | Runs                                                      | Purpose                                                                                        |
|---------------------|-----------------------------------------------------------|------------------------------------------------------------------------------------------------|
| `buildJs`           | `npm ci && npm run build`                                 | Compiles every extension's sources. Run after a source change, and commit the result.          |
| `checkJsBuildClean` | delete the outputs, rebuild, assert `git status` is empty | The gate that makes committed artifacts trustworthy. Runs in CI.                               |
| `lintTypescript`    | `npm run lint:fix`, or `lint` with `-n`                   | eslint 9 with typescript-eslint. Mirrors `cgl`: fixes by default, checks only with `-n`.       |
| `typecheckJs`       | `npm run typecheck`                                       | `tsc --noEmit`, which the build does not do.                                                   |
| `testJs`            | `npm run test`                                            | The behavioural tests of the sources — see [JavaScript tests](../testing/javascript-tests.md). |
| `npm`               | `npm "$@"` with the working directory set to `Build/`     | Escape hatch, mirroring the `composer` suite.                                                  |
| `cleanJs`           | `rm -rf Build/node_modules`                               | Intermediates only. It never removes a compiled artifact — those are committed files.          |

```bash
Build/Scripts/runTests.sh -s buildJs
Build/Scripts/runTests.sh -s checkJsBuildClean
Build/Scripts/runTests.sh -s lintTypescript -n
Build/Scripts/runTests.sh -s typecheckJs
Build/Scripts/runTests.sh -s testJs
Build/Scripts/runTests.sh -s npm -- install --save-dev sass@latest
```

All seven are **core version independent**. They look at the sources and the
committed artifacts and never at the installed core, so `-t` does not change
what they do and no `composerUpdate` is needed. That makes them the only suites
that are safe to run while the other core version's dependency set is installed.

The image is pinned: `ghcr.io/typo3/core-testing-nodejs24:1.1`, the one TYPO3
core uses for its own JavaScript suites. It carries node 24 and npm 11, matching
the `engines` range of `Build/package.json`, and it ships git, which
`checkJsBuildClean` needs. Pinned rather than `:latest` on purpose — a node
major changing under a committed artifact is the kind of surprise the gate
exists to catch, not to produce.

The npm cache lands in `.cache/npm`, next to the composer cache and for the same
reason: `composerUpdate` starts with `rm -rf .Build`, so a cache inside `.Build/`
would be discarded on every dependency install.

`Build/package.json` also lists a package nothing here imports: `js-yaml`. It
is a transitive dependency of `eslint` itself, by way of `@eslint/eslintrc`, and
a transitive version is stated nowhere but the committed lock. The direct
`devDependencies` entry puts the minimum into the file that is reviewed, so a
regenerated lock cannot lower it unnoticed.

It is worth checking on an eslint raise, because the entry protects less than it
looks like it does. Should `@eslint/eslintrc` move to a `js-yaml` major the root
entry does not accept, npm installs a second, nested copy rather than failing:
the pinned one then covers nothing and the one actually in use is unpinned
again. The entry is useful for as long as its range and that of the dependent
still overlap.

## What the build does

**Scripts** are emitted one module per source module, unbundled, as ES modules.
Every import survives into the output exactly as written and is resolved in the
browser by the TYPO3 import map. That is what gives each module its `?bust=`
cache key: only a specifier that goes through the map receives one, while a
relative specifier resolves against the URL of the importing module and drops
the query string — so a deploy could pair a fresh entry module with a stale
cached dependency. Modules of one extension therefore import each other by their
bare specifier, never relatively.

**Stylesheets** are bundled, because `@use` and `url()` have to be resolved at
build time. dart-sass compiles the SCSS and hands the result to esbuild as CSS,
so each tool does the part it is good at.

**Referenced files** — images, icons, fonts — are emitted into
`Resources/Public/Css/assets/<name>-<hash>.<ext>` and the `url()` is rewritten to
point at them relatively. That is what keeps a stylesheet working in a composer
installation, where only `Resources/Public/` is published.

Nothing is minified: the emitted files are meant to be readable, and nothing here
is large enough for the size to be worth the loss. Source maps are never
committed; `npm run build:dev` carries an inline one instead, and differs from
the committed build in nothing else.

## Loading the result

The compiled JavaScript is an ES module, which a classic `<script src>` cannot
execute. There is **no TypoScript key that loads an ES module** — the frontend
request handler only knows `includeJSLibs`, `includeJSFooterlibs`, `includeJS`
and `includeJSFooter`, all of which emit a classic script tag.

So an extension shipping TypeScript declares its prefix:

```php
// packages/fgtclb/<extension>/Configuration/JavaScriptModules.php
return [
    'dependencies' => ['core'],
    'imports' => [
        '@fgtclb/<extension>/' => 'EXT:<extension_key>/Resources/Public/JavaScript/',
    ],
];
```

and its templates load a module rather than a script:

```html
<f:asset.module identifier="@fgtclb/<extension>/frontend/example.js" />
```

CSS is unaffected and keeps loading through `f:asset.css` or
`page.includeCSS`.

Verified present on TYPO3 13.4.34 and 14.3.6: the `f:asset.module` ViewHelper,
`AssetCollector::addJavaScriptModule()`, and `ImportMap` reading
`Configuration/JavaScriptModules.php` from every package.

## Making an asset optional

A template that registers its assets itself is the reason an installation
overrides the whole template to restyle one element — and then either loses the
script or copies it, after which the copy stops following the original. The way
out is a switch per asset, and it has a shape:

| Layer                                                       | What it holds                                                               |
|-------------------------------------------------------------|-----------------------------------------------------------------------------|
| `Configuration/Sets/<Component>/settings.definitions.yaml`  | `plugin.tx_<ext>.assets.css` / `.js`, `type: bool`, `default: true`         |
| `Configuration/TypoScript/<Component>/constants.typoscript` | the same two paths, `= 1`, for an installation without site sets            |
| `Configuration/TypoScript/<Component>/setup.typoscript`     | `settings.assets.css = {$plugin.tx_<ext>.assets.css}` on the content object |
| the template                                                | `<f:if condition="{settings.assets.css}">` around the `f:asset.*` line      |

Four things about it are worth knowing before copying it:

- **A `FLUIDTEMPLATE` reads `settings.` straight off the content object**
  (`FluidTemplateContentObject`, `$conf['settings.']` → `{settings}`), so the
  switch is written on the object itself. An Extbase plugin gets its `settings`
  from `plugin.tx_*.settings`, its FlexForm and the cObject's own TypoScript
  merged together (`FrontendConfigurationManager::getConfiguration()`), so the
  same switch belongs in the plugin's TypoScript there, not on the element.
- **A boolean site setting arrives as `1` or as the empty string.**
  `SysTemplateTreeBuilder::addDefaultTypoScriptConstantsFromSite()` concatenates
  the value into a constants line, and PHP's `false` stringifies to nothing.
  `1` is truthy for Fluid's `f:if` and the empty string is falsy, so the switch
  works through either mechanism — but a test asserting the rendered constant
  asserts `1`, never `true`.
- **The two defaults have to agree**, for the reason
  [TypoScript and site sets](../architecture/typoscript-and-site-sets.md#there-is-no-double-parse-guard-and-that-is-deliberate)
  gives. Assert them in the delivery test of that extension, through both
  mechanisms, rather than by reading the two files.
- **Switching a script off does not make the markup work without one.** The
  markup is the contract an integrator's own script addresses, so it stays
  exactly as it is. What does have to be handled is markup that is only inert
  *because* the script rewrites it — `academic_study_plan` renders the item its
  filter clones as `<li hidden>` and has the module take the attribute off the
  clones.

One thing the switch cannot reach: an installation that overrides the template
keeps whatever that copy does, so its own `f:asset` lines load unconditionally
until they are wrapped as well. The same holds for a hand-written content
object — it assigns no `settings.assets.` block, both conditions are false, and
the element loses *both* assets. Say so in the changelog entry rather than
guarding it in the template.

`academic_study_plan` is the worked example:
`Tests/Functional/ContentElement/AcademicStudyPlanSiteSettingsTest.php` covers
both delivery mechanisms and every switch.

## A module finds its parts by attribute, never by class

A frontend module that queries `.filter`, `.module` or `.modal-trigger` has made
the stylesheet's vocabulary its API. Every markup change an installation needs
then breaks it, so the installation forks the module — and a fork stops
following the original on the day it is taken. Three of six analysed projects
had forked `academic_study_plan` for exactly that.

The contract is a `data-<extension>-*` attribute per part the module drives,
documented in the extension's own manual with the element each one belongs on,
and a partial per part so that an override replaces one of them rather than all
of them. Class names stay what a stylesheet selects.

What that costs, and what it buys:

- **Classes a module *writes* are not part of it.** `highlighted` and `open` are
  state the stylesheet reacts to; they are documented as written, not as looked
  up.
- **A rename is a deprecation.** Markup written for the previous version has to
  keep working, so each lookup falls back to the old class selector **per part**
  — a mixed override is the normal case during a migration. The fallback logs
  nothing: a console message reaches visitors, not integrators. It is announced
  in a `Deprecation-*.rst` with the removal version named.
- **The module exports its initialiser.** The `DOMContentLoaded` start stays,
  but a `testJs` fixture cannot be driven without a way to start the module on
  it, and node hands every test in a file the same module instance.
- **Two fixtures, not one.** One that carries only the attributes and none of
  the old classes, one that carries only the old classes. Removing the fallback
  has to turn the second red and removing the attribute lookup the first, or
  neither is proving anything.
- **The functional test asserts the same inventory.** The jsdom fixture is a
  copy of the rendered markup and a copy drifts; the counterpart that renders
  the real page and asserts every attribute is what keeps it honest. It is the
  rule [JavaScript tests](../testing/javascript-tests.md#where-a-fixture-comes-from)
  states for every fixture.

An override may put a trigger attribute on a bigger element than the extension
does — the module element itself rather than a button inside it. Resolve such a
pair *within* the part it belongs to rather than across the container, or the
first dialog of the page answers for every module. Two consequences of a
bigger trigger, both learned in review: a key pressed on a control *inside* it
belongs to that control, so an activation handler checks `event.target` before
it calls `preventDefault()`; and the module does not make that element
focusable, so the manual has to say that the override supplies `tabindex` and
`role` itself.

Two more, neither of them specific to the study plan:

- **`hidden` does not hide anything the stylesheet gives a `display` to.** The
  rule that makes the attribute work is the *user agent's*, and any author rule
  beats it. A module that collapses a part by setting `hidden` needs
  `.part[hidden] { display: none }` in the extension's own stylesheet, next to
  the rule it undoes — and neither jsdom nor either PHP suite computes style, so
  nothing but reading catches its absence. `bk2k/bootstrap-package` carries
  `[hidden] { display: none !important }`, which is why the dev instances hide
  it and a plain site does not.
- **A record's text substituted into markup is an injection.** Cloning a
  rendered prototype and `.replace()`-ing placeholders into its `outerHTML` was
  how the study plan built its filter, and a category title is written by an
  editor: `innerHTML` then parses the title. Substitute into the *clone's*
  attribute values and text nodes instead — neither can become markup — and
  treat a value that lands in a `style` attribute as a separate problem, because
  a `;` there opens a declaration of its own.

## Libraries come from the core

No library in this repository's frontend code is vendored, and the rule that
gets there is short: **before shipping a library, ask what the core already
ships, and what version it is.** TYPO3 delivers a set of JavaScript libraries
through import maps of its own, and a specifier that resolves through the core's
map costs the page nothing, is cached across every extension that uses it, and
is upgraded by a core update rather than by a commit here.

The two the profile editor uses both come from there:

| Library    | Version    | Package            | Specifier               |
|------------|------------|--------------------|-------------------------|
| CKEditor 5 | as shipped | `EXT:rte_ckeditor` | `@ckeditor/ckeditor5-*` |
| CropperJS  | 1.6.1      | `EXT:core`         | `cropperjs`             |

CropperJS is the instructive one. The core maps it for the backend's image
manipulation, and it is **1.6.1** on 13.4.34 and 14.3.6 — the same file on both,
verified byte for byte. That is the API before CropperJS became a set of custom
elements, so the profile image editor is written against 1.6: an options bag, a
`ready` callback, `getData()`, `getCroppedCanvas()`. Writing against the newer
API would have meant shipping the newer library, which is 44 KB reaching every
profile editing page for an interface the older one also has.

What follows from taking a library from the core:

- **The extension's `Configuration/JavaScriptModules.php` declares the package
  that maps it**, and maps nothing itself. `academic_persons_edit` declares
  `core`, which is what makes `cropperjs` resolvable on the page.
  `EXT:rte_ckeditor` is the exception the same file explains: its own dependency
  chain would expand several hundred backend entries into the inline import map,
  so its six bundles and their closure are mapped one by one.
- **The specifier is declared, not inferred.** The core's builds carry no type
  declarations, so `Resources/Private/TypeScript/frontend/_dependencies.d.ts`
  declares each module with the surface this repository actually calls, and no
  more. That declaration is the contract a core upgrade is checked against, and
  `Build/tests/resolve-hook.mjs` stubs the same specifier for the behavioural
  suite.
- **A stylesheet is not part of the deal.** The core delivers CropperJS's
  JavaScript to any page, and its CSS only inside the backend's own bundle. The
  cropper's appearance is therefore written in
  `packages/fgtclb/academic-persons-edit/Resources/Private/Scss/frontend/profile-editing.scss`,
  scoped to the editor's stage so it cannot reach a `cropper-` class another
  extension brought along. Check for the stylesheet as well as for the module.
- **A version the core ships is not automatically the right one** — but it is
  the first candidate, and the API difference has to be a real obstacle before
  a copy is shipped instead. Check the version, not the presence of a mapping.

Should a library ever have to be shipped after all, it is committed under
`Resources/Public/JavaScript/vendor/<library>/<version>/` with its licence file
beside it, published under a bare specifier by the extension's
`Configuration/JavaScriptModules.php`, and never imported by path.
`academic_partners` ships a mapping library and its plugin straight in
`Resources/Public/JavaScript/` and predates all of this. Files below
`Resources/Public/` that have no source under `Resources/Private/` are outside
the build gate by construction: `checkJsBuildClean` neither writes nor deletes
them.

## Artifacts are committed, and that makes a gate mandatory

`Resources/Public/JavaScript/**` and `Resources/Public/Css/**` are tracked files.
This is not a preference:

- **Composer distribution requires it.** `composer require` runs no node build.
- **TER requires it.** A TER upload is an archive of the working tree; there is
  no build hook.
- **Core does the same** — its shipped JavaScript is tracked and only the
  intermediates are ignored.

The sources stay out of the distributed package instead: every package's
`.gitattributes` marks `Resources/Private/Scss` and `Resources/Private/TypeScript`
as `export-ignore`, so they are absent from the archive composer downloads for a
`dist` install, while `Resources/Private/Language` and the rest still ship.

The consequence has to be stated plainly: **a committed artifact that no longer
matches its source is a silent defect.** It passes every review, ships to every
installation, and is only noticed when someone wonders why a fix had no effect.
`checkJsBuildClean` is therefore mandatory, not optional.

That gate cannot simply delete the output directories the way a single-extension
repository can. `academic_partners` keeps vendored files there that have no
source — a minified mapping library, its plugin, their stylesheets and their
images — and deleting them would report a permanently dirty tree. So
`node esbuild.mjs --list-outputs` derives the exact set of files the build would
write, from the same discovery the build itself uses, and the gate removes only
those. A source that stopped producing an output is still caught, as a deletion
in `git status`.

## Things that were got wrong once already

- **The build must not depend on the working directory.** esbuild writes the
  path of each input into the bundled CSS as a comment, relative to its working
  directory, so a build started from the repository root produced different
  bytes than one started from `Build/` — and the clean gate would have gone red
  for no reason. `absWorkingDir` is pinned to the repository root.
- **A git pathspec containing a wildcard must match the whole path.**
  `git status -- 'packages/*/*/Resources/Public'` matches nothing, because the
  leading-directory shortcut does not apply once a pattern contains a wildcard.
  The gate uses `'packages/*/*/Resources/Public/*'`.
- **`tsc` fails when it has nothing to check.** With no TypeScript anywhere it
  aborts with TS18003, a configuration error rather than a type error, which
  would make the suite red for a repository that is perfectly fine.
  `Build/typecheck.mjs` asks the build for the source list first and skips when
  it is empty.
- **npm packages ship PHP.** `flatted` carries a PHP port of itself, so
  `Build/node_modules` is excluded from `lintPhp`.
- **A bare specifier only type-checks when `Build/tsconfig.json` maps it.**
  Without a `paths` entry TypeScript cannot resolve
  `@fgtclb/<extension>/frontend/x.js` and falls back to whatever ambient
  `declare module` it finds. `academic_persons_edit` shipped such a declaration
  for each of its own modules for a while, so `typecheckJs` checked a
  hand-written copy of the exports that had already drifted from the real ones.
  Ambient declarations are for vendor specifiers only; an extension whose
  modules import each other gets a `paths` entry.

## See also

- [Development environment](environment.md) — the harness these suites run in.
- [Quality gates](quality-gates.md) — where they sit among the other gates.
- [Monorepo layout](monorepo-layout.md) — the packages and their
  `.gitattributes`.
- [Changelog and documentation](../workflow/changelog-and-documentation.md) —
  a changed asset path is user facing and needs an entry.
