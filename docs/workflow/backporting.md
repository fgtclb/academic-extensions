# Backporting

Most fixes made on `main` belong on the older maintained branch as well. Getting
them there is a small research task, not a `git cherry-pick`: the two branches
support different TYPO3 major versions, so the same defect can need a different
patch — or, just as often, the identical one.

This page is about deciding which of the two it is, cheaply and with evidence.

## The only maintained targets

| Branch | Version line | TYPO3     | PHP floor | Backport target?                       |
|--------|--------------|-----------|-----------|----------------------------------------|
| `main` | `3.0.0-dev`  | v13 + v14 | 8.2       | yes — where changes originate          |
| `2`    | `2.4.x-dev`  | v12 + v13 | 8.1       | yes — the one maintained backport line |
| `2.2`  | `2.2.x`      | —         | —         | **no** — unmaintained                  |
| `1`    | `1.x`        | —         | —         | **no** — legacy                        |

The core constraints come from `packages-dev/monorepo-shared/composer.json`,
which is where every extension's TYPO3 requirement is centralised: `main`
declares `~13.4.0@dev || ~14.3.6@dev`, branch `2` declares
`^12.4.22 || ^13.4`. The PHP floor comes from each extension's own
`composer.json` (`^8.2 || …` versus `^8.1 || …`).

**`2.2` and `1` are never proposed as a backport target**, and that is not a
judgement about the change — it holds even when those branches demonstrably
carry the same defect. Stating that fact is fine and often useful ("`2.2` has
the same code"); planning work on it is not, unless somebody explicitly asks for
that specific change on that specific branch.

So a bugfix has at most two homes, and the question is only ever whether the
patch for the second one is the same.

## A backport is analysed, not cherry-picked

The branches diverged at `ecba366f0` and have been developed apart since. What
can differ underneath a change:

* **TCA** — column types, `type` names and their options moved between v12, v13
  and v14.
* **FlexForm** — the `ds` shape differs per core version, and no version
  tolerates another's.
* **Plugin registration** — the `addPlugin()` signature changed, and what makes
  a `CType` selectable is not what makes it render.
* **API availability** — a class, method or attribute used by the change may
  simply not exist on TYPO3 v12.
* **PHP syntax** — branch `2` still compiles against PHP 8.1, so readonly
  classes, constants in traits and disjunctive normal form types are not
  available there.

Any one of those turns a clean-applying cherry-pick into a fatal error at
runtime, which no amount of "it merged without conflicts" will reveal.

## But check before adapting

The mistake in the other direction is more common and more expensive: assuming
the branches differ and rewriting a patch that did not need it. In practice a
large share of the files are **byte-identical** across the two branches. A
survey of `packages/fgtclb/academic-jobs/Classes/` finds 15 of 19 files
identical between `origin/2` and `origin/main`.

So the first step is always measurement, and it takes three commands. None of
them needs a checkout, a worktree or a stash — they read the branches straight
out of the object database.

### 1. Diff every touched file

For each file the change on `main` touches:

```bash
git diff --stat origin/2 origin/main -- packages/fgtclb/academic-jobs/Classes/Controller/JobController.php
```

Empty output means the file is identical on both branches and the hunk will
apply as-is. Anything else is the amount of adaptation actually required, and
its size is the estimate:

```
 .../Classes/Controller/JobController.php | 155 +++++++++++++++++----
 1 file changed, 125 insertions(+), 30 deletions(-)
```

Do this per file rather than for the whole change, because one heavily diverged
file among five identical ones is a very different job from five moderately
diverged ones.

### 2. Grep for every API the change uses

For each class, method, attribute or constant the patch introduces:

```bash
git grep -l "FileUploadConfiguration" origin/2 -- 'packages/*'
```

No output (exit code 1) means nothing on branch `2` uses that API — which is a
strong hint that it is not available there, and always a prompt to check the
core version rather than to assume. Run the same grep against `origin/main` to
see whether the API arrives with this change or was already established — and
when the answer decides the backport, confirm it against the vendor tree of the
older core version rather than against the packages.

### 3. Check test-harness parity

```bash
git ls-tree -r --name-only origin/2 -- packages/fgtclb/academic-jobs/Tests/
git ls-tree -r --name-only origin/main -- packages/fgtclb/academic-jobs/Tests/
```

Compare the two listings. The tests that come with the change need a place to
live, and that place does not always exist — see the next section.

Only after these three does the adaptation get planned. Often the plan is "apply
unchanged".

## What actually differs, in practice

| Area                     | `main`                           | `2`                              |
|--------------------------|----------------------------------|----------------------------------|
| TYPO3                    | v13 + v14                        | v12 + v13                        |
| `runTests.sh -t` default | `13`                             | `12`                             |
| `COMPOSER_ROOT_VERSION`  | `3.0.0-dev`                      | `2.4.0-dev`                      |
| PHPStan configurations   | `Build/phpstan/Core13`, `Core14` | `Build/phpstan/Core12`, `Core13` |
| XLF indentation          | two spaces                       | tabs                             |
| Test-helper traits       | 7                                | 4                                |
| phpunit group names      | `not-core-13`, `not-core-14`     | `not-core-12`, `not-core-13`     |
| Changelog directory      | `Documentation/Changelog/3.0/`   | `Documentation/Changelog/2.4/`   |

### XLF indentation

Language files are indented with **tabs on branch `2`** and with **two spaces on
`main`**. This is uniform: all 50 `.xlf` files of `origin/2` are tab-indented
and none of the 52 on `main` is.

```xml
<!-- origin/main -->
  <file
    source-language="en"

<!-- origin/2 -->
	<file
		source-language="en"
```

A label added on `main` and pasted into branch `2` therefore arrives with the
wrong indentation, in a file where nothing else uses it. It is invisible in a
rendered diff and obvious in the file. Re-indent the added lines to match their
surroundings; do not reformat the file.

### The test harness is not at parity

`packages-dev/testing-helper/` — the shared functional-test traits — has grown
on `main` and was not backported wholesale:

| Trait                                  | `main` | `2` |
|----------------------------------------|--------|-----|
| `ExtensionsLoadedTestsTrait`           | yes    | yes |
| `ExtensionCoreVersionCompatTestsTrait` | yes    | yes |
| `TcaHelperMethodsTrait`                | yes    | yes |
| `DeprecatedCoreLabelsTrait`            | yes    | no  |
| `EnsureTtContentListTypeColumnTrait`   | yes    | no  |
| `FrontendPluginRenderingTrait`         | yes    | yes |
| `PluginFlexFormDataStructureTrait`     | yes    | no  |

All seven live in
`packages-dev/testing-helper/Classes/FunctionalTestCase/` on `main`; branch `2`
has four of them.

The consequence is concrete for the three that are missing, and it is worth
checking per change rather than assumed: the whole
`packages/fgtclb/academic-jobs/Tests/Functional/Plugins/` tree, for instance,
exists only on `main`, although the trait it uses is on both branches now.
Backporting a change whose test has no home means one of three things, and the
choice is worth stating in the pull request:

1. backport the production fix without the test,
2. backport the trait first, as its own change, then the fix with its test,
3. write a different test on branch `2` that uses what is there.

None of them is wrong; silently dropping the test without saying so is.

### phpunit group names mean different things

Both branches run `--exclude-group not-core-${CORE_VERSION}`, so a test tagged
`not-core-13` is skipped when the suite runs against TYPO3 v13. But the *other*
version of each branch is not the same version:

| Attribute     | On `main` runs on | On `2` runs on |
|---------------|-------------------|----------------|
| `not-core-13` | v14 only          | v12 only       |
| `not-core-14` | v13 only          | n/a            |
| `not-core-12` | n/a               | v13 only       |

A `#[Group('not-core-13')]` copied unchanged from `main` to `2` therefore flips
its meaning from "v14 only" to "v12 only" — the test still runs, still passes or
fails plausibly, and tests the opposite of what was intended. Both attributes
are in active use: `main` carries eleven `not-core-13` and six `not-core-14`,
branch `2` eighteen `not-core-12` and four `not-core-13`.

Translate the intent, not the string: "the newer core version of this branch" is
`not-core-13` on `main` and `not-core-12` on `2`.

### Core-version-aware structure

`main` has no `Core13/`/`Core14/` class folders at all; its two version
differences are inline switches on
`(new Typo3Version())->getMajorVersion()` in
`packages/fgtclb/academic-base/Classes/TcaManipulator.php:137` and `:179`.
Branch `2` does use the folder split, under
`packages/fgtclb/academic-base/Classes/` as `Core12/Environment/` and
`Core13/Environment/`.

The same file can therefore need a different mechanism on each branch.
`TcaManipulator.php` exists on both and is nearly 100 lines longer on `main`
(296 lines versus 185), almost entirely because of the v14 handling — a change
to it is never a straight copy.

### The changelog entry moves

A user-facing backport needs its own changelog entry on the target branch, in
that branch's version directory: `Documentation/Changelog/2.4/` on branch `2`,
not the `3.0/` the entry was written into on `main`. The text usually needs
adapting too, because "removed in 3.0" is not what happened on the `2.x` line.
See [Changelog and documentation](changelog-and-documentation.md).

## Verify on the target branch, not by analogy

`-t` selects configuration only; it does not reinstall dependencies. Before
running anything on the backport branch:

```bash
Build/Scripts/runTests.sh -t 12 -p 8.1 -s composerUpdate
Build/Scripts/runTests.sh -t 12 -p 8.1 -s cgl -n
Build/Scripts/runTests.sh -t 12 -p 8.1 -s phpstan
Build/Scripts/runTests.sh -t 12 -p 8.1 -s unit
Build/Scripts/runTests.sh -t 12 -p 8.1 -s functional
```

…and then the same series with `-t 13`, the branch's other core version, each
time starting with `composerUpdate`. A vendor tree installed for one core
version silently answers questions about the other one wrongly. `-p` needs the
same treatment: the PHP version is part of the install, not only of the run.

Run the functional suite against a real DBMS (`-d mysql`, `-d postgres`) for
anything that writes. SQLite accepts several constructs the other systems
reject, so the default run is the one least likely to show a database defect —
and branch `2` reaches one TYPO3 major version further back, where the core's
own schema handling differs.

## Commit messages

A backport is a **new commit**: same subject, same issue reference, different
hash. `ACE-358` is `8b5557037` on `main` and `1dd253043` on branch `2`, both
titled `[BUGFIX] ACE-358: Drop the defaults on TEXT columns`.

The body is expected to differ, because the facts differ. The `main` message
says the failure "is not reachable on this branch" and explains why the change
belongs there anyway; the branch `2` message describes the failure actually
occurring on TYPO3 v12 with MySQL, and names the CI jobs that see it. Copying
either message onto the other branch would have made it wrong.

**Never silently rewrite another author's commit message.** Adapting the body
to the target branch is required work, but when the original is somebody else's,
it is done with them and not behind them: keep their authorship, keep their
reasoning, and raise the parts that no longer hold rather than quietly deleting
them. A message that claims something untrue about the branch it sits on is
worse than one that is merely terse.

## Backporting an OpenSpec change

An [OpenSpec](openspec.md) change is branch-scoped like everything else here.
The main specs under `openspec/specs/` describe the behaviour of the branch they
sit on — on `main` that is 3.x on TYPO3 v13 and v14 — so they are never copied
to branch `2` wholesale, for the same reason `docs/` is not. A capability
without a spec on `2` simply has none yet; it gets one with the first change
there that touches it.

What travels is the delta of one change, in the shape the analysis above
produced:

1. On `main`, the change is proposed, applied and archived within its pull
   request. The archive updates `main`'s specs.
2. The backport is analysed as above: the file-level diff, the API grep, the
   harness parity check.
3. On `2`, the backport pull request carries a change of its own, under the
   same name `ace-NNN-<slug>`. Its proposal states that it backports the `main`
   change and names the archived folder there. Its delta spec is written from
   the analysis: the requirements name v12 and v13, and whatever the branch
   cannot do is left out. When the analysis found the touched files identical,
   the archived folder from `main` is a fine starting point, with the version
   statements adjusted.
4. Apply and archive on `2` as the last commit of the backport pull request,
   which updates `2`'s own specs.

Never sync `openspec/specs/` between the branches in either direction, and
never move an archived change across branches unchanged: the spec it carries
would claim v14 behaviour on a branch that has no v14.

## See also

- [Changelog and documentation](changelog-and-documentation.md) — where the
  entry for a backport goes.
- [Releasing](releasing.md) — each branch releases on its own version line.
- [Commit messages](commit-messages.md) — the subject and reference a backport
  keeps.
- [Pull requests](pull-requests.md) — a backport is a pull request like any
  other.
- [OpenSpec](openspec.md) — the artifacts and the lifecycle a backported
  change goes through again on the target branch.
- [Development environment](../development/environment.md) — `-t`, `-p`, `-d`
  and why `composerUpdate` is not optional.
- `AGENTS.md` in the repository root — the backport policy in its short form.
- `README.md`, section "Repository version support" — the per-extension support
  matrix.
- `packages-dev/monorepo-shared/composer.json` — the TYPO3 constraints that
  define what each branch has to work against.
