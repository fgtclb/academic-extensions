# Backporting

This branch is the receiving end. Most fixes are made on `main` first and belong
here as well; getting them here is a small research task, not a
`git cherry-pick`. The two branches support different TYPO3 major versions, so
the same defect can need a different patch — or, just as often, the identical
one.

This page is about deciding which of the two it is, cheaply and with evidence.

## The only maintained targets

| Branch | Version line | TYPO3     | PHP floor | Role                                |
|--------|--------------|-----------|-----------|-------------------------------------|
| `main` | `3.0.0-dev`  | v13 + v14 | 8.2       | where changes originate             |
| `2`    | `2.4.x-dev`  | v12 + v13 | 8.1       | **this branch** — the backport line |
| `2.2`  | `2.2.x`      | —         | —         | **no** — unmaintained               |
| `1`    | `1.x`        | —         | —         | **no** — legacy                     |

The core constraints come from `packages-dev/monorepo-shared/composer.json`,
which is where every extension's TYPO3 requirement is centralised: this branch
declares `^12.4.22 || ^13.4`, `main` declares `~13.4.0@dev || ~14.3.6@dev`. The
PHP floor comes from each extension's own `composer.json` — `^8.1 || ^8.2 ||
^8.3 || ^8.4` here, `^8.2 || ^8.3 || ^8.4` on `main`.

Note that the two branches overlap on exactly one core version, TYPO3 v13. That
overlap is what makes most backports trivial, and the two versions that do *not*
overlap — v12 here, v14 there — are where the work is.

**`2.2` and `1` are never proposed as a backport target**, and that is not a
judgement about the change — it holds even when those branches demonstrably
carry the same defect. Stating that fact is fine and often useful ("`2.2` has
the same code"); planning work on it is not, unless somebody explicitly asks for
that specific change on that specific branch.

So a bugfix has at most two homes, and the question is only ever whether the
patch for the second one is the same.

## A backport is analysed, not cherry-picked

The branches diverged at `ecba366f0` (2026-07-13) and have been developed apart
since. What can differ underneath a change arriving from `main`:

* **TCA** — column types, `type` names and their options moved between v12, v13
  and v14.
* **FlexForm** — the `ds` shape differs per core version, and no version
  tolerates another's. This branch keeps the two shapes apart in `Core12/` and
  `Core13/` subfolders of the FlexForm configuration directory, in five
  extensions.
* **Plugin registration** — the `addPlugin()` signature changed, and what makes
  a `CType` selectable is not what makes it render.
* **API availability** — a class, method or attribute the change uses may simply
  not exist on TYPO3 v12. This is the direction that bites here: a patch written
  against v13 and v14 has no reason to avoid anything v12 lacks.
* **PHP syntax** — this branch still compiles against PHP 8.1, so readonly
  classes, constants in traits and disjunctive normal form types are not
  available and a patch from `main` may use them.

Any one of those turns a clean-applying cherry-pick into a fatal error at
runtime, which no amount of "it merged without conflicts" will reveal.

## But check before adapting

The mistake in the other direction is more common and more expensive: assuming
the branches differ and rewriting a patch that did not need it. In practice a
large share of the files are **byte-identical** across the two branches. A
survey of `packages/fgtclb/academic-jobs/Classes/` finds 16 of 19 files
identical between `origin/2` and `origin/main`.

So the first step is always measurement, and it takes three commands. None of
them needs a checkout, a worktree or a stash — they read the branches straight
out of the object database.

### 1. Diff every touched file

For each file the change on `main` touches, compared against its state here:

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

No output (exit code 1) means nothing on this branch uses that API — a strong
hint that it is not available on TYPO3 v12, and always a prompt to check rather
than to assume. Run the same grep against `origin/main` to see whether the API
arrives with this change or was already established — and when the answer
decides the backport, confirm it against the v12 vendor tree rather than against
the packages.

`FileUploadConfiguration` is the worked example: five files on `origin/main`
mention it, none on this branch. It is part of TYPO3's native Extbase file
upload handling, and it is present in the v13 vendor tree installed here
(`.Build/vendor/typo3/cms-extbase/Classes/Mvc/Controller/`). Whether v12 has it
is the question that decides the backport, and it is answered by installing v12
and looking — not by the grep. What the grep does establish is that this branch
still ships its own
`academic-base/Classes/Extbase/Property/TypeConverter/FileUploadConverter.php`,
which `main` removed, so the two file upload paths are genuinely different code.

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

| Area                       | `2` — this branch                | `main`                           |
|----------------------------|----------------------------------|----------------------------------|
| TYPO3                      | v12 + v13                        | v13 + v14                        |
| `runTests.sh -t` values    | `12` (default), `13`             | `13` (default), `14`             |
| `COMPOSER_ROOT_VERSION`    | `2.4.0-dev`                      | `3.0.0-dev`                      |
| PHPStan configurations     | `Build/phpstan/Core12`, `Core13` | `Build/phpstan/Core13`, `Core14` |
| XLF indentation            | tabs                             | two spaces                       |
| Test-helper traits         | 3                                | 7                                |
| phpunit group names        | `not-core-12`, `not-core-13`     | `not-core-13`, `not-core-14`     |
| Core-version class folders | `Core12/`, `Core13/`             | none                             |
| Changelog directory        | `Documentation/Changelog/2.4/`   | `Documentation/Changelog/3.0/`   |

### XLF indentation

Language files are indented with **tabs on this branch** and with **two spaces
on `main`**. This is uniform: of the 50 `.xlf` files on each branch, all 50 here
are tab-indented and none on `origin/main` is.

```xml
<!-- this branch -->
	<file
		source-language="en"

<!-- origin/main -->
  <file
    source-language="en"
```

A label added on `main` and pasted in here therefore arrives with the wrong
indentation, in a file where nothing else uses it. It is invisible in a rendered
diff and obvious in the file. Re-indent the added lines to match their
surroundings; do not reformat the file.

### The test harness is not at parity

`packages-dev/testing-helper/` — the shared functional-test traits — has grown
on `main` and was not backported wholesale. This branch has three of the seven:

| Trait                                  | `2` — this branch | `main` |
|----------------------------------------|-------------------|--------|
| `ExtensionCoreVersionCompatTestsTrait` | yes               | yes    |
| `ExtensionsLoadedTestsTrait`           | yes               | yes    |
| `TcaHelperMethodsTrait`                | yes               | yes    |
| `DeprecatedCoreLabelsTrait`            | no                | yes    |
| `EnsureTtContentListTypeColumnTrait`   | no                | yes    |
| `FrontendPluginRenderingTrait`         | no                | yes    |
| `PluginFlexFormDataStructureTrait`     | no                | yes    |

All of them live in `packages-dev/testing-helper/Classes/FunctionalTestCase/`;
here that directory holds exactly the first three files.

The consequence is concrete: a fix on `main` that comes with a frontend plugin
rendering test has **no home here**. The whole
`packages/fgtclb/academic-jobs/Tests/Functional/Plugins/` tree exists only on
`main`. Backporting such a change means one of three things, and the choice is
worth stating in the pull request:

1. backport the production fix without the test,
2. backport the trait first, as its own change, then the fix with its test,
3. write a different test here that uses what is available.

None of them is wrong; silently dropping the test without saying so is.

### phpunit group names mean different things

Both branches run `--exclude-group not-core-${CORE_VERSION}`, so a test tagged
`not-core-13` is skipped when the suite runs against TYPO3 v13. But the *other*
version of each branch is not the same version:

| Attribute     | Here runs on | On `main` runs on |
|---------------|--------------|-------------------|
| `not-core-12` | v13 only     | n/a               |
| `not-core-13` | v12 only     | v14 only          |
| `not-core-14` | n/a          | v13 only          |

`not-core-13` is the trap: it exists on both branches and means the opposite
thing. Copied unchanged from `main` to here it flips from "v14 only" to "v12
only" — the test still runs, still passes or fails plausibly, and tests the
opposite of what was intended. `not-core-14` copied in here excludes nothing at
all, because `-t 14` cannot be selected, so the test runs on both versions
silently. Both of this branch's attributes are in active use, four occurrences
each, all of them in `academic-base/Tests/Functional/`; `main` carries seven
`not-core-13` and one `not-core-14`.

Translate the intent, not the string: "the newer core version of this branch" is
`not-core-12` here and `not-core-13` on `main`.

### Core-version-aware structure

The two branches solve the same problem differently, which is the least obvious
of the differences here.

This branch uses the folder split: `packages/fgtclb/academic-base/Classes/`
carries `Core12/Environment/` and `Core13/Environment/`, wired up in
`Configuration/Services.php`, with matching `Tests/Functional/Core12/` and
`Core13/` folders. `main` has no `Core13/`/`Core14/` class folders at all; its
version differences are inline switches on
`(new Typo3Version())->getMajorVersion()`.

The same file can therefore need a different mechanism on each branch.
`TcaManipulator.php` exists on both and is nearly 100 lines longer on `main`
(282 lines versus 185 here), almost entirely because of the v14 handling — a
change to it is never a straight copy. Inline switches exist here too, in the
`Configuration/TCA/Overrides/tt_content.php` of six extensions and in three
`ext_localconf.php` files, all keyed on the TYPO3 major version.

### The changelog entry moves

A user-facing backport needs its own changelog entry here, in this branch's
version directory: `Documentation/Changelog/2.4/`, not the `3.0/` the entry was
written into on `main`. There is no `3.0/` directory in any package here, and no
`Changelog-3.rst` to link one from — the tree runs `2.0` … `2.4` and stops. The
text usually needs adapting too, because "removed in 3.0" is not what happened
on the `2.x` line. See
[Changelog and documentation](changelog-and-documentation.md).

## Verify on the target branch, not by analogy

`-t` selects configuration only; it does not reinstall dependencies. Before
running anything here:

```bash
Build/Scripts/runTests.sh -t 12 -p 8.1 -s composerUpdate
Build/Scripts/runTests.sh -t 12 -p 8.1 -s cgl -n
Build/Scripts/runTests.sh -t 12 -p 8.1 -s phpstan
Build/Scripts/runTests.sh -t 12 -p 8.1 -s unit
Build/Scripts/runTests.sh -t 12 -p 8.1 -s functional
```

…and then the same series with `-t 13 -p 8.2`, this branch's other core version,
each time starting with `composerUpdate`. A vendor tree installed for one core
version silently answers questions about the other one wrongly. `-p` needs the
same treatment: the PHP version is part of the install, not only of the run. A
vendor tree left over from working on `main` is the same hazard one step larger
— it can hold TYPO3 v14.

Run the functional suite against a real DBMS (`-d mysql`, `-d postgres`) for
anything that writes. SQLite accepts several constructs the other systems
reject, so the default run is the one least likely to show a database defect —
and this branch reaches one TYPO3 major version further back than `main`, where
the core's own schema handling differs. ACE-358 is exactly that case: the defect
was visible only in the two `functional mysql 8.0 (v12, …)` jobs.

## Commit messages

A backport is a **new commit**: same subject, same issue reference, different
hash. `ACE-358` is `8b5557037` on `main` and `1dd253043` here, both titled
`[BUGFIX] ACE-358: Drop the defaults on TEXT columns`.

The body is expected to differ, because the facts differ. The `main` message
says the failure "is not reachable on this branch" and explains why the change
belongs there anyway; the message here describes the failure actually occurring
on TYPO3 v12 with MySQL, and names the CI jobs that see it. Copying either
message onto the other branch would have made it wrong.

`ACE-381` shows the milder version of the same thing. Its body here ends with a
paragraph that only makes sense on this branch — the overlay distinction the
change restores is not observable on v12 or v13, because the core fix for forge
#88886 landed in v14.3.6 and was never backported to v13.4. That paragraph does
not exist in the `main` message, and the `main` message's measurements
("verified green on 13.4.34 and on 14.3.6") do not exist here.

**Never silently rewrite another author's commit message.** Adapting the body
to the target branch is required work, but when the original is somebody else's,
it is done with them and not behind them: keep their authorship, keep their
reasoning, and raise the parts that no longer hold rather than quietly deleting
them. A message that claims something untrue about the branch it sits on is
worse than one that is merely terse.

## See also

- [Changelog and documentation](changelog-and-documentation.md) — where the
  entry for a backport goes.
- [Releasing](releasing.md) — each branch releases on its own version line.
- [Commit messages](commit-messages.md) — the subject and reference a backport
  keeps.
- [Pull requests](pull-requests.md) — a backport is a pull request like any
  other.
- [Development environment](../development/environment.md) — `-t`, `-p`, `-d`
  and why `composerUpdate` is not optional.
- `AGENTS.md` in the repository root — the backport policy in its short form.
- `README.md`, section "Repository version support" — the per-extension support
  matrix.
- `packages-dev/monorepo-shared/composer.json` — the TYPO3 constraints that
  define what each branch has to work against.
