# Testing helper

[`packages-dev/testing-helper/`](../../packages-dev/testing-helper) is the
composer package `fgtclb/academics-monorepo-testing-helper`. It holds nothing
but three PHP traits — the parts of the test setup that were being copied
between extensions, each one carrying the memory of a defect that made the copy
necessary.

| Trait                                                                           | Purpose                                                             |
|---------------------------------------------------------------------------------|---------------------------------------------------------------------|
| [`ExtensionCoreVersionCompatTestsTrait`](#extensioncoreversioncompatteststrait) | Asserts the run really happens on a supported core version.         |
| [`ExtensionsLoadedTestsTrait`](#extensionsloadedteststrait)                     | Asserts an extension resolves by package name and by extension key. |
| [`TcaHelperMethodsTrait`](#tcahelpermethodstrait)                               | Backs up and restores `$GLOBALS['TCA']` *and* the schema factory.   |

## How an extension gets access

The package is a path package, resolved through the `packages-dev/*` repository
declared in the root [`composer.json`](../../composer.json), and required there
as a development dependency:

```json
"require-dev": {
    "fgtclb/academics-monorepo-testing-helper": "@dev"
}
```

Three consequences, all worth knowing before looking for a missing `require`:

- **No extension requires it.** Not one `packages/fgtclb/*/composer.json`
  mentions the package. It is installed once for the whole mono repository, and
  its `FGTCLB\TestingHelper\` PSR-4 root lands in the root autoloader, which is
  the same autoloader the test suites use. Every extension's tests can therefore
  `use` a trait without declaring anything.
- **The version is not written twice.** `sbuerk/extended-path-repository` derives
  the path package's version from `extra.typo3/cms.version` in the package's own
  `composer.json` — `2.4.0-dev` on this branch, matching every other package
  here. There is no `repositories.*.options.versions` map to keep in sync.
- **It never ships.** `require-dev` in a `project`-type root, in a directory that
  is not split out to a public repository. It exists for this checkout only.

The flip side is that the split repositories do **not** get it. An extension's
tests are only runnable from this mono repository, which is consistent with the
rest of the harness — `Build/phpunit/*.xml` globs across all packages at once,
see [Unit tests](unit-tests.md#discovery).

## Two things the package does not do

**It declares no dependencies.** `packages-dev/testing-helper/composer.json` has
no `require` and no `require-dev` at all — not on `phpunit/phpunit`, not on
`typo3/cms-core`, although the traits use both. Nothing enforces that a
consuming class actually provides `$this->get()` or the assertion methods a
trait calls. Using a trait in a class that does not extend a test case fails at
runtime, not at install time or in static analysis.

**It has no tests of its own.** The PHPUnit test suites glob
`packages/*/*/Tests/…`, which does not reach `packages-dev/`. The traits are
covered only through the extensions that use them.

A namespace quirk follows from the same history: every trait lives under
`FGTCLB\TestingHelper\FunctionalTestCase\`, but
`ExtensionCoreVersionCompatTestsTrait` is used from **unit** tests too. The
namespace records where the traits started, not where they are usable.

---

## `ExtensionCoreVersionCompatTestsTrait`

[`Classes/FunctionalTestCase/ExtensionCoreVersionCompatTestsTrait.php`](../../packages-dev/testing-helper/Classes/FunctionalTestCase/ExtensionCoreVersionCompatTestsTrait.php)

**What it does.** Three tests. `allowedMajorTypo3Version()` asserts the running
TYPO3 major version is one of the branch's two. The other two assert a specific
version, gated so each runs on exactly one leg of the matrix:

```php
#[Group('not-core-' . TYPO3_HIGHEST_SUPPORTED_MAJOR_VERSION)]
#[Test]
public function verifyLowestSupportedMajorVersion(): void

#[Group('not-core-' . TYPO3_LOWEST_SUPPORTED_MAJOR_VERSION)]
#[Test]
public function verifyHighestSupportedMajorVersion(): void
```

The two constants are declared at file scope, lines 11-12:

```php
const TYPO3_LOWEST_SUPPORTED_MAJOR_VERSION = 12;
const TYPO3_HIGHEST_SUPPORTED_MAJOR_VERSION = 13;
```

So the two gated tests carry `#[Group('not-core-13')]` and
`#[Group('not-core-12')]` respectively — see
[Unit tests](unit-tests.md#core-version-aware-unit-tests) for how the group
names read on this branch.

**When to use it.** Already everywhere: every extension carries a
`Tests/Unit/VersionCompatTest.php` *and* a
`Tests/Functional/VersionCompatTest.php` whose entire body is
`use ExtensionCoreVersionCompatTestsTrait;`.
`EXT:category_types` spells the unit one `VersionCompareTest.php`; same content.

**The trap it exists for.** `-t` selects configuration, not dependencies. Running
`-t 13 -s functional` after a `-t 12 -s composerUpdate` uses the v12 vendor tree
with the v13 exclusion group, and the resulting failures point everywhere except
at the cause. These tests fail first and say what happened.

The constants are also the single place the branch's supported range is written
for the test suite. Widening or narrowing support means editing this file — and
forgetting to means every extension's `VersionCompatTest` goes red at once,
which is the intended alarm rather than a nuisance.

---

## `ExtensionsLoadedTestsTrait`

[`Classes/FunctionalTestCase/ExtensionsLoadedTestsTrait.php`](../../packages-dev/testing-helper/Classes/FunctionalTestCase/ExtensionsLoadedTestsTrait.php)

**What it does.** Takes a list of identifiers and asserts
`ExtensionManagementUtility::isLoaded()` returns `true` for each. The data
provider labels every entry by what it is, deciding on the presence of a slash:

```php
yield sprintf("%s: %s", (str_contains($identifier, '/') ? 'composer package name' : 'extension key'), $identifier)
```

**When to use it.** Once per extension — all twelve have one — in a
`Tests/Functional/ExtensionLoadedTest.php` naming both spellings:

```php
final class ExtensionLoadedTest extends AbstractAcademicBaseTestCase
{
    use ExtensionsLoadedTestsTrait;

    private static $expectedLoadedExtensions = [
        // composer package names
        'fgtclb/academic-base',
        // extension keys
        'academic_base',
    ];
}
```

— [`academic-base/Tests/Functional/ExtensionLoadedTest.php`](../../packages/fgtclb/academic-base/Tests/Functional/ExtensionLoadedTest.php)

**The trap it exists for.** In a composer installation an extension has two
names, and they are not derived from each other.
`packages/fgtclb/academic-contact4pages/` ships the extension key
`academic_contacts4pages` — directory, package name and key all differ. Asserting both spellings catches a package that loads under one
identifier and is invisible under the other, which otherwise surfaces much later
as a `LLL:EXT:…` that does not resolve or a TCA override that never applies.

**Watch out.** The trait reads `self::$expectedLoadedExtensions` but does not
declare it. Forgetting the property is a PHP error, not a failed assertion.

---

## `TcaHelperMethodsTrait`

[`Classes/FunctionalTestCase/TcaHelperMethodsTrait.php`](../../packages-dev/testing-helper/Classes/FunctionalTestCase/TcaHelperMethodsTrait.php)

**What it does.** `createTCABackup(bool $force)` snapshots `$GLOBALS['TCA']`;
`restoreTCABackup(bool $throwExceptionWhenBackupDoesNotExists)` puts it back.
The interesting part is the private `updateGlobalTCA()`, which does not only
reassign the global:

```php
$GLOBALS['TCA'] = $tca;
if (class_exists(TcaSchemaFactory::class)) {
    $tcaSchemaFactory = $this->get(TcaSchemaFactory::class);
    $tcaSchemaFactory->load($GLOBALS['TCA'], true);
}
```

**When to use it.** In a test that has to modify TCA at runtime, wrapping the
modification:

```php
protected function setUp(): void
{
    parent::setUp();
    $this->createTCABackup(false);
}

protected function tearDown(): void
{
    $this->restoreTCABackup(true);
    parent::tearDown();
}
```

— [`academic-base/Tests/Functional/Tca/TableConfigurationTest.php:20,25`](../../packages/fgtclb/academic-base/Tests/Functional/Tca/TableConfigurationTest.php#L20-L25)

**The trap it exists for.** From TYPO3 v13 on, `TcaSchemaFactory` holds an
object representation of the global TCA that is immutable by design: writing to
`$GLOBALS['TCA']` does **not** update it, and large parts of the core read the
schema rather than the array. A test that only changes the global therefore runs
half against its modification and half against the original — and the same on
the way back, leaving a stale schema for the next test. The forced
`load($GLOBALS['TCA'], true)` is what keeps the two in step.

On this branch the `class_exists()` guard around that call is a genuine core
version gate: `TYPO3\CMS\Core\Schema\TcaSchemaFactory` was introduced in TYPO3
v13 and does not exist on v12, where the method reduces to the plain assignment.
The trait's own comment says as much. Do not "simplify" the guard away — it is
what makes the trait usable on both core versions this branch supports.

`backupGlobals="true"` in the PHPUnit configuration restores `$GLOBALS` between
tests but knows nothing about the schema factory, so it does not replace this
trait.

Both methods take a boolean that decides whether a misuse throws:
`createTCABackup(false)` refuses to overwrite an existing backup (code
1759190691), `restoreTCABackup(true)` refuses to restore a backup that was never
taken (code 1759190705). Passing the permissive value everywhere defeats the
point — the codes exist so that a `setUp()`/`tearDown()` that drifted out of
pairing is reported as such.

The class docblock carries a `@todo` proposing extraction into a dedicated
public helper package with its own TYPO3 and testing-framework constraints. Not
done; the trait is used by one test class today.

## See also

- [Functional tests](functional-tests.md)
- [Unit tests](unit-tests.md)
- [Fixture extensions](fixture-extensions.md)
- [PHPUnit configuration](phpunit-configuration.md)
