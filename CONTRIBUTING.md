# Contributing

Contributions are welcome. This is the central place to work on all `academic-*`
extensions and `category_types`: use this repository to report issues or to
contribute changes to the code or the documentation.

**Never send a change to a split repository.** Every extension is mirrored out
to its own read-only GitHub repository, and those mirrors are not a source of
truth — a change made there is lost on the next split.

This document is the entry point. It covers what you need to get started and
links to the detailed developer documentation in [`docs/`](docs/Index.md)
instead of repeating it.

## Table of contents

- [Getting started](#getting-started)
- [Quality gates](#quality-gates)
- [Running tests](#running-tests)
- [Code rules](#code-rules)
- [Commit messages](#commit-messages)
- [Pull request checklist](#pull-request-checklist)
- [Backporting](#backporting)
- [Documentation](#documentation)
- [Releasing](#releasing)
- [AI-assisted contributions](#ai-assisted-contributions)

## Getting started

Everything runs in containers through `Build/Scripts/runTests.sh`. The only
requirement on the host is **podman** (preferred) or **docker** — no PHP, no
composer, no node.

```bash
git clone git@github.com:fgtclb/academic-extensions.git
cd academic-extensions
Build/Scripts/runTests.sh -t 13 -p 8.2 -s composerUpdate
```

> **Important:** `-t` selects configuration only, it does **not** reinstall
> dependencies, and `-p` needs a `composerUpdate` just as much. Running a gate
> for one core version while the other one's dependency set is installed fails
> in confusing ways.

→ [Development environment](docs/development/environment.md) ·
[Monorepo layout](docs/development/monorepo-layout.md) ·
[Dual core setup](docs/development/dual-core-setup.md)

## Quality gates

The same gates run locally and in the GitHub Actions workflow, for every TYPO3
version the branch supports:

```bash
Build/Scripts/runTests.sh -s cgl -n      # coding guidelines, omit "-n" to fix
Build/Scripts/runTests.sh -s phpstan     # static analysis, per core version
Build/Scripts/runTests.sh -s lintPhp     # PHP linting
```

PHPStan is configured per core version and has a baseline per core version. A
growing baseline is a defect — prefer fixing the finding.

→ [Quality gates](docs/development/quality-gates.md)

## Frontend assets

TypeScript and SCSS sources live in the extension they belong to, below
`Resources/Private/TypeScript/` and `Resources/Private/Scss/`, and compile into
its `Resources/Public/`. Neither directory has to exist; adding one is picked up
without any configuration change.

```bash
Build/Scripts/runTests.sh -s buildJs             # compile, then commit the result
Build/Scripts/runTests.sh -s checkJsBuildClean   # prove the artifacts match, as CI does
Build/Scripts/runTests.sh -s lintTypescript -n   # eslint, omit "-n" to fix
Build/Scripts/runTests.sh -s typecheckJs         # tsc --noEmit
```

The compiled files are **committed**, because neither composer nor a TER upload
runs a node build. That makes it possible to change a source and forget to
rebuild, which nothing else would notice — so `checkJsBuildClean` is a required
gate rather than a convenience.

These suites are core version independent: no `-t`, no `composerUpdate`.

→ [Frontend assets](docs/development/frontend-assets.md)

## Running tests

```bash
Build/Scripts/runTests.sh -s unit
Build/Scripts/runTests.sh -s functional                 # SQLite, the default
Build/Scripts/runTests.sh -s functional -d postgres     # anything that writes
```

Both suites are **hard breaking**: deprecations, notices, warnings and risky
tests all fail the run. Never silence one to get a run green — fix the cause.

A test without an assertion still passes, so prove a new test can fail: break
what it covers, watch it go red, restore.

SQLite is the default and is not sufficient. Several defects here were invisible
on SQLite and only appeared on MySQL, MariaDB or PostgreSQL.

→ [Testing](docs/testing/Index.md) ·
[Unit tests](docs/testing/unit-tests.md) ·
[Functional tests](docs/testing/functional-tests.md) ·
[Testing helper](docs/testing/testing-helper.md)

## Code rules

- **A core version difference is a switch while it is one or two lines**, and a
  class split only when a whole class has to differ.
  → [Core version aware code](docs/architecture/core-version-aware-code.md)
- **Services are stateless.** New services must be; existing ones must not gain
  state. → [Dependency injection](docs/architecture/dependency-injection.md)
- **Never hand a raw array to `in()` or `notIn()`**, and build a constraint on
  the query builder that executes it. Both rules come from defects that reached
  a release. → [Database queries](docs/architecture/database-queries.md)
- Match the surrounding extension's existing style rather than introducing a
  second one alongside it. → [Class design](docs/architecture/class-design.md)

## Commit messages

TYPO3 Core conventions: `[TAG] ACE-NNN: Subject`, subject within 52 characters,
body wrapped at 72.

The `ACE-NNN` keys are YouTrack issues while the public issue and pull request
tracker is GitHub. **An issue reference is verified before it is written into a
commit**, never assumed.

→ [Commit messages](docs/workflow/commit-messages.md)

## Pull request checklist

Before opening a pull request, run every gate above and both test suites for
**each** TYPO3 version the branch supports, every one of them after its own
`composerUpdate`.

A green local run is not a substitute for the pipeline — watch it after pushing.

→ [Pull requests](docs/workflow/pull-requests.md)

## Backporting

The only maintained targets are `main` and `2`. Branch `2.2` is no longer
maintained and branch `1` is legacy; neither is a backport target.

A backport is **analysed, not cherry-picked** — the branches support different
TYPO3 versions. But check before adapting: diff each touched file between the
branches first, because they are frequently identical.

→ [Backporting](docs/workflow/backporting.md)

## Documentation

Two audiences, two places — both updated in the same commit as the change:

| Location                                     | Audience                   | Format   | Scope           |
|----------------------------------------------|----------------------------|----------|-----------------|
| `packages/fgtclb/<extension>/Documentation/` | Users and integrators      | reST     | per extension   |
| [`docs/`](docs/Index.md)                     | Developers and maintainers | Markdown | repository wide |

```bash
# Render every extension's manual, as CI does. Must pass without errors.
Build/Scripts/runTests.sh -s checkRstRenderingAll

# Render one, then open it.
Build/Scripts/runTests.sh -s checkRstRenderingSingle academic-persons
Build/Scripts/runTests.sh -s openDocumentation academic-persons

# Check every Markdown file, as CI does. Omit "-n" to fix what can be fixed.
Build/Scripts/runTests.sh -s lintMarkdown -n
```

User facing changes need a changelog entry below the extension's
`Documentation/Changelog/<version>/` — `Breaking-*.rst`, `Deprecation-*.rst`,
`Feature-*.rst` or `Important-*.rst`. Templates are in
`Build/Documentation/Templates/`.

One formatting rule that is easy to get wrong and is not caught by a gate: a
reST section over- or underline must match its title length **exactly**. The
Markdown side is caught — `lintMarkdown` pads the tables, strips trailing
whitespace and reports links that do not resolve.

→ [Changelog and documentation](docs/workflow/changelog-and-documentation.md)

## Releasing

Maintainers only. Every package carries its own version, and a release is
applied across the repository with `bin/set-version` and orchestrated with
`bin/release`. Publishing to the TER happens per extension, one step later in
the chain, from the split repositories.

→ [Releasing](docs/workflow/releasing.md)

## AI-assisted contributions

Whoever submits a change is responsible for it in full, exactly as if every line
had been typed by hand. The quality bar does not move: every gate applies
unchanged, and code that has only been reasoned about is not code that has been
run.

Tools are not authors. A change is never attributed to one — no
`Co-authored-by:` trailer for a model or a tool, no `AI-assisted:` trailer, no
"Generated with …" notice, in a commit, a pull request, an issue or a file.
Everything is written in the maintainer's voice.

[`AGENTS.md`](AGENTS.md) is the instruction file for AI coding agents, with
`CLAUDE.md`, `GEMINI.md` and `.github/copilot-instructions.md` as symlinks to
it. It links into this document and into [`docs/`](docs/Index.md) rather than
repeating them, and adds what applies to agent work: the above, scratch files
below the git-ignored `.agent/`, and the gate matrix including the dual core
rule.

→ [Agent instructions](AGENTS.md)
