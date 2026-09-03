# Workflow

How a change gets from a working copy into a release.

## The short version

1. Branch from the target branch, one branch per issue. A change worth
   planning gets an OpenSpec proposal first, named after the same issue.
2. Commit with the TYPO3 Core conventions and a **verified** issue reference.
3. Run every gate for **both** supported core versions, each after its own
   `composerUpdate`, before opening a pull request — and watch the pipeline
   afterwards.
4. Update the affected extension's `Documentation/` in the same change when it
   is user or integrator facing, with a changelog entry.
5. Backport to the other maintained branch as a separate, analysed change. The
   maintained targets are `main` and `2`, and nothing else.

## Pages

| Page                                                          | Contents                                                                                                            |
|---------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------|
| [Commit messages](commit-messages.md)                         | The format, the tags in use, subject and body limits, and where the issue reference goes.                           |
| [Pull requests](pull-requests.md)                             | Branch naming, the rebase merge model and what it implies, the pre-flight checklist, the repository rules.          |
| [Backporting](backporting.md)                                 | The maintained targets, and why a backport is analysed rather than cherry-picked — starting with a file-level diff. |
| [Changelog and documentation](changelog-and-documentation.md) | The two audiences, rendering the manual, and the changelog entry kinds.                                             |
| [Releasing](releasing.md)                                     | Versions across twelve packages, `bin/set-version` and `bin/release`, and the three-step publishing chain.          |
| [OpenSpec](openspec.md)                                       | The spec-driven planning workflow: artifacts, lifecycle, per-tool commands, and the conventions for changes here.   |

## See also

- [Quality gates](../development/quality-gates.md) — what has to pass, and how
  continuous integration stages it.
- [Dual core setup](../development/dual-core-setup.md) — why every gate runs
  twice.
- [Monorepo layout](../development/monorepo-layout.md) — the packages, their
  versions and their split repositories.
