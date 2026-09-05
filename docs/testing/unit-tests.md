# Unit tests

Unit tests run against a bootstrapped TYPO3 class loader and nothing else — no
database, no site, no request. Everything the subject needs is passed to it or
stubbed. That makes the suite fast enough to run on every save, and it makes a
failure point at one class instead of at a stack.

There are 84 unit test classes across the twelve extensions and two more in
`packages-dev/dev-site`, 12 of which are the one-line version compatibility
test every extension carries (see
[below](#the-version-compatibility-test)). Measured with
`find packages/fgtclb/*/Tests/Unit packages-dev/*/Tests/Unit -name '*Test.php' | wc -l`.

## Running them

Dependencies come first, always. `-t` selects configuration, it does **not**
install anything, so a run for a core version whose dependencies are not
installed silently uses the wrong vendor tree:

```bash
Build/Scripts/runTests.sh -t 13 -p 8.3 -s composerUpdate
Build/Scripts/runTests.sh -t 13 -p 8.3 -s unit
```

| Command                                                                                      | What it does                                                            |
|----------------------------------------------------------------------------------------------|-------------------------------------------------------------------------|
| `runTests.sh -s unit`                                                                        | The whole suite, TYPO3 v13 and PHP 8.2 by default.                      |
| `runTests.sh -t 14 -p 8.5 -s unit`                                                           | The same suite on the other end of the support matrix.                  |
| `runTests.sh -s unitRandom`                                                                  | The suite with `--order-by=random` and a fresh seed.                    |
| `runTests.sh -s unitRandom -o 1234`                                                          | The same, with a fixed seed — how a random-order failure is reproduced. |
| `runTests.sh -s unit packages/fgtclb/academic-persons/Tests/Unit`                            | Restricted to one extension.                                            |
| `runTests.sh -s unit packages/fgtclb/academic-persons/Tests/Unit/Domain/Model/EmailTest.php` | Restricted to one file.                                                 |

The trailing path is the usual way to narrow a run, and there is no `-e`
option. Anything else — a `--filter`, a `--group` — goes after a `--`
separator, which every phpunit suite appends to its command:

```bash
Build/Scripts/runTests.sh -s unit -- --filter EmailTest
```

`-o` is parsed in its own `getopts` arm and turns into
`--random-order-seed=<seed>`. It only has an effect together with
`-s unitRandom`; the `unit` suite does not pass `PHPUNIT_RANDOM` on. Prefer it
over `-- --random-order-seed=<number>`, which reaches phpunit as well but says
the same thing twice as long.

`unitRandom` is not decoration. CI runs it right after `unit` in the same job —
the *Execute unit tests in random order* step — because ordering dependencies
between tests are exactly the kind of defect a fixed order hides.

## Discovery

There is no per-extension PHPUnit configuration. Two globs in
[`Build/phpunit/UnitTests.xml:36-51`](../../Build/phpunit/UnitTests.xml#L36-L51)
collect every extension's unit tests into a single suite:

```xml
<testsuites>
    <testsuite name="Unit tests">
        <directory>../../packages/*/*/Tests/Unit/</directory>
        <directory>../../packages-dev/*/Tests/Unit/</directory>
    </testsuite>
</testsuites>
```

Three things follow from those two lines.

**A new extension needs no configuration change.** Drop it under
`packages/<vendor>/<dir>/` with a `Tests/Unit/` folder and it is in the suite.

**`packages-dev/` is covered too**, and the second glob is why: the seed
definition of [`packages-dev/dev-site/`](../../packages-dev/dev-site) carries
tests of its own, and a suite that does not collect them reports the seed as
green because it never looked at it.
[`packages-dev/testing-helper/`](../../packages-dev/testing-helper) has no tests
of its own all the same — its traits are exercised only through the extensions
that use them.

**Test classes are autoloaded, not included.** Each extension registers its own
`Tests/` namespace as `autoload-dev`, for example
`FGTCLB\AcademicPersons\Tests\` → `Tests/` in
[`academic-persons/composer.json`](../../packages/fgtclb/academic-persons/composer.json).
A test class in the wrong namespace for its path is not found, and PHPUnit
reports nothing rather than an error.

## Conventions

These are read off the existing classes, not prescribed from outside. All of
them follow the shape below.

**Every test class is `final`, declares `strict_types`, and extends
`TYPO3\TestingFramework\Core\Unit\UnitTestCase`.** There is no local abstract
unit test case, and no unit test extends another test class.

**The namespace mirrors the path, which mirrors `Classes/`.**
`FGTCLB\AcademicPersons\Tests\Unit\Domain\Model\EmailTest` tests
`FGTCLB\AcademicPersons\Domain\Model\Email`. Finding the test of a class is a
path transformation, never a search.

**Test methods carry `#[Test]`, never a `test` prefix.** There is not a single
`public function test…()` in the repository. Method names are readable
statements about the subject, which is what turns the PHPUnit output into a
specification:

```php
#[Test]
public function getSortingReturnsIntegerZeroForNewModel(): void
{
    $this->assertSame(0, (new Email())->getSorting());
}
```

— [`EmailTest.php:37-41`](../../packages/fgtclb/academic-persons/Tests/Unit/Domain/Model/EmailTest.php#L37-L41)

**Data providers are `public static` and return a `\Generator` with named data
sets.** The name is what a failure report shows, so it describes the case rather
than numbering it:

```php
public static function returnsExpectedTcaArrayDataSet(): \Generator
{
    yield 'custom tab is added after the general tab not moving palettes to wrong tab for all types' => [
        // …
    ];
}
```

— [`TcaManipulatorTest.php:15-17`](../../packages/fgtclb/academic-base/Tests/Unit/TcaManipulatorTest.php#L15-L17)

**Non-obvious tests carry a docblock that says why, not what.** The assertion
already says what. The docblock records the defect the test descends from, so
that a later reader does not "simplify" it away:

```php
/**
 * A registry nobody attached anything to is a real state: only three extensions of
 * this repository ship a `Configuration/CategoryTypes.yaml`, so an installation
 * using `EXT:category_types` alone never fills it. `attach()` also returns early for
 * an empty argument list, which is what the loader passes in that case.
 */
#[Test]
public function freshRegistryReportsNoTypes(): void
```

— [`CategoryTypeRegistryTest.php:31-38`](../../packages/fgtclb/typo3-category-types/Tests/Unit/Registry/CategoryTypeRegistryTest.php#L31-L38)

**Fixtures are built by a private helper on the test class, not by a data
provider full of literals.** `CategoryTypeRegistryTest` has a `categoryType()`
factory with named defaults
([lines 15-29](../../packages/fgtclb/typo3-category-types/Tests/Unit/Registry/CategoryTypeRegistryTest.php#L15-L29)),
so each test names only the property it is about.

**Expected exceptions are pinned by code, not only by class.**
`$this->expectExceptionCode(1683633304209)` next to
`$this->expectException(\InvalidArgumentException::class)` keeps the test from
passing on a different `\InvalidArgumentException` thrown three frames earlier.

**No assertion-free test without a reason.** `beStrictAboutTestsThatDoNotTestAnything`
is relaxed (see [PHPUnit configuration](phpunit-configuration.md)), so PHPUnit
will not catch one for you.

## Core-version-aware unit tests

`runTests.sh` always appends `--exclude-group not-core-${CORE_VERSION}` to the
PHPUnit call, in both the `unit` and the `unitRandom` arm. The group names read
as what they exclude:

| Group         | Runs on | Meaning                      |
|---------------|---------|------------------------------|
| `not-core-13` | v14     | Excluded from a `-t 13` run. |
| `not-core-14` | v13     | Excluded from a `-t 14` run. |
| *(none)*      | both    | The normal case.             |

Use them for a single method or for a class that differs only in expectations. A
whole class that only exists for one version belongs in a `Core13/` or `Core14/`
subfolder instead.

The real example is the pair at the end of
[`academic-base/Tests/Unit/TcaManipulatorTest.php`](../../packages/fgtclb/academic-base/Tests/Unit/TcaManipulatorTest.php#L561-L597).
The same production method is asserted twice, once per core version, because the
two versions want *different and mutually incompatible* shapes for a plugin's
FlexForm data structure:

```php
/**
 * A plugin content element has no `list_type`, so `*,<CType>` is the key.
 * Assigning it to the record type instead is what left the backend showing
 * core's own default data structure.
 */
#[Group('not-core-14')]
#[Test]
public function pluginFlexFormIsAssignedToTheGlobalColumnOnCoreV13(): void

/**
 * TYPO3 v14 resolves the data structure through the record type of the TCA
 * schema and requires the string; an array leaves the FlexForm tab empty.
 */
#[Group('not-core-13')]
#[Test]
public function pluginFlexFormIsAssignedAsStringOnCoreV14(): void
```

Stating both sides rather than testing only the current one is the point: the
defect behind them (ACE-293) was a v14-shaped registration that made all fifteen
plugin content elements unopenable in the v13 backend, while every gate stayed
green. The functional counterpart of these two tests is described in
[Testing helper](testing-helper.md#pluginflexformdatastructuretrait).

Both methods start with `unset($GLOBALS['TCA']['tt_content'])`. That is safe
because `backupGlobals="true"` restores `$GLOBALS` after each test.

## The version compatibility test

Every extension ships a `Tests/Unit/VersionCompatTest.php` that is nothing but a
`use` statement:

```php
final class VersionCompatTest extends UnitTestCase
{
    use ExtensionCoreVersionCompatTestsTrait;
}
```

— [`academic-persons/Tests/Unit/VersionCompatTest.php`](../../packages/fgtclb/academic-persons/Tests/Unit/VersionCompatTest.php)

The trait asserts that the running TYPO3 major version is one of the two the
branch supports, and — via `not-core-*` groups — that the v13 leg really runs on
13 and the v14 leg really runs on 14. It is the tripwire for "the harness
installed a different core than the run asked for", which is otherwise a
confusing pile of unrelated failures. See
[Testing helper](testing-helper.md#extensioncoreversioncompatteststrait).

`EXT:category_types` names its copy `VersionCompareTest.php`
([`typo3-category-types/Tests/Unit/VersionCompareTest.php`](../../packages/fgtclb/typo3-category-types/Tests/Unit/VersionCompareTest.php));
the content is identical. Every extension also carries the same test in its
functional suite.

## See also

- [PHPUnit configuration](phpunit-configuration.md)
- [Functional tests](functional-tests.md)
- [Testing helper](testing-helper.md)
