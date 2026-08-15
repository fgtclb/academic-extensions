# Pull requests

Every change reaches a maintained branch through a pull request on GitHub,
`fgtclb/academic-extensions`. There is no second route: the branches that
matter are covered by a repository ruleset that requires one, and direct pushes
to them are rejected.

## Branch naming

The established pattern is derived from the pull requests merged into `2` and
from the branches that currently exist on the remote. Every branch that targets
`2` says so in its name:

| Purpose                        | Pattern                | Real example                       |
|--------------------------------|------------------------|------------------------------------|
| Backport of a change on `main` | `ace-<nnn>-backport-2` | `ace-236-backport-2`               |
| Backport, alternative spelling | `ace-<nnn>-<slug>-2`   | `ace-358-text-column-defaults-2`   |
| Work originating on `2`        | `ace-<nnn>-<slug>-2`   | `ace-363-collection-consistency-2` |

All lowercase, words separated by hyphens, the issue number written in full and
in the same order as in the commit subject. The slug describes the change, not
the file it touches, so a branch name is recognizable next to the commit it
carries.

Both spellings are in active use — `-backport-2` and a plain `-2` suffix. Either
is fine; what matters is that the target branch is visible in the name, because
a pull request against `2` looks exactly like one against `main` in a
notification. On `main` the same names appear without the suffix
(`ace-358-text-column-defaults`), which is the other half of the convention.

Branches merged into `2` before August 2026 carry a `task/` prefix, for example
`task/ACE-341-selected-records-free-mode-2` and
`task/ace-313-ci-docker-runtime-2`. `#406` was the last one to use it and `#410`
the first one without; it is not used for new work. Those older names spell the
issue key upper-case about as often as lower-case — the current pattern settles
that on lower case.

## One change, two pull requests

A change that applies to both maintained branches is **two** pull requests, not
one with two commits. The pull request against `main` goes first and is merged
first; the one against `2` follows. The merged history shows the pairs
consistently — `#453` on `main` and `#455` here for ACE-236, `#451` and `#452`
for ACE-382, `#449` and `#450` for ACE-381, each pair minutes apart and in that
order.

The order is not cosmetic. `main` is where the change is reviewed on its merits;
the backport is reviewed as an adaptation of something already accepted, and its
description says what had to differ. Merging the backport first inverts that and
leaves no record of what the change originally was.

A defect that only exists on this branch — because it is in code `main` has
already replaced, or in v12 handling `main` does not have — is a single pull
request against `2`. ACE-358 is the borderline case: the failure it fixes is
only reachable on TYPO3 v12 with MySQL, so only this branch can observe it, and
it still went to `main` first so that the two branches keep the same schema.

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
history: branch `2` does not contain a single merge commit, ever.

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
git diff origin/2..<branch>   # empty output: the branch adds nothing new
git branch -D <branch>        # then delete it forcefully
```

The remote branch is gone already — `delete_branch_on_merge` removes it when the
pull request is merged — so this is local cleanup only. Use the target the pull
request actually went to: `origin/2` for work on this branch, `origin/main` for
the change it was backported from.

## Rules on the branches

The repository uses **rulesets**, not classic branch protection.
`gh api repos/fgtclb/academic-extensions/branches/main/protection` answers
`404 Branch not protected`, and that says nothing at all — it is the wrong
endpoint. Two that are right:

```bash
# What exists.
gh api "repos/fgtclb/academic-extensions/rulesets?includes_parents=true"

# What actually applies to one branch, across all rulesets. Use this one.
gh api repos/fgtclb/academic-extensions/rules/branches/main
```

Prefer the second when a question is "is this branch protected against X". The
conditions are `fnmatch` patterns including bracket expressions, several
rulesets overlap, and reading four condition lists and intersecting them by eye
is how a wrong answer is produced.

Four rulesets, all repository level and all active:

| Ruleset                   | Applies to                                          | Rules                                                                                 |
|---------------------------|-----------------------------------------------------|---------------------------------------------------------------------------------------|
| `linear-history`          | `~ALL`                                              | `required_linear_history`                                                             |
| `version-branches`        | default branch, `2`, `2.[3-9]`, `2.[1-9][0-9]`, …   | `pull_request`, `creation`, `deletion`, `non_fast_forward`, `required_linear_history` |
| `required_status_checks`  | default branch and every version-shaped branch name | `required_status_checks`                                                              |
| `End Of Live (read-only)` | `1`, `1.[0-9]`, …, and `2.[0-2]`                    | `update`, `creation`, `deletion`, `non_fast_forward`                                  |

Which produces two distinct shapes:

| Branch | Effective rules                                                                                                 |
|--------|-----------------------------------------------------------------------------------------------------------------|
| `main` | `creation`, `deletion`, `non_fast_forward`, `pull_request`, `required_linear_history`, `required_status_checks` |
| `2`    | the same                                                                                                        |
| `1`    | `creation`, `deletion`, `non_fast_forward`, `required_linear_history`, `required_status_checks`, `update`       |
| `2.2`  | the same as `1`                                                                                                 |

### The maintained branches — `version-branches`

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

Its conditions name the *live* `2.x` line explicitly — `2.[3-9]` and upwards —
rather than a blanket `2.*`, so that the frozen `2.0`, `2.1` and `2.2` fall to
the read-only ruleset instead. A future `2.3` is covered without touching
anything.

### The pipeline is a merge blocker — `required_status_checks`

One required check, and it is not a leaf job:

| Parameter                              | Value                                                                     |
|----------------------------------------|---------------------------------------------------------------------------|
| `context`                              | `all checks`                                                              |
| `integration_id`                       | `15368` — GitHub Actions, so no other app can satisfy it                  |
| `strict_required_status_checks_policy` | `false` — a pull request need not be rebased onto the newest target first |
| `do_not_enforce_on_create`             | `true`                                                                    |

Requiring the leaf jobs by name does not work here: nearly all of them carry
their matrix values in the name — `unit (v12, PHP 8.1)`,
`functional mysql 8.0 (v13, PHP 8.5)` — so the ruleset would pin the matrix of
a branch in a repository setting that lives outside that branch, and the two
maintained branches do not have the same matrix.

`ci.yml` therefore ends in an aggregating job named **`all checks`**. It needs
every other job, runs with `if: always()` and fails when any of them reported
`failure`, `cancelled` or `skipped`. Its name never changes, so it is the one
check the ruleset requires.

The `always()` is the load-bearing part. Without it the job would be *skipped*
as soon as anything it needs fails, and a skipped required check blocks a pull
request indefinitely rather than reporting a failure. Treating a skipped
dependency as a failure follows from the same reasoning: a gate that did not
run has not passed.

Two consequences worth knowing before they are met in the middle of a merge:

* **This ruleset grants no bypass** — not to organization admins, not to
  anybody. `gh pr merge --admin` is refused while `all checks` is not green,
  unlike every rule in `version-branches`. A degraded GitHub Actions blocks
  merging entirely, and that is intended.
* **A pull request older than the job cannot merge on approval alone.** The
  check is matched on the head commit, so a branch that was last pushed before
  `all checks` existed has no such check run and stays blocked until it is
  rebased or pushed again.

### The frozen branches — `End Of Live (read-only)`

`1` and the `2.0`–`2.2` line are end of life. `update` is the rule that does the
work: it rejects every push to the branch, which blocks merging a pull request
into it as well, so no `pull_request` rule is needed there. `deletion`,
`creation` and `non_fast_forward` close the remaining routes — the branches
cannot be removed, re-created or rewritten.

It grants no bypass either, so working on those branches again means disabling
the ruleset first. That is the intended amount of friction. See
[Backporting](backporting.md): the maintained targets are `main` and `2`.

## Pre-flight, before pushing

Run the gates locally first. The harness is containerized, so a local run and a
CI run execute the same commands in the same image — the difference is only how
much of the matrix is covered.

The rule that is easiest to get wrong: `-t` selects configuration, it does
**not** install dependencies. Each core version needs its own `composerUpdate`
before its gates, and a run for one version against the other version's vendor
tree fails in ways that look like real defects.

The two core versions of this branch are v12 and v13. `-t` accepts nothing else
here, and its default is `12`.

```bash
# TYPO3 v12
Build/Scripts/runTests.sh -t 12 -p 8.1 -s composerUpdate
Build/Scripts/runTests.sh -t 12 -p 8.1 -s cgl -n
Build/Scripts/runTests.sh -t 12 -p 8.1 -s phpstan
Build/Scripts/runTests.sh -t 12 -p 8.1 -s lintPhp
Build/Scripts/runTests.sh -t 12 -p 8.1 -s unit
Build/Scripts/runTests.sh -t 12 -p 8.1 -s functional

# TYPO3 v13 — its own composerUpdate first
Build/Scripts/runTests.sh -t 13 -p 8.2 -s composerUpdate
Build/Scripts/runTests.sh -t 13 -p 8.2 -s cgl -n
Build/Scripts/runTests.sh -t 13 -p 8.2 -s phpstan
Build/Scripts/runTests.sh -t 13 -p 8.2 -s lintPhp
Build/Scripts/runTests.sh -t 13 -p 8.2 -s unit
Build/Scripts/runTests.sh -t 13 -p 8.2 -s functional
```

The PHP versions above are the lowest each core version supports, which is what
CI uses for the gates that do not depend on PHP. TYPO3 v13 does not run on PHP
8.1, so `-t 13 -p 8.1` is not a combination to try.

Notes on the individual gates:

- `cgl -n` reports without modifying, which is what CI runs. Drop the `-n` to
  let it fix. CI runs it once only, on PHP 8.1 with `-t 12`, because
  php-cs-fixer inspects source files rather than the installed core.
- `phpstan` is the only source gate that genuinely differs per core version — it
  analyses against the installed core through `Build/phpstan/Core12` and
  `Build/phpstan/Core13`.
- `lintPhp` needs neither `-t` nor a prepared vendor tree; running it once is
  enough.
- A change that touches shipped documentation adds
  `Build/Scripts/runTests.sh -s checkRstRenderingAll`. It is a real gate in CI,
  run with `--fail-on-log --fail-on-error`.
- Anything that writes to the database deserves one run on a real DBMS before
  the pipeline finds it: `-d postgres` is the strictest of the four and catches
  what SQLite silently accepts.

## Watch the pipeline

Push, then watch the pull request pipeline. A green local run is **not** a
substitute, for three reasons that are all structural rather than occasional:

- **PHP versions.** Local runs use one version, usually the 8.2 default of the
  harness. CI lints on 8.1, 8.2, 8.3, 8.4 and 8.5, and runs unit and functional
  tests on the edges of each core version: v12 on 8.1 and 8.4, v13 on 8.2 and
  8.5. The supported PHP set differs per core version on this branch, which is
  why `ci.yml` lists core/PHP pairs explicitly instead of forming a cross
  product.
- **Database systems.** The local default is SQLite, which accepts constructs
  MariaDB, MySQL and PostgreSQL reject. CI runs the functional suite on MySQL
  8.0, MariaDB 10.4, MariaDB 10.6 and PostgreSQL 10 as well.
- **Core versions.** It is easy to run the full sequence for one core version
  and only part of it for the other. CI does not forget.

The `ci.yml` workflow is staged so that a defect is reported cheaply:

```
cgl     ─┐
phpstan ─┼─> unit ─> functional (SQLite) ─> functional (MySQL, MariaDB, Postgres) ─┐
lint    ─┘                                                                         ├─> all checks
frontend assets, markdown, documentation   (independent) ──────────────────────────┘
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
too. Its consequence bites harder here than on `main`: `workflow_run` always
runs the copy of `pr-comment.yml` that sits on the **default branch**, which is
`main`. A change to that file on branch `2` therefore has no effect at all — not
in the pull request that carries it, and not after it is merged. Edit it on
`main`.

## Backport targets

**The only maintained backport targets are `main` and `2`.**

Branch `2.2` is no longer maintained and branch `1` is legacy. Neither is
proposed as a backport target — not even when it demonstrably carries the same
defect. Stating factually which branches contain a defect is useful; opening a
pull request against `2.2` or `1`, or suggesting one, is not, unless it has been
explicitly requested for that specific change.

That is enforced rather than merely agreed: the `End Of Live (read-only)`
ruleset carries an `update` rule for `1`, its `1.x` line and `2.0`–`2.2`, which
rejects every push to them — merging a pull request included. A backport to one
of those branches cannot land without an administrator disabling the ruleset
first. `version-branches` covers only the live line, so which ruleset a branch
falls under is now a reliable signal of whether it is alive.

## See also

- [Commit messages](commit-messages.md) — the format of what ends up on the
  target branch, one commit at a time.
- `AGENTS.md` in the repository root — the quality gates, the backport policy
  and the `runTests.sh` flag reference in full.
- `.github/workflows/ci.yml` — the authoritative job list, matrices and staging.
