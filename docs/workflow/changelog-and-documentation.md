# Changelog and documentation

Documentation in this repository has two audiences, and they never share a file.
The split is the same one every TYPO3 extension makes — a shipped manual and
notes for the people who work on the code — but a mono repository moves the
first half **into each package** and keeps only the second half at the root:

| Location                               | Audience                   | Format   | Scope           |
|----------------------------------------|----------------------------|----------|-----------------|
| `packages/fgtclb/<ext>/Documentation/` | users and integrators      | reST     | per extension   |
| `docs/`                                | developers and maintainers | Markdown | repository wide |

That is the important difference from a single-extension repository. There is no
one manual for "the academic extensions": there are twelve, one per package, and
each is split out to its own read-only repository and published on its own. A
sentence about `academic_jobs` belongs in
`packages/fgtclb/academic-jobs/Documentation/`, not in a shared place, because a
reader of the split repository would never see the shared place.

`docs/` is the opposite: it describes the repository — the harness, the branch
model, the release chain — none of which survives the split, and none of which a
site integrator needs.

Both are written as part of the change that makes them necessary. A changelog
entry added a week later is a changelog entry written from memory.

## What a package manual looks like

Every package under `packages/fgtclb/` ships the same skeleton
(`packages/fgtclb/academic-base/Documentation/` is the reference):

| File or folder                                     | Purpose                                                              |
|----------------------------------------------------|----------------------------------------------------------------------|
| `Index.rst`                                        | Entry page: extension key, package name, release, license, card grid |
| `guides.xml`                                       | Renderer configuration: theme, project title and release, edit links |
| `Changelog/`                                       | The per-version changelog tree, see below                            |
| `Introduction/`, `Installation/`, `KnownProblems/` | The narrative sections                                               |
| `Upgrade/`                                         | The upgrade steps per path, see below                                |
| `Sitemap.rst`                                      | Generated sitemap page                                               |

Extensions add chapters of their own on top of that skeleton: `Configuration/`
and `Templates/` in most of them, and `ProfileEditing/` in
`academic-persons-edit`. Every manual has the upgrade chapter and, where the
extension ships plugins or dispatches events, the chapters for integrators and
developers described below.

`guides.xml` carries the version too, as `<project title="…" release="3.0.0"/>`
(`packages/fgtclb/academic-base/Documentation/guides.xml:16`). It is written by
`tailor set-version`, which `bin/set-version` runs for every package that has a
`Documentation/` folder — so it is not maintained by hand. See
[Releasing](releasing.md).

It also carries the links docs.typo3.org puts on every page. "Edit on GitHub"
is built from `edit-on-github`, `edit-on-github-branch` and
`edit-on-github-directory`, and has to lead to this mono repository, to the
branch the file lives on and to the package's own `Documentation/` directory.
`project-repository` names the split repository and `project-home` the
extension in the TER. A unit test holds all five, see
[Unit tests](../testing/unit-tests.md#the-links-of-the-manuals).

The extension key is not always the directory name: `academic-contact4pages`
ships `academic_contacts4pages`. The manual's `Index.rst` states the key, the
directory does not.

### Links into the manual of academic_base

A link from another manual into the manual of `academic_base` is an interlink,
with the composer name of the package as its shortcode and a label of that
manual as its target:

```rst
Each finding is described in the :ref:`upgrade check
<fgtclb/academic-base:upgrade-check>` of :guilabel:`academic_base`.
```

The renderer resolves it from the inventory docs.typo3.org publishes with the
manual of `main`, `objects.inv.json`, and logs a warning for a label it does
not find there, which fails the render, see
[The render is a gate](#the-render-is-a-gate-not-an-artifact-producer). An
absolute link is checked by nothing. Until ACE-937 the manual of
`academic_base` was not rendered at all, and the 88 absolute links into it
answered 404 without a failing gate.

The inventory is the published one, not the one of the change: a label that
the same change adds to the manual of `academic_base` resolves only once
docs.typo3.org has rendered `main` again, after the merge. Link a label that
is already published, or add the link in a later change. Renaming or removing
a label that another manual links renders green in its own change, because
the published inventory still holds it, and turns the next pull request red
once `main` is rendered again. Add the new label next to the old one, move the
links once docs.typo3.org has rendered `main`, and remove the old label last.

The shortcode resolves against the manual of `main` on every branch. A version
branch cut from `main` keeps linking that manual through its interlinks, unlike
the absolute links, which `bin/set-version` moves to the manual of the new
version line, see [below](#absolute-links-between-the-manuals). Its render
depends on the labels published there. The links between the other manuals
are still absolute.

### Absolute links between the manuals

An absolute link from a package file into a manual of this repository, a manual
page that links the page of another manual or a settings file that names the
page documenting it, names the manual of the version line the file ships with:
`https://docs.typo3.org/p/<composer name>/main/en-us/...` on `main`, and the
major and minor version instead of `main` on a version branch, `2.4` on branch
`2`. `bin/set-version` writes that version with
`Build/Scripts/manualLinkVersion.php`, so a minor bump on a version branch and
a branch cut move the links along, and a unit test fails for a link that names
another version, see
[Unit tests](../testing/unit-tests.md#the-links-into-the-manuals-of-the-repository).
The `README.md` of a package is read without its link table, the lines that
start with `|`, which `Build/Scripts/readmeManualLink.php` writes and pads and
a test of its own checks, see
[Unit tests](../testing/unit-tests.md#the-link-table-of-the-readmes). A link
in the text below the table follows the version line like any other.
Write a new link with the version of the branch it is written on. Branch `2`
was cut from `main` with seven links into the 3.0 manuals, which nothing
reported until ACE-941.

The 88 interlinks `fgtclb/academic-base:` are not absolute links and are
neither checked nor written. They resolve against the inventory of `main` on
every branch, so a branch `3` cut from `main` keeps linking the `main` manual
of `academic_base` through them. Resolving them against the inventory of the
version line would be the way out, which is not possible before the manual of
`2.4` is rendered.

Two kinds of file are left out, and their links are not checked:

- The `Documentation/Changelog/` folders and the `Documentation/Upgrade/`
  pages. They are shared between the branches: `Changelog/2.4/` and
  `From23To24.rst` are byte-identical on `main` and `2`, and a released folder,
  2.0 to 2.3, is never edited. No single version is right on both branches, so
  a link there keeps the manual it was written with, on every branch and after
  a cut. Three entries of `Changelog/3.0/` and six upgrade pages to 3.0 link
  the manuals of `main` today, and a branch `3` cut from `main` keeps those
  links.
- The render output, `vendor/` and `node_modules/` trees and binary files.

### The upgrade chapter

Every manual carries `Upgrade/` (ACE-910): an index page with the table of
which page applies to which installed version and what to do before any
upgrade, and one page per upgrade path: `From23To24.rst`, `From24To30.rst` and
`From23To30.rst`, the last one for installations that skip 2.4 and go straight
to the 3.0 state. A page lists the steps of its own extension in the order they
have to be done, from the package update over the database, the upgrade
wizards and the configuration to the templates and the code, and links the
changelog entry of each step. It names an extension that has to be upgraded
first or together, because there is no guide across the extensions: every
package is split out and rendered on its own.

The changelog stays the record of what changed. The upgrade page is the order
to apply it in, so a new Breaking or Deprecation entry of a release adds its
step to the pages of the upgrade paths that release belongs to, in the same
change. `From23To24.rst` is byte-identical on branch `2`, an edit of it is made
on both branches.

### Plugins and events

Two chapters have a fixed shape in every manual that has something to put in
them (ACE-909):

| Folder                               | Holds                                                                                                                        |
|--------------------------------------|------------------------------------------------------------------------------------------------------------------------------|
| `Integrator/Index.rst`               | "For integrators": how plugin settings meet TypoScript and site settings, and a table of the plugins with icon, title, CType |
| `Integrator/<Plugin>.rst`            | One page per plugin: what it shows and when to use it, then every FlexForm option as a `confval`, grouped by the backend tab |
| `Developers/Index.rst`               | "For developers": a table of every PSR-14 event the extension dispatches or owns, its own and those of another extension     |
| `Developers/Events/<EventClass>.rst` | One page per event dispatched or owned: when it is dispatched, what a listener reads and changes, an example, its changelog  |

An event dispatched by several extensions has a page in each of them, with an
example that fits that extension's plugin, and the manual of the extension that
owns it describes it in general. A new plugin, a new FlexForm field or a new
event therefore changes one of these pages in the same change. A `confval`
needs a `:name:` unique within its manual, so it is prefixed with the plugin.

Option text belongs on the plugin page, not in `Configuration/`, which keeps
the site settings, the TypoScript and the table of what each set adds. The
table of events on the Extension points page of academic_base stays where it
is, `ExtensionPointTest` reads it.

Every manual writes these pages the same way:

- A plugin page starts with the field list `:CType:` (a double backtick
  literal), `:Plugin:` for an Extbase plugin (extension name, plugin name,
  controller action), `:Set:`, and `:Templates:` where the manual has a
  Templates chapter.
- The section with the options of a plugin is titled `Settings` and carries a
  label. A `confval` writes `:Default: empty` for an empty default and a
  literal value such as ``` ``10`` ``` in double backticks. Its `:type:` is
  what the editor sees: `text` for an input, `number` only for an input with
  integer evaluation.
- A `:confval:` reference in running prose carries the backend label as its
  link text, ``:confval:`Startingpoint <partner-list-pages>` ``.
- Icons and figures are referenced as `/Images/...`, absolute from
  `Documentation/`. An icon in a table cell is a `figure`, never an `image`,
  see [below](#an-image-in-a-table-cell-is-a-figure).
- The events table of `Developers/Index.rst` has the columns `Event`,
  `Dispatched`, `What a listener can change` and `Owner`, an event page closes
  with `Rules and pitfalls` and `Introduced in`, and every hidden toctree of
  the two index pages uses `:titlesonly:`.

Images live in `Documentation/Images/`, because a manual is rendered from its
own folder and split out with its package. `Images/Icons/` holds copies of the
plugin icons with `currentColor` replaced by the grey `#737373`, which reads on
the light and the dark theme of docs.typo3.org, and `Images/Screenshots/`
holds screenshots of the styled development instances, cropped to the plugin
and taken only where an option visibly changes the output. They are PNG, apart
from a map, whose tiles compress far better as JPEG. Both are
shipped with the package, so a screenshot earns its place. The icons are Font
Awesome Free, which the Third-party icons section of the Introduction credits.

## Rendering the manual

Rendering goes through the harness like every other check, so it uses the same
image locally and in CI:

```bash
Build/Scripts/runTests.sh -s checkRstRenderingAll                    # all 12 packages
Build/Scripts/runTests.sh -s checkRstRenderingSingle academic-jobs   # one package
Build/Scripts/runTests.sh -s openDocumentation academic-jobs         # open the result
```

The image is `ghcr.io/typo3-documentation/render-guides:latest`
(`IMAGE_RSTRENDERING`), the renderer of docs.typo3.org. Both rendering suites
pull it before they render, so a local run renders with the same release as CI
and docs.typo3.org, see
[Container images](../development/environment.md#container-images).
Neither `-t` nor `-p` matters here: the renderer is a self-contained container
and needs no vendor tree, which is why `composerUpdate` is not a precondition
for a documentation change.

The argument to `checkRstRenderingSingle` and `openDocumentation` is the
**directory** below `packages/fgtclb/`, not the extension key — the script
checks `packages/fgtclb/${extensionKey}` for existence, in the
`checkRstRenderingSingle` arm, despite the variable's name. For
`academic_contacts4pages` the argument is therefore `academic-contact4pages`.
Both suites reject an argument that contains a `/`, or is `.` or `..`,
before anything else. `executeRstRendering()` removes and recreates
`documentation-rendered/<argument>/Documentation-GENERATED-temp/`, and such
an argument would point it outside `documentation-rendered/`.

### Where the output lands

`executeRstRendering()` mounts one package into the container and runs the
renderer with the package as both configuration and input directory:

```
--fail-on-log --fail-on-error --no-progress --config=Documentation Documentation
```

The renderer writes into the package itself, at
`packages/fgtclb/<ext>/Documentation-GENERATED-temp/`, which every package's
`.gitignore` excludes (`packages/fgtclb/academic-base/.gitignore:9`). The script
then copies that tree to
`documentation-rendered/<ext>/Documentation-GENERATED-temp/`, which is ignored
at the root.

Two locations for one result is deliberate. The per-package one is where the
renderer has to write, because it renders one package at a time with that
package as its root. The collected one is what makes a whole-repository run
useful: after `checkRstRenderingAll`, `documentation-rendered/` holds one folder
per extension, and that single directory is what CI uploads as an artifact.

`openDocumentation` reads only the collected copy and opens
`documentation-rendered/<ext>/Documentation-GENERATED-temp/Index.html` with
`xdg-open`. It renders nothing itself: without a preceding render,
`openDocumentation()` prints the two commands to run first. It is Linux-only,
which the script marks with a `@todo`.

There is no watch mode in this repository. The loop is edit, render the single
package, reopen.

## The render is a gate, not an artifact producer

The `documentation` job of the CI workflow runs exactly the command above:

```yaml
- name: "Render documentation of all extensions"
  run: "Build/Scripts/runTests.sh -b docker -s checkRstRenderingAll"
```

Because `executeRstRendering()` passes **`--fail-on-log --fail-on-error`**, the
renderer exits non-zero on a warning, not only on a hard error, and the job
fails. A broken cross-reference, an unknown directive or a malformed table is
therefore a red pull request, not a cosmetic remark. The comment above the step
says so in the workflow itself.

The tag `latest` also means a new release of the renderer can turn every
pull request and the nightly run red at once, without a change here. A new
release is the first thing to check when the documentation job of an unrelated
change fails: render again locally, which pulls the same release, and compare
the `Renderer:` line the run prints with the one in the job log. The release of
October 2026 started to warn about a manual without an interlink shortcode,
which every `guides.xml` now declares and a repository check keeps (see
[the links of the manuals](../testing/unit-tests.md#the-links-of-the-manuals)).

The job is independent of the source gates — it has no `needs:` — so a
documentation-only change gets its answer without waiting for `cgl`, `phpstan`,
`lint`, `unit` and the functional matrix. It also runs no `composerUpdate`,
consistent with the renderer needing no vendor tree.

## The rendered documentation on the pull request

`.github/workflows/pr-comment.yml` posts one comment per pull request linking
the `documentation` artifact of the run, and updates that same comment on every
push instead of adding a new one (it finds its own comment by the marker
`<!-- rendered-documentation -->`).

It is a **separate workflow on the `workflow_run` event**, and that is not an
accident:

* A pull request from a **fork** gets a read-only `GITHUB_TOKEN` and no secrets.
  Commenting needs `pull-requests: write`, so a comment step inside `ci.yml`
  would work for branches in this repository and silently fail for exactly the
  external contributors it is meant to serve.
* `workflow_run` fires when `ci.yml` finishes and runs in the context of the
  **default branch** of this repository, not the fork. Its token can write even
  though the token of the run that triggered it could not, and no code from the
  pull request is checked out — which is what makes granting write permission
  safe — see its `on:` block and its `permissions:`.
* `pull_request_target` is deliberately **not** used. It also carries a write
  token, but running the pull request's own code under it is a documented way to
  leak write access and secrets.

Two consequences follow, and both surprise people:

1. **This file only takes effect once it is on the default branch.** Changing
   `pr-comment.yml` inside a pull request does not change the behaviour of that
   pull request — the run that comments is the one on `main`. Test a change to
   it by merging it, not by pushing it.
2. `github.event.workflow_run.pull_requests` is empty for a fork, so the pull
   request number cannot be read from the event. `ci.yml` writes it into a
   `pull-request-context` artifact *before* rendering, in the *Record the pull
   request number* and *Upload the pull request context* steps, so the comment
   lands on the right pull request even when the rendering failed.

## Changelog entries

Each package keeps its own changelog under
`packages/fgtclb/<ext>/Documentation/Changelog/<version>/`. All twelve currently
carry `2.0`, `2.1`, `2.2`, `2.3`, `2.4` and `3.0` — the version directory is the
**minor** line, so a `3.0.1` fix is documented in `3.0/`.

There are four kinds, distinguished by the file name prefix:

| File pattern        | Use for                                                       | Entries today |
|---------------------|---------------------------------------------------------------|---------------|
| `Breaking-*.rst`    | Changes requiring action from users of the extension          | 69            |
| `Deprecation-*.rst` | Functionality marked for removal, together with the migration | 1             |
| `Feature-*.rst`     | New functionality                                             | 40            |
| `Important-*.rst`   | Notable changes that are none of the above                    | 86            |

Templates for all four live in `Build/Documentation/Templates/`
(`Changelog-Breaking.rst`, `Changelog-Deprecation.rst`, `Changelog-Feature.rst`,
`Changelog-Important.rst`). Copy the matching one, rename it, replace the
placeholder text.

Two deviations from the template are the actual house style, so follow the
existing entries rather than the template file:

* **Drop the `.. include:: /Includes.rst.txt` line.** The templates open with
  it; no shipped entry uses it, and there is no `Includes.rst.txt` anywhere in
  the repository.
* **Give the anchor a real name.** The template's `.. _feature-0000000000:` is a
  placeholder. Two shapes occur in the tree: a numeric one
  (`.. _important-1785874800:`, a timestamp) and a slug derived from the title
  (`.. _breaking-removed-file-upload-converter:`). The numeric shape is the more
  common one. A file name may additionally carry a reference number when the
  entry documents a foreign issue, as in
  `Important-88886-StrictLanguageFallbackForSelectedProfiles.rst`.

The body follows the template's section order: `Description`, `Impact`, and for
a breaking change additionally `Affected Installations` and `Migration`. A
trailing `.. index::` line closes the entry.
`packages/fgtclb/academic-base/Documentation/Changelog/3.0/Breaking-RemovedFileUploadConverter.rst`
is a full worked example, including a `list-table` mapping old options to their
replacements.

### The index files, and why nothing has to be registered

Adding an entry means adding one file. Nothing lists it:

* `Changelog/<version>/Index.rst` carries four `toctree` directives with
  `:glob:` over `Breaking-*`, `Feature-*`, `Deprecation-*` and `Important-*`
  (`packages/fgtclb/academic-base/Documentation/Changelog/3.0/Index.rst:13-51`).
  A hand-maintained list would go stale the first time somebody forgot it; a
  glob cannot.
* `Changelog/Changelog-<major>.rst` is the per-major landing page. It links the
  minor `Index` files by hand (`3.0/Index`) — this one **does** need an edit,
  once, when a new minor directory is created.
* `Changelog/Changelog-<major>-combined.rst` is the same set of entries grouped
  by kind instead of by version, again through `:glob:` patterns
  (`Changelog/3.*/Breaking-*` and so on), and is linked from
  `Changelog-<major>.rst` as "Also available".

So the full cost of a new entry is one file; the full cost of a new minor
version is one directory, an `Index.rst` copied from the previous one, and one
line in `Changelog-<major>.rst`.

## Section adornments must match the title exactly

This is the one reST rule that has cost this repository time, so it gets its own
section.

A reST section title is delimited by an over/underline of punctuation
characters. Written correctly, the adornment is exactly as long as the title:

```rst
=======================================
Breaking: Removed `FileUploadConverter`
=======================================
```

An adornment that is **longer** than its title is not a reST error. The renderer
accepts it and the page comes out looking right, so nothing in the pipeline
objects — `--fail-on-log --fail-on-error` cannot fail on something the parser
never complains about. What it produces is a file that disagrees with itself,
which is then copied as the template for the next entry, and the drift spreads.

It happens for a boring reason: the title gets edited after the adornment was
typed, and a shortened title leaves an over-long ruler behind.

There is no gate for this, so check it before committing. Every `.rst` file in
every package is currently consistent, and this keeps it that way:

```bash
find packages/fgtclb -path '*/Documentation/*' -name '*.rst' -exec awk '
  /^[=~^-]+$/ && length($0) >= 3 && prev != "" && prev !~ /^[=~^-]+$/ &&
  length(prev) != length($0) {
      printf "%s:%d: title %d, adornment %d\n", FILENAME, FNR, length(prev), length($0)
  }
  { prev = $0 }' {} +
```

It compares every underline with the title directly above it — the shape the
mistake takes in practice — and prints nothing when they all match. The
character class covers the three adornment characters actually used here (`=`,
`^`, `-`). An overline is not checked by it, but an overline has to be identical
to its underline, which is visible at a glance in the same three lines.

Restrict the `find` to the package you touched while writing.

## An image in a table cell is a figure

The plugin table of an integrator chapter shows the icon of each plugin in its
first column. Written as an `image` directive, that icon is dropped by the
renderer of docs.typo3.org. render-guides 0.46.0 brought phpDocumentor/guides
1.11, which turns most directives into their node while compiling a page instead
of while parsing it, and the compiler does not walk into the rows of a table.
The cell comes out empty and nothing is logged, so `--fail-on-log` has nothing
to fail on. The icon column of nine manuals was empty on docs.typo3.org until
ACE-935, verified with render-guides 0.46.0 and 0.47.2.

A `figure` without a caption renders the same `<img>`, with its `:width:` and
its `:alt:`, on 0.45.0 and on 0.47.2, because the theme of the renderer still
creates a figure while parsing:

```rst
    *   -   ..  figure:: /Images/Icons/persons.svg
                :width: 32px
                :alt: Icon of the profile list
        -   :ref:`Person List <integrator-person-list>`
```

The image is not the only directive the cell loses. What a cell of a
`list-table` or a `csv-table` renders on 0.45.0 and on 0.47.2:

| In a table cell                              | 0.45.0           | 0.47.2                    |
|----------------------------------------------|------------------|---------------------------|
| `image`, SVG or PNG, with or without options | rendered         | dropped                   |
| `note`, `tip`, `container`                   | rendered         | its text, without the box |
| `figure`, `code-block`, `versionadded`       | rendered         | rendered                  |
| the reference to an image substitution       | its name as text | its name as text          |

A substitution therefore is no way out either: `..  |icon| image::` works in a
paragraph, but is not replaced in a table cell. The options of a directive in
a grid table cell are not parsed on either release, they come out as a field
list, and 0.47.2 drops the image as well.

No manual has a directive other than the figures in a table cell today.
`DocumentationTableCellDirectiveTest` keeps it that way: it refuses every
directive in a `list-table`, a `t3-field-list-table` or a `csv-table` but the
three that render on both releases, and
every substitution reference in a cell, wherever it is defined, see
[Unit tests](../testing/unit-tests.md#directives-in-table-cells). A directive
that is to be allowed is rendered in a cell with both releases first. The
render is no check for it: `runTests.sh` renders with the `latest` image the
container engine has cached, which may be older than 0.46.0 and still show
the cell as written, and a newer one drops it without a word.

## When a change needs a changelog entry

The test is not "did PHP change" — it is **can a user or integrator notice**.
Everything a project can override, configure or depend on is user facing, which
in a TYPO3 extension is considerably more than the public PHP API:

| Change                                                        | Entry | Kind                      |
|---------------------------------------------------------------|-------|---------------------------|
| Public PHP API added, changed or removed                      | yes   | `Feature` / `Breaking`    |
| A **Fluid template, layout or partial** changed or removed    | yes   | `Breaking` or `Important` |
| An **asset path** changed (`Resources/Public/…`)              | yes   | `Breaking` or `Important` |
| TCA, FlexForm or plugin registration changed                  | yes   | usually `Breaking`        |
| A database column changed, requiring a schema update          | yes   | `Important`               |
| TypoScript or extension configuration option added or removed | yes   | `Feature` / `Breaking`    |
| A new or raised dependency                                    | yes   | `Important`               |
| A **defect fix that changes what a page renders**             | yes   | `Important`               |
| Internal refactoring with identical behaviour                 | no    | —                         |
| Tests, CI, the harness, `docs/`                               | no    | —                         |

The three rows in bold type are the ones that get forgotten. **A template change
is user facing.** Projects copy partials into their site package and override
them, so a renamed partial, a changed variable name or a restructured section is
a breaking change for every project that did — even though no PHP signature
moved. The repository has documented exactly this repeatedly:

* `academic-contact4pages/Documentation/Changelog/2.1/Breaking-RemovedPartials.rst`
  (and the same entry in `academic-persons`, `academic-programs`,
  `academic-partners`, `academic-projects`)
* `academic-persons-edit/Documentation/Changelog/2.4/Important-AdaptedProfileImagePartial.rst`
* `academic-persons-edit/Documentation/Changelog/3.0/Breaking-ReplacedProfileEditingPlugin.rst`

**A changed asset path is user facing** for the same reason: a project may
reference the file from its own TypoScript, its own template or its build.

**A defect fix is user facing when its output is.** The surfaces above are what
usually triggers an entry, but none of them is the test. A fix can touch nothing
but PHP — no template, no option, no column, no signature anybody calls — and
still change every page an installation serves: a list that comes out in a
different order, a link target that is suddenly dialable, a row that used to
render empty. Somebody sees that after an update and goes looking for what caused
it, and the changelog is where they look.
`academic-persons/Documentation/Changelog/2.4/Important-PaginatedSelectionKeepsItsOrder.rst`
is that shape exactly: a controller fix, no surface touched, one `Important`
entry.

What this rules out is the reasoning "it is a bugfix, so an integrator has
nothing to do". Having something to do is not the criterion — noticing is. The
question to answer before deciding against an entry is whether an installation
that changed nothing of its own renders something different after the update. If
it does, the entry is `Important`.

When in doubt, write the entry. An `Important` entry that nobody needed costs a
paragraph; a missing `Breaking` entry costs somebody an afternoon after an
update.

## See also

- [Releasing](releasing.md) — how the version reaches `guides.xml`, and the
  three-step publishing chain.
- [Backporting](backporting.md) — where the changelog entry for a backport goes.
- [Commit messages](commit-messages.md) — the message that accompanies the entry.
- [Pull requests](pull-requests.md) — the gates a documentation change passes.
- [Development environment](../development/environment.md) — the harness the
  renderer runs in.
- `Build/Scripts/runTests.sh` — `executeRstRendering()` and the
  `checkRstRendering*` and `openDocumentation` suites.
- `.github/workflows/ci.yml` — the `documentation` job.
- `.github/workflows/pr-comment.yml` — the artifact link comment.
- `Build/Documentation/Templates/` — the four changelog entry templates.
