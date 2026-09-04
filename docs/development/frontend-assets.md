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
today: `academic-jobs` and `academic-persons-edit` ship TypeScript,
`academic-partners`, `academic-study-plan` and `academic-persons` ship both —
the latter the public profile's `frontend/profile.ts` and
`frontend/profile-detail.scss`, loaded by `Templates/Profile/Detail.html`.

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

## Vendored libraries

A library that is not written here and is not shipped by the TYPO3 core is
*vendored*: the built file is committed under
`Resources/Public/JavaScript/vendor/<library>/<version>/`, with its licence file
next to it. `academic_persons_edit` vendors Vue 3.5.42 and CropperJS 2.2.0 that
way; `academic_partners` vendors a mapping library and its plugin.

The rules, all of them learned from the two extensions that do it:

- **The directory carries the version.** `vendor/vue/3.5.42/` rather than
  `vendor/vue/`. An upgrade is a new directory plus one edit in the module that
  names it, so a page can never mix a cached old file with a new consumer, and
  the `?bust=` cache key of the import map is not needed for it.
- **A licence file sits beside it**, verbatim, under the name the project ships
  it as (`LICENSE`). It is what makes the redistribution lawful, and it is
  reviewed when the version changes.
- **One module names the file, relatively.** `TypeScript/frontend/vue.ts` is the
  only place `../vendor/vue/3.5.42/vue.esm-browser.prod.js` appears; every other
  module imports the wrapper through the import map. A relative import is
  correct here precisely because the path is versioned - the objection to
  relative imports below is about a *stale cached dependency*, which a versioned
  path cannot produce.
- **The wrapper declares the surface.** The vendored file has no type
  declarations, so the wrapper casts it once to a hand written interface holding
  what this repository actually calls. That interface is the contract an upgrade
  is checked against.
- **A library the core already ships is not vendored.** CKEditor 5 comes from
  `EXT:rte_ckeditor` and is mapped from there; Lit comes from `EXT:core`.
- **Vendored files are outside the build gate by construction**: they have no
  source under `Resources/Private/`, so `checkJsBuildClean` neither writes nor
  deletes them.

Vendoring is a cost - 172 KB of Vue reach every profile editing page - and it is
paid deliberately, per library, with the alternative named in the commit that
adds it.

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
