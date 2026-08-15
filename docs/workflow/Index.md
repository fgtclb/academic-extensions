# Workflow

How a change gets from a working copy into a release. This is branch `2`, the
`2.4.x-dev` line supporting TYPO3 v12 and v13; where something differs on
`main`, the pages say so.

## The short version

1. Branch from `2`, one branch per issue, with the target branch in the name.
2. Commit with the TYPO3 Core conventions and a **verified** issue reference.
3. Run every gate for **both** core versions of this branch, TYPO3 v12 and v13,
   each after its own `composerUpdate`, before opening a pull request — and
   watch the pipeline afterwards.
4. Update the affected extension's `Documentation/` in the same change when it
   is user or integrator facing, with a changelog entry under
   `Documentation/Changelog/2.4/`.
5. A change that applies to `main` as well goes there first and arrives here as
   a separate, analysed backport. The maintained branches are `main` and `2`,
   and nothing else.

## Pages

| Page                                                          | Contents                                                                                                           |
|---------------------------------------------------------------|--------------------------------------------------------------------------------------------------------------------|
| [Commit messages](commit-messages.md)                         | The format, the tags in use, subject and body limits, and where the issue reference goes.                          |
| [Pull requests](pull-requests.md)                             | Branch naming on `2`, the rebase merge model and what it implies, the pre-flight gates, the repository rules.      |
| [Backporting](backporting.md)                                 | What arrives here from `main`, and why it is analysed rather than cherry-picked — starting with a file-level diff. |
| [Changelog and documentation](changelog-and-documentation.md) | The two audiences, rendering the manual, and the changelog entry kinds.                                            |
| [Releasing](releasing.md)                                     | Versions across fourteen packages, `bin/set-version` and `bin/release`, and the three-step publishing chain.       |

## See also

- [Quality gates](../development/quality-gates.md) — what has to pass, and how
  continuous integration stages it.
- [Dual core setup](../development/dual-core-setup.md) — why every gate runs
  twice.
- [Monorepo layout](../development/monorepo-layout.md) — the packages, their
  versions and their split repositories.
