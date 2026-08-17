# Development

Everything about getting a working copy running, what the repository is made of,
and what has to pass before a change is done.

## Quick start

The only requirement on the host is a container runtime — **podman** (preferred)
or **docker**. Everything else runs inside images.

```bash
# Install dependencies for the core version and PHP version you will test.
Build/Scripts/runTests.sh -t 12 -p 8.1 -s composerUpdate

# Gates.
Build/Scripts/runTests.sh -t 12 -p 8.1 -s cgl -n
Build/Scripts/runTests.sh -t 12 -p 8.1 -s phpstan
Build/Scripts/runTests.sh -t 12 -p 8.1 -s lintPhp

# Tests.
Build/Scripts/runTests.sh -t 12 -p 8.1 -s unit
Build/Scripts/runTests.sh -t 12 -p 8.1 -s functional

# All options.
Build/Scripts/runTests.sh -h
```

Everything has to pass for **both** supported core versions, each after its own
`composerUpdate` — see [Dual core setup](dual-core-setup.md). This branch
supports TYPO3 v12 and v13; `-t` defaults to `12`. The PHP version has to fit
the core version, so the second round is `-t 13 -p 8.2` rather than `-t 13`
with the same `-p 8.1`.

## Pages

| Page                                      | Contents                                                                                                                             |
|-------------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------|
| [Development environment](environment.md) | The `runTests.sh` wrapper, container runtimes, every suite and every option.                                                         |
| [Development instances](instances.md)     | The two ready-to-start DDEV instances, their backend and frontend accounts, and what the seed puts on which page.                    |
| [Monorepo layout](monorepo-layout.md)     | What each directory is for, the twelve extensions and their split repositories, extension keys, how a path package gets its version. |
| [Dual core setup](dual-core-setup.md)     | Why the installed dependency set must match `-t`, how to check which core is installed, and how tests are scoped per version.        |
| [Quality gates](quality-gates.md)         | Each gate and what it actually runs, PHPStan per core version, and how continuous integration stages them.                           |
| [Frontend assets](frontend-assets.md)     | The TypeScript and SCSS build, where sources live, how the result is loaded, and why the committed artifacts need a gate.            |

## See also

- [Architecture](../architecture/Index.md) — the rules the code itself follows.
- [Testing](../testing/Index.md) — both test suites and their conventions.
- [Workflow](../workflow/Index.md) — commits, pull requests, releases.
