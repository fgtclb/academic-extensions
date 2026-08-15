# Pull requests

Every change reaches a maintained branch through a pull request on GitHub,
`fgtclb/academic-extensions`. There is no second route: the branches that
matter are covered by a repository ruleset that requires one, and direct pushes
to them are rejected.

## Branch naming

The established pattern is derived from the merged history and from the branches
that currently exist on the remote:

| Purpose               | Pattern                | Real example                     |
|-----------------------|------------------------|----------------------------------|
| Work on `main`        | `ace-<nnn>-<slug>`     | `ace-358-text-column-defaults`   |
| Backport to `2`       | `ace-<nnn>-backport-2` | `ace-236-backport-2`             |
| Backport to `2`, alt. | `ace-<nnn>-<slug>-2`   | `ace-358-text-column-defaults-2` |
| Documentation work    | `ace-<nnn>-<slug>`     | `ace-389-docs`                   |

All lowercase, words separated by hyphens, the issue number written in full and
in the same order as in the commit subject. The slug describes the change, not
the file it touches, so a branch name is recognizable next to the commit it
carries.

Both backport spellings are in active use — `-backport-2` and a plain `-2`
suffix. Either is fine; what matters is that the target branch is visible in the
name, because a pull request against `2` looks exactly like one against `main`
in a notification.

Older branches on the remote use a `task/<slug>` prefix, for example
`task/image-fallback-metadata` and
`task/ace-109-extbase-propertymapper-bases-extension-settings`. That prefix is
history and is not used for new work.

## One change, two pull requests

A change that applies to both maintained branches is **two** pull requests, not
one with two commits. The pull request against `main` goes first and is merged
first; the backport follows. The merged history shows the pairs consistently —
`#453` on `main` and `#455` on `2` for ACE-236, `#451` and `#452` for ACE-382,
each pair minutes apart and in that order.

The order is not cosmetic. `main` is where the change is reviewed on its merits;
the backport is reviewed as an adaptation of something already accepted, and its
description says what had to differ. Merging the backport first inverts that and
leaves no record of what the change originally was.

## Rebase merges only

The repository is configured for a linear history, and the settings back that
up. From the repository configuration:

| Setting                  | Value   |
|--------------------------|---------|
| `allow_merge_commit`     | `false` |
| `allow_squash_merge`     | `false` |
| `allow_rebase_merge`     | `true`  |
| `delete_branch_on_merge` | `true`  |
| Default branch           | `main`  |

A ruleset named `linear-history` additionally enforces `required_linear_history`
on **all** branches, with no bypass actors at all. The result is visible in the
history: there is not a single merge commit in the last 300 commits on `main`.

Two consequences follow, and both bite in day-to-day work.

**Every commit lands on its own.** Nothing is squashed, so each commit in a pull
request becomes a commit on the target branch with its own message. Each one
therefore has to be a complete, meaningful change with a message that stands
alone, and ideally each one is green by itself — a bisect that lands on a commit
which only works together with the next one is a lost afternoon.

**A merged branch is no longer recognizable by SHA.** Rebasing rewrites every
commit the branch carried, so the commits on the target branch have different
SHAs than the ones that were pushed. Git's ancestry check then says the local
branch is not merged, and `git branch -d` refuses to delete it — correctly, by
its own definition, and uselessly, by yours. Containment has to be checked by
content instead:

```bash
git fetch origin
git diff origin/main..<branch>   # empty output: the branch adds nothing new
git branch -D <branch>           # then delete it forcefully
```

The remote branch is gone already — `delete_branch_on_merge` removes it when the
pull request is merged — so this is local cleanup only. Use the target the pull
request actually went to, `origin/2` for a backport.

## Rules on the maintained branches

Verified through `gh api repos/fgtclb/academic-extensions/rulesets`. Note that
`gh api repos/fgtclb/academic-extensions/branches/main/protection` answers
`404 Branch not protected`: the repository uses **rulesets**, not classic branch
protection, so the classic endpoint being empty says nothing.

The ruleset `version-branches` is active and applies to the default branch plus
`refs/heads/1`, `refs/heads/1.*`, `refs/heads/2` and `refs/heads/2.*`:

| Rule                                       | Effect                                                  |
|--------------------------------------------|---------------------------------------------------------|
| `pull_request`                             | A pull request is required; direct pushes are rejected  |
| `required_approving_review_count`          | 1 approving review                                      |
| `dismiss_stale_reviews_on_push`            | A new push drops existing approvals                     |
| `require_last_push_approval`               | The most recent push must be approved by someone else   |
| `required_review_thread_resolution`        | All review threads must be resolved                     |
| `allowed_merge_methods`                    | `rebase` only                                           |
| `required_linear_history`                  | No merge commits                                        |
| `creation`, `deletion`, `non_fast_forward` | The branches cannot be created, deleted or force-pushed |

Bypass is granted to organization admins and to one repository role, in
`always` mode. That is an escape hatch for a maintainer, not a workflow.

**There is no required status checks rule in either ruleset.** CI is therefore
not a mechanical merge blocker — GitHub will let a pull request with a red
pipeline be merged by someone with the approval. Reading the pipeline result
before merging is a discipline here, not a guard rail, which is exactly why the
next two sections exist.

## Pre-flight, before pushing

Run the gates locally first. The harness is containerized, so a local run and a
CI run execute the same commands in the same image — the difference is only how
much of the matrix is covered.

The rule that is easiest to get wrong: `-t` selects configuration, it does
**not** install dependencies. Each core version needs its own `composerUpdate`
before its gates, and a run for one version against the other version's vendor
tree fails in ways that look like real defects.

```bash
# TYPO3 v13
Build/Scripts/runTests.sh -t 13 -p 8.2 -s composerUpdate
Build/Scripts/runTests.sh -t 13 -p 8.2 -s cgl -n
Build/Scripts/runTests.sh -t 13 -p 8.2 -s phpstan
Build/Scripts/runTests.sh -t 13 -p 8.2 -s lintPhp
Build/Scripts/runTests.sh -t 13 -p 8.2 -s unit
Build/Scripts/runTests.sh -t 13 -p 8.2 -s functional

# TYPO3 v14 — its own composerUpdate first
Build/Scripts/runTests.sh -t 14 -p 8.2 -s composerUpdate
Build/Scripts/runTests.sh -t 14 -p 8.2 -s cgl -n
Build/Scripts/runTests.sh -t 14 -p 8.2 -s phpstan
Build/Scripts/runTests.sh -t 14 -p 8.2 -s lintPhp
Build/Scripts/runTests.sh -t 14 -p 8.2 -s unit
Build/Scripts/runTests.sh -t 14 -p 8.2 -s functional
```

Notes on the individual gates:

- `cgl -n` reports without modifying, which is what CI runs. Drop the `-n` to
  let it fix.
- `phpstan` is the only source gate that genuinely differs per core version — it
  analyses against the installed core through `Build/phpstan/Core13` and
  `Build/phpstan/Core14`.
- `lintPhp` needs neither `-t` nor a prepared vendor tree; running it once is
  enough.
- A change that touches shipped documentation adds
  `Build/Scripts/runTests.sh -s checkRstRenderingAll`. It is a real gate in CI,
  run with `--fail-on-log --fail-on-error`.
- Anything that writes to the database deserves one run on a real DBMS before
  the pipeline finds it: `-d postgres` is the strictest of the four and catches
  what SQLite silently accepts.

On branch `2` the two core versions are v12 and v13, so the same sequence is run
with `-t 12` and `-t 13`.

## Watch the pipeline

Push, then watch the pull request pipeline. A green local run is **not** a
substitute, for three reasons that are all structural rather than occasional:

- **PHP versions.** Local runs use one version, usually the 8.2 default. CI
  lints on 8.2, 8.3, 8.4 and 8.5, and runs unit and functional tests on the
  edges 8.2 and 8.5.
- **Database systems.** The local default is SQLite, which accepts constructs
  MariaDB, MySQL and PostgreSQL reject. CI runs the functional suite on MySQL
  8.0, MariaDB 10.4, MariaDB 10.6 and PostgreSQL 10 as well.
- **Core versions.** It is easy to run the full sequence for one core version
  and only part of it for the other. CI does not forget.

The `ci.yml` workflow is staged so that a defect is reported cheaply:

```
cgl     ─┐
phpstan ─┼─> unit ─> functional (SQLite) ─> functional (MySQL, MariaDB, Postgres)
lint    ─┘
documentation   (independent)
```

The 16-job DBMS matrix only starts once the same functional tests passed on
SQLite for both core versions and both edge PHP versions. A defect that is not
DBMS specific is therefore reported by four jobs instead of twenty — but it also
means a red pipeline can still have most of its work ahead of it, and "the DBMS
jobs did not run" is not the same as "the DBMS jobs passed".

The `documentation` job uploads the rendered documentation as an artifact, and a
separate `pr-comment.yml` workflow posts the link as a pull request comment that
is updated in place. That second workflow runs on `workflow_run` on purpose, so
that pull requests from forks — which get a read-only token — get the comment
too. Its consequence for anyone editing it: changes to `pr-comment.yml` only
take effect once they are on the default branch, never within the pull request
that changes them.

## Backport targets

**The only maintained backport targets are `main` and `2`.**

Branch `2.2` is no longer maintained and branch `1` is legacy. Neither is
proposed as a backport target — not even when it demonstrably carries the same
defect. Stating factually which branches contain a defect is useful; opening a
pull request against `2.2` or `1`, or suggesting one, is not, unless it has been
explicitly requested for that specific change.

Note that the `version-branches` ruleset covers `refs/heads/1`, `refs/heads/1.*`
and `refs/heads/2.*` as well. Those branches being protected is a leftover of
when they were maintained; it is not an invitation.

## See also

- [Commit messages](commit-messages.md) — the format of what ends up on the
  target branch, one commit at a time.
- `AGENTS.md` in the repository root — the quality gates, the backport policy
  and the `runTests.sh` flag reference in full.
- `.github/workflows/ci.yml` — the authoritative job list, matrices and staging.
