# Dual core setup

Branch `2` supports TYPO3 v12 and v13 from one code base. Every package
declares `php: ^8.1 || ^8.2 || ^8.3 || ^8.4`, and `core-13/` narrows that to
`^8.2 || ^8.3 || ^8.4` because v13 drops PHP 8.1.
Only one dependency set can be installed in `.Build/` at a time, and nothing in
the tooling notices when the installed one is not the one a command was asked
for. That makes the rule below the single most important habit in this
repository.

## The rule

> The dependency set installed in `.Build/` must match the `-t` value the
> command is run with.

`-t <12|13>` selects **configuration only**. It picks the phpstan config
(`Build/phpstan/Core${CORE_VERSION}/phpstan.neon`, in the `phpstan` arm) and
the phpunit exclude group (`--exclude-group not-core-${CORE_VERSION}`, in the
`functional`, `unit` and `unitRandom` arms). It installs nothing.

The only suite that changes what is in `.Build/` is `composerUpdate`:

```bash
rm -rf .Build composer.lock composer.json.orig
\cp -f composer.json composer.json.orig
composer require --dev --no-update "typo3/minimal":"^${CORE_VERSION}"
composer install
[[ -f composer.json.orig ]] && \cp -f composer.json.orig composer.json
```

It removes `.Build/`, pins `typo3/minimal` to the requested major in a
throwaway copy of `composer.json`, installs, and restores the original
`composer.json` afterwards — which is why a core switch never shows up as a
working-tree change.

So the sequence is always install first, then run:

```bash
Build/Scripts/runTests.sh -t 13 -p 8.3 -s composerUpdate

Build/Scripts/runTests.sh -t 13 -p 8.3 -s cgl -n
Build/Scripts/runTests.sh -t 13 -p 8.3 -s phpstan
Build/Scripts/runTests.sh -t 13 -p 8.3 -s unit
Build/Scripts/runTests.sh -t 13 -p 8.3 -s functional
```

Running `-t 12` while the v13 set is installed does not fail with a useful
message. It produces wrong answers: phpstan reports unknown classes for APIs
that only exist in the other major and misses the errors it was supposed to
find, and tests pass or fail for reasons that have nothing to do with the
change under test. The default is `-t 12` — `CORE_VERSION="12"` — so the trap
is easiest to fall into right after finishing a v13 round and
omitting `-t` on the next command.

Both `.Build/` and `composer.lock` are git-ignored, so nothing is lost by
reinstalling. The composer download cache lives in `.cache/composer`, outside
`.Build/`, so a switch back and forth does not re-download the world.

## `-p` is part of the dependency set too

`-p <8.1|8.2|8.3|8.4|8.5>` has exactly the same property, and it is easier to
overlook because a PHP version does not feel like a dependency. The default is
`8.2` — `PHP_VERSION="8.2"`.

`composerUpdate` runs `composer` inside the container image selected by `-p`
(`IMAGE_PHP`, which builds the name
`ghcr.io/typo3/core-testing-php<version without dot>:latest` and is what both
composer calls of that arm run in). The root
`composer.json` sets no `config.platform`, so composer resolves against the PHP
version it is actually running on. A tree resolved on 8.1 can therefore hold
different package versions than one resolved on 8.4, and running the 8.4 image
against a tree resolved on 8.1 tests something other than what continuous
integration will test.

`.github/workflows/ci.yml` follows this without exception: every job that runs
a suite runs `composerUpdate` first with the same `-t` **and** `-p` values it
then uses — its *Prepare dependencies for TYPO3 v…* step, present in `cgl`,
`phpstan`, `unit`, `functional-sqlite` and `functional-dbms`. The one job that
does not is `lint`, which passes neither `-t` nor `composerUpdate` because
`lintPhp` only runs `php -l` over the sources and needs no vendor tree at all.

Treat `-t` and `-p` together as the identity of the installed tree: change
either, reinstall.

PHP 8.5 is supported, even though no `composer.json` names it. The root, all
twelve extensions and `core-12/` declare `^8.1 || ^8.2 || ^8.3 || ^8.4` and
`core-13/` declares `^8.2 || ^8.3 || ^8.4` — and a caret constraint bounds only
the leftmost non-zero digit, so `^8.4` means `>=8.4 <9.0` and matches 8.5. That
is why `runTests.sh` accepts `-p 8.5` (`:437`) and `ci.yml` schedules jobs on it
(`:188`, `:231`, `:273`) without anything having to be added.

## Which core version is installed right now

The installed core states its own version. Reading it needs no container:

```bash
grep -n "protected const VERSION" \
  .Build/vendor/typo3/cms-core/Classes/Information/Typo3Version.php
```

On a v13 tree that prints, for example:

```
22:    protected const VERSION = '13.4.34';
```

The equivalent through the harness, which also shows the constraint the package
was resolved with, is the `composer` suite — it dispatches all remaining
arguments to composer inside the container:

```bash
Build/Scripts/runTests.sh -s composer show typo3/cms-core
```

If `.Build/vendor/` does not exist at all, no dependency set is installed and
every suite except `lintPhp` and the `composer*` ones will fail.

## The changelogs come with the dependency set

The TYPO3 changelogs are shipped inside `typo3/cms-core`, so they are installed
along with everything else:

```
.Build/vendor/typo3/cms-core/Documentation/Changelog/
```

A core package carries the changelogs of its own version and of every earlier
one. With the v12 set installed the directory ends at `12.4/` and `12.4.x/`,
and there is no `13.0/`; installing v13 adds the `13.*` folders on top. To look
up a v13 behaviour change, install the v13 set first — reading a changelog is
not running a gate, so switching back afterwards costs nothing but the install.

Use it whenever a difference between the two majors has to be explained rather
than guessed at: a deprecation notice in a test run, an API that behaves
differently, a signature that changed. The entry names the affected API, gives
the migration and carries the forge issue number, which is what belongs in a
commit message or an `@todo`.

Read the entries, do not guess filenames. The naming scheme is
`<Type>-<IssueNumber>-<Title>.rst`, and the issue number is not something to
reconstruct. List the folder and grep it:

```bash
ls .Build/vendor/typo3/cms-core/Documentation/Changelog/12.4/
grep -rl "FlexFormService" .Build/vendor/typo3/cms-core/Documentation/Changelog/
```

A path assembled from a remembered issue number is how a wrong changelog ends
up cited in a commit message.

## Test grouping

Not every test can run on both core versions. Two mechanisms exist and they are
picked by scope, not by taste.

`runTests.sh` always passes `--exclude-group not-core-${CORE_VERSION}` to
phpunit, in the `functional`, `unit` and `unitRandom` arms — the `functional`
one combines it with the DBMS exclusion into
`--exclude-group not-${DBMS},not-core-${CORE_VERSION}`. The group therefore
names the version the test must
**not** run on:

| Group         | Runs on     | Meaning                                     |
|---------------|-------------|---------------------------------------------|
| `not-core-12` | v13 only    | Excluded from a `-t 12` run.                |
| `not-core-13` | v12 only    | Excluded from a `-t 13` run.                |
| _(no group)_  | v12 and v13 | The normal case. Most tests carry no group. |

Note that `not-core-13` means the opposite of what it means on `main`, where
the supported pair is v13 and v14. The group always names the excluded major,
never the supported one.

The inversion is deliberate: an untagged test runs everywhere, so a new test is
version neutral until someone states otherwise.

### Shape one: a single method

When only one assertion differs, the method carries the attribute and stays in
the shared test class next to its counterpart. The clearest example is
`packages/fgtclb/academic-base/Tests/Functional/Environment/EnvironmentBuilderFactoryTest.php`,
where the factory returns a different builder implementation per major:

- `:36` — `#[Group('not-core-13')]` on
  `createReturnsTypoV12FrontendEnvironmentBuilderInstance()`
- `:49` — `#[Group('not-core-12')]` on
  `createReturnsTypoV13FrontendEnvironmentBuilderInstance()`

Both methods sit in the same class, next to three untagged ones that assert the
version neutral behaviour, so the two expectations are readable side by side.
That is the whole argument for this shape: the difference is the subject of the
test.

### Shape two: a whole class in a core version folder

When the subject itself only exists on one version, the test class moves into a
`Core12/` or `Core13/` subfolder of `Tests/Unit` or `Tests/Functional`,
mirroring the `Classes/Core12/` and `Classes/Core13/` folders of the production
code. `academic-base` is the only package that does this today:

- `packages/fgtclb/academic-base/Tests/Functional/Core12/Environment/StateManagerTest.php`
  — covers `FGTCLB\AcademicBase\Core12\Environment\StateManager`, all three
  test methods tagged `#[Group('not-core-13')]` (`:43`, `:100`, `:163`).
- `packages/fgtclb/academic-base/Tests/Functional/Core13/Environment/StateManagerTest.php`
  — the v13 counterpart, all three tagged `#[Group('not-core-12')]` (`:43`,
  `:105`, `:183`).

The folder alone does not exclude anything: phpunit selects by group, so the
attribute is still required. Both files put it on every test method rather than
on the class, which works but has to be repeated for each new method — a class
level attribute would cover them all.

The phpunit suites glob `packages/*/*/Tests/Unit/` and
`packages/*/*/Tests/Functional/` recursively (`Build/phpunit/UnitTests.xml:42`,
`Build/phpunit/FunctionalTests.xml:42`), so such a subfolder is discovered
without any configuration change. phpstan does need to know about it: each
configuration excludes the other version's folders explicitly
(`Build/phpstan/Core12/phpstan.neon:19-20`,
`Build/phpstan/Core13/phpstan.neon:19-20`).

Whichever shape is used, add a comment saying **why** the test is limited and,
where the limitation ends with v12 support, a `@todo` to drop the group. None
of the tests above carries one yet, so this is a convention to establish rather
than one to copy.

## Verifying a change

A change is verified when the full set of gates has run for **both** core
versions. Continuous integration will do it either way, but it reports after
the pull request is open, and core-version-dependent code is exactly where the
mistakes are.

```bash
for core in 12 13; do
  Build/Scripts/runTests.sh -t "$core" -p 8.2 -s composerUpdate
  Build/Scripts/runTests.sh -t "$core" -p 8.2 -s lintPhp
  Build/Scripts/runTests.sh -t "$core" -p 8.2 -s cgl -n
  Build/Scripts/runTests.sh -t "$core" -p 8.2 -s phpstan
  Build/Scripts/runTests.sh -t "$core" -p 8.2 -s unit
  Build/Scripts/runTests.sh -t "$core" -p 8.2 -s functional
done
```

Notes on that loop:

- `composerUpdate` is inside the loop, and it is the reason the loop works. Do
  not lift it out.
- `-s cgl -n` reports without modifying, which is what continuous integration
  runs. Drop the `-n` to let php-cs-fixer apply the fixes, then rerun with
  `-n`.
- `-s functional` defaults to `-d sqlite`. Add a run with `-d postgres` — and
  `-d mariadb` or `-d mysql` — whenever the change touches queries, schema or
  TCA. SQLite is the most forgiving of the four and has hidden real defects in
  this repository before.
- `-p 8.2` is chosen because both majors accept it — v13 does not run on 8.1,
  v12 does not require it — which is what lets one loop cover both. The matrix
  edges differ per core version: v12 on 8.1 and 8.4, v13 on 8.2 and 8.5
  — the `combo` axis of `ci.yml`. A change that touches language-level behaviour deserves a
  second pass on the other edge of each major, and every such pass needs its
  own `composerUpdate`.
- Restrict a run while iterating by appending a path, for example
  `-s unit packages/fgtclb/academic-base/Tests/Unit/TcaManipulatorTest.php`.
  The trailing path is the restriction mechanism; there is no dedicated flag
  for handing extra options to phpunit, apart from `-o <seed>` for
  `unitRandom`.

Run the unrestricted sequence once before pushing, no matter how narrow the
change looked.

## See also

- [Monorepo layout](monorepo-layout.md) — what lives where, and why the
  extensions are developed together
- [Development environment](environment.md) — host requirements and the full
  `runTests.sh` option list
- [Quality gates](quality-gates.md) — what each suite checks
- [Core version aware code](../architecture/core-version-aware-code.md) — how
  production code handles the v12/v13 difference
- [PHPUnit configuration](../testing/phpunit-configuration.md)
- [Commit messages](../workflow/commit-messages.md) — how to reference a TYPO3
  behaviour change
- [`README.md`](../../README.md) — branch and core version support matrix
