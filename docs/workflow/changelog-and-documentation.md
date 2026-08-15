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
| `Sitemap.rst`                                      | Generated sitemap page                                               |

`guides.xml` carries the version too, as `<project title="…" release="2.4.0"/>`
(`packages/fgtclb/academic-base/Documentation/guides.xml:15`). It is written by
`tailor set-version`, which `bin/set-version` runs for every package that has a
`Documentation/` folder — so it is not maintained by hand. See
[Releasing](releasing.md).

The extension key is not always the directory name: `academic-contact4pages`
ships `academic_contacts4pages`. The manual's `Index.rst` states the key, the
directory does not.

## Rendering the manual

Rendering goes through the harness like every other check, so it uses the same
image locally and in CI:

```bash
Build/Scripts/runTests.sh -s checkRstRenderingAll                    # all 12 packages
Build/Scripts/runTests.sh -s checkRstRenderingSingle academic-jobs   # one package
Build/Scripts/runTests.sh -s openDocumentation academic-jobs         # open the result
```

The image is `ghcr.io/typo3-documentation/render-guides:latest`
(`IMAGE_RSTRENDERING`). Neither `-t` nor `-p` matters here: the
renderer is a self-contained container and needs no vendor tree, which is why
`composerUpdate` is not a precondition for a documentation change.

The argument to `checkRstRenderingSingle` and `openDocumentation` is the
**directory** below `packages/fgtclb/`, not the extension key — the script
checks `packages/fgtclb/${extensionKey}` for existence
, in the `checkRstRenderingSingle` arm, despite the variable's name. For
`academic_contacts4pages` the argument is therefore `academic-contact4pages`.

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
`documentation-rendered/<ext>/Documentation-GENERATED-temp/`
, which is ignored at the root.

Two locations for one result is deliberate. The per-package one is where the
renderer has to write, because it renders one package at a time with that
package as its root. The collected one is what makes a whole-repository run
useful: after `checkRstRenderingAll`, `documentation-rendered/` holds one folder
per extension, and that single directory is what CI uploads as an artifact.

`openDocumentation` reads only the collected copy and opens
`documentation-rendered/<ext>/Documentation-GENERATED-temp/Index.html` with
`xdg-open`. It renders nothing itself: without a preceding render it prints the
two commands to run first, in `openDocumentation()`. It is
Linux-only, which the script marks with a `@todo`.

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

1. **This file only takes effect on the default branch, which is `main`.**
   Changing `pr-comment.yml` here changes nothing at all: not for the pull
   request that carries it, and not after it is merged, because the run that
   comments is always the one on `main`. Edit it there.
2. `github.event.workflow_run.pull_requests` is empty for a fork, so the pull
   request number cannot be read from the event. `ci.yml` writes it into a
   `pull-request-context` artifact *before* rendering, in the *Record the pull
   request number* and *Upload the pull request context* steps, so the comment
   lands on the right pull request even when the rendering failed.

## Changelog entries

Each package keeps its own changelog under
`packages/fgtclb/<ext>/Documentation/Changelog/<version>/`. All twelve carry
exactly `2.0`, `2.1`, `2.2`, `2.3` and `2.4` on this branch — the version
directory is the **minor** line, so a `2.4.1` fix is documented in `2.4/`. The
current line is `2.4`, so that is where a new entry goes; `3.0/` exists only on
`main` and must not be created here.

There are four kinds, distinguished by the file name prefix:

| File pattern        | Use for                                                       | Entries today |
|---------------------|---------------------------------------------------------------|---------------|
| `Breaking-*.rst`    | Changes requiring action from users of the extension          | 31            |
| `Deprecation-*.rst` | Functionality marked for removal, together with the migration | 1             |
| `Feature-*.rst`     | New functionality                                             | 28            |
| `Important-*.rst`   | Notable changes that are none of the above                    | 38            |

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
  (`.. _important-1785882200:`, a timestamp) and a slug derived from the title
  (`.. _breaking-adapted-frontend-editing-fluid-files:`). The numeric shape is
  the more common one — 75 entries against 15. A file name may additionally
  carry a reference number when the entry documents a foreign issue, as in
  `Important-88886-StrictLanguageFallbackForSelectedProfiles.rst`.

The body follows the template's section order: `Description`, `Impact`, and for
a breaking change additionally `Affected Installations` and `Migration`. A
trailing `.. index::` line closes the entry.
`packages/fgtclb/academic-persons-edit/Documentation/Changelog/2.4/Breaking-AdaptedFrontendEditingFluidFiles.rst`
is a full worked example: it uses sub-sections to collect several related
changes under one entry, and spells out why the alternative was rejected.

### The index files, and why nothing has to be registered

Adding an entry means adding one file. Nothing lists it:

* `Changelog/<version>/Index.rst` carries four `toctree` directives with
  `:glob:` over `Breaking-*`, `Feature-*`, `Deprecation-*` and `Important-*`
  (`packages/fgtclb/academic-base/Documentation/Changelog/2.4/Index.rst:13-51`).
  A hand-maintained list would go stale the first time somebody forgot it; a
  glob cannot.
* `Changelog/Changelog-2.rst` is the per-major landing page — on this branch
  that is the only one. It links the minor `Index` files by hand, newest first
  (`2.4/Index` … `2.0/Index`) — this one **does** need an edit, once, when a new
  minor directory is created.
* `Changelog/Changelog-2-combined.rst` is the same set of entries grouped by
  kind instead of by version, again through `:glob:` patterns
  (`Changelog/2.*/Breaking-*` and so on), and is linked from `Changelog-2.rst`
  as "Also available".

So the full cost of a new entry is one file; the full cost of a new minor
version is one directory, an `Index.rst` copied from the previous one, and one
line in `Changelog-<major>.rst`.

## Section adornments must match the title exactly

This is the one reST rule that has cost this repository time, so it gets its own
section.

A reST section title is delimited by an over/underline of punctuation
characters. Written correctly, the adornment is exactly as long as the title:

```rst
==============================================
Breaking: Adapted frontend editing Fluid files
==============================================
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
| Internal refactoring with identical behaviour                 | no    | —                         |
| Tests, CI, the harness, `docs/`                               | no    | —                         |

The two rows in bold type are the ones that get forgotten. **A template change
is user facing.** Projects copy partials into their site package and override
them, so a renamed partial, a changed variable name or a restructured section is
a breaking change for every project that did — even though no PHP signature
moved. The repository has documented exactly this repeatedly:

* `academic-contact4pages/Documentation/Changelog/2.1/Breaking-RemovedPartials.rst`
  (and the same entry in `academic-persons`, `academic-programs`,
  `academic-partners`, `academic-projects`)
* `academic-persons-edit/Documentation/Changelog/2.4/Important-AdaptedProfileImagePartial.rst`
* `academic-persons-edit/Documentation/Changelog/2.4/Breaking-AdaptedFrontendEditingFluidFiles.rst`

**A changed asset path is user facing** for the same reason: a project may
reference the file from its own TypoScript, its own template or its build.

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
