# Developer documentation

Technical documentation for developers working **on** these extensions: how the
mono repository is structured, which rules apply to it, how to run the tooling
and how changes get released.

Documentation for people **using** an extension lives in that extension's own
`Documentation/` directory — for example
[`packages/fgtclb/academic-persons/Documentation/`](../packages/fgtclb/academic-persons/Documentation)
— is written in reStructuredText and is rendered to docs.typo3.org. There is one
such manual per extension; this tree is the single, repository-wide counterpart.

[`README.md`](../README.md) is the short overview,
[`CONTRIBUTING.md`](../CONTRIBUTING.md) the entry point that links here, and
[`AGENTS.md`](../AGENTS.md) the instruction file for AI coding agents.

## [Development](development/Index.md)

| Page                                                  | Contents                                                                                                  |
|-------------------------------------------------------|-----------------------------------------------------------------------------------------------------------|
| [Development environment](development/environment.md) | `runTests.sh`, container runtimes, every suite and option.                                                |
| [Development instances](development/instances.md)     | The two DDEV instances, the backend and frontend accounts, and what the seed contains.                    |
| [Monorepo layout](development/monorepo-layout.md)     | What each directory is, the packages and their split repositories, extension keys, path package versions. |
| [Dual core setup](development/dual-core-setup.md)     | Running against TYPO3 v13 and v14, and the rule that avoids false positives.                              |
| [Quality gates](development/quality-gates.md)         | Every gate and its configuration, PHPStan per core version, continuous integration.                       |
| [Frontend assets](development/frontend-assets.md)     | The TypeScript and SCSS build, and why the compiled result is committed.                                  |

## [Architecture](architecture/Index.md)

| Page                                                                       | Contents                                                                                                                      |
|----------------------------------------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------|
| [Core version aware code](architecture/core-version-aware-code.md)         | Version switches, when to split a class per core version, and the APIs that block a v15 migration.                            |
| [Dependency injection](architecture/dependency-injection.md)               | Service configuration, stateless services, the TYPO3 attributes that are safe on both core versions.                          |
| [Class design](architecture/class-design.md)                               | What the code base actually does with `final`, `readonly`, injection and data objects.                                        |
| [Database queries](architecture/database-queries.md)                       | The two query builder rules that were learned from released defects.                                                          |
| [Validation settings](architecture/validation-settings.md)                 | The one YAML driving both the backend FormEngine and the frontend edit form, and how it is overridden.                        |
| [Form data transformation](architecture/form-data-transformation.md)       | When a submitted value reaches the domain model, and why the shipped `profile` defaults lock the name fields.                 |
| [TypoScript and site sets](architecture/typoscript-and-site-sets.md)       | One physical copy of every TypoScript and page TSconfig file, delivered as a site set and as a static template.               |
| [Translation synchronization](architecture/translation-synchronization.md) | Why the profile translation sync goes through the DataHandler, its event chain, workspace rules and the contact4pages policy. |

## [Testing](testing/Index.md)

| Page                                                      | Contents                                                                                                     |
|-----------------------------------------------------------|--------------------------------------------------------------------------------------------------------------|
| [PHPUnit configuration](testing/phpunit-configuration.md) | Where the configuration comes from, the deliberate deviations, the strictness policy.                        |
| [Unit tests](testing/unit-tests.md)                       | Running them, discovery across all extensions, core version aware tests.                                     |
| [Functional tests](testing/functional-tests.md)           | Databases, why SQLite alone is not enough, loading extensions.                                               |
| [Fixture extensions](testing/fixture-extensions.md)       | Test-only extensions and how they are wired.                                                                 |
| [Testing helper](testing/testing-helper.md)               | The shared traits in `packages-dev/testing-helper/` and the trap each one exists for.                        |
| [Seed verification](testing/seed-verification.md)         | The manifest of the development seed, and the four checks that keep it, the YAML and the snapshots together. |
| [JavaScript tests](testing/javascript-tests.md)           | The `node --test` harness for the frontend TypeScript: the runner, the resolve hook and the stubs.           |

## [Workflow](workflow/Index.md)

| Page                                                                   | Contents                                                                       |
|------------------------------------------------------------------------|--------------------------------------------------------------------------------|
| [Commit messages](workflow/commit-messages.md)                         | The TYPO3 Core conventions as this repository applies them.                    |
| [Pull requests](workflow/pull-requests.md)                             | Branch naming, the rebase merge model, the pre-flight checklist.               |
| [Backporting](workflow/backporting.md)                                 | The maintained targets, and analysing a backport instead of cherry-picking it. |
| [Changelog and documentation](workflow/changelog-and-documentation.md) | The two audiences, rendering the manual, changelog entries per extension.      |
| [Releasing](workflow/releasing.md)                                     | Versions across twelve packages, the release scripts, the publishing chain.    |
| [OpenSpec](workflow/openspec.md)                                       | Spec-driven planning: artifacts, lifecycle, per-tool commands, conventions.    |

## Conventions of this documentation

- Every directory has an `Index.md` linking its pages; every page ends with a
  *See also* section.
- Pages document **why**, not just **what** — the reasoning is the part that does
  not survive in code.
- A change updates the page covering it in the same commit.
- Statements are verified against the repository rather than recalled. Where a
  page states something it could not verify, it says so.
- **Tables are always formatted.** Every cell is padded so the pipes line up, and
  the separator row is as wide as the widest cell in its column:

  ```markdown
  <!-- no -->
  | Header 1 | Header 2 |
  |----------|----------|
  | Value 1 with long text | Value 2 |

  <!-- yes -->
  | Header 1               | Header 2 |
  |------------------------|----------|
  | Value 1 with long text | Value 2  |
  ```

  Both render identically, which is exactly the problem: an unaligned table is
  invisible until someone edits it, and then the reflow touches every row and
  buries the actual change in the diff. Alignment markers (`:---`, `---:`,
  `:---:`) are kept and padded the same way.
