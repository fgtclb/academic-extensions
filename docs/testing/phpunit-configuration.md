# PHPUnit configuration

Four files configure both PHPUnit suites of this mono repository. They live
together in [`Build/phpunit/`](../../Build/phpunit), next to the configuration
of every other tool:

| File                                                                                             | Lines | Role                                           |
|--------------------------------------------------------------------------------------------------|-------|------------------------------------------------|
| [`Build/phpunit/UnitTests.xml`](../../Build/phpunit/UnitTests.xml)                               | 49    | PHPUnit configuration of the unit suite.       |
| [`Build/phpunit/UnitTestsBootstrap.php`](../../Build/phpunit/UnitTestsBootstrap.php)             | 101   | Bootstrap referenced by `UnitTests.xml`.       |
| [`Build/phpunit/FunctionalTests.xml`](../../Build/phpunit/FunctionalTests.xml)                   | 49    | PHPUnit configuration of the functional suite. |
| [`Build/phpunit/FunctionalTestsBootstrap.php`](../../Build/phpunit/FunctionalTestsBootstrap.php) | 60    | Bootstrap referenced by `FunctionalTests.xml`. |

Nothing selects them implicitly. `Build/Scripts/runTests.sh` passes the matching
file with `-c` for every suite it starts — see
[`Build/Scripts/runTests.sh:658`](../../Build/Scripts/runTests.sh#L658) for the
functional suite and
[`Build/Scripts/runTests.sh:750`](../../Build/Scripts/runTests.sh#L750) and
[`:756`](../../Build/Scripts/runTests.sh#L756) for `unit` and `unitRandom`.

The installed PHPUnit is `phpunit/phpunit` 11.5.56, against
`typo3/testing-framework` 9.6.1.

## Where the configuration comes from

All four files are copies of the boilerplate that `typo3/testing-framework`
ships in
`.Build/vendor/typo3/testing-framework/Resources/Core/Build/`. That package
states the intent in the file header it also ships:

> This file is loosely maintained within TYPO3 testing-framework, extensions
> are encouraged to not use it directly, but to copy it to an own place […]

"Loosely maintained" is the reason to copy rather than reference: the template
tracks what TYPO3 Core itself needs, changes without a deprecation path, and
would otherwise move under the repository's feet on every dependency update.
Copying makes every deviation below a decision recorded in git rather than a
difference nobody notices.

The trade-off is that the copies do not follow the template forward. When
`typo3/testing-framework` is raised, diffing the four files against the vendor
originals is the only thing that reveals a new upstream default.

## The deliberate deviations

Diffed against the installed template, the copies differ in these points and
nothing else:

| File                           | Template                                                                     | Here                                                                              | Why                                                                                                                              |
|--------------------------------|------------------------------------------------------------------------------|-----------------------------------------------------------------------------------|----------------------------------------------------------------------------------------------------------------------------------|
| both `.xml`                    | `<directory>../../../../../../typo3/sysext/*/Tests/Unit/</directory>`        | `<directory>../../packages/*/*/Tests/Unit/</directory>`                           | The template points into a TYPO3 Core checkout. Here the tests live in the mono repository's package tree.                       |
| both `.xml`                    | `xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/11.2/phpunit.xsd"` | `xsi:noNamespaceSchemaLocation="../../.Build/vendor/phpunit/phpunit/phpunit.xsd"` | The schema of the *installed* PHPUnit, not a pinned remote one. No network access, and it cannot drift from the running version. |
| both `.xml`                    | attribute absent                                                             | `beStrictAboutTestsThatDoNotTestAnything="false"`                                 | See [the strictness policy](#strictness-policy) below.                                                                           |
| `UnitTestsBootstrap.php`       | fully qualified class names inline                                           | `use` imports                                                                     | Cosmetic; the file is linted and CGL-checked like every other PHP file here.                                                     |
| `FunctionalTestsBootstrap.php` | plain `Testbase` setup                                                       | additional fixture package adoption block                                         | See [the functional bootstrap](#the-functional-bootstrap) below.                                                                 |

The `phpunit v10.1 compatible version.` line in both XML headers is a local
addition that no longer describes anything — the configuration is read by
PHPUnit 11 and validates against its schema. Treat it as a leftover, not as a
constraint.

## Strictness policy

Both XML files carry an identical attribute block,
[`UnitTests.xml:20-34`](../../Build/phpunit/UnitTests.xml#L20-L34) and
[`FunctionalTests.xml:20-34`](../../Build/phpunit/FunctionalTests.xml#L20-L34).
The "PHPUnit default" column is read from the schema the installed PHPUnit
ships, `.Build/vendor/phpunit/phpunit/phpunit.xsd`:

| Attribute                                      | Value here | PHPUnit default | Effect                                                                 |
|------------------------------------------------|------------|-----------------|------------------------------------------------------------------------|
| `backupGlobals`                                | `true`     | `false`         | `$GLOBALS` is restored after every test — `$GLOBALS['TCA']` above all. |
| `beStrictAboutTestsThatDoNotTestAnything`      | `false`    | `true`          | **Relaxed.** A test without an assertion is not reported risky.        |
| `cacheResult`                                  | `false`    | `true`          | No result cache, so no ordering that depends on a previous run.        |
| `colors`                                       | `true`     | `false`         | Coloured output.                                                       |
| `displayDetailsOnTestsThatTriggerDeprecations` | `true`     | `false`         | Deprecations are printed with their origin, not only counted.          |
| `displayDetailsOnTestsThatTriggerErrors`       | `true`     | `false`         | Same for errors.                                                       |
| `displayDetailsOnTestsThatTriggerNotices`      | `true`     | `false`         | Same for notices.                                                      |
| `displayDetailsOnTestsThatTriggerWarnings`     | `true`     | `false`         | Same for warnings.                                                     |
| `failOnDeprecation`                            | `true`     | `false`         | A triggered deprecation fails the run.                                 |
| `failOnNotice`                                 | `true`     | `false`         | A notice fails the run.                                                |
| `failOnRisky`                                  | `true`     | `false`         | A risky test fails the run.                                            |
| `failOnWarning`                                | `true`     | `false`         | A warning fails the run.                                               |
| `requireCoverageMetadata`                      | `false`    | `false`         | No coverage annotations required. There is no coverage gate here.      |

Everything the schema knows and the files do not name keeps the PHPUnit
default. Notably absent, and therefore off: `failOnIncomplete`, `failOnSkipped`,
`failOnEmptyTestSuite` and `failOnAllIssues`. `failOnSkipped` staying off is
load-bearing — several tests skip themselves on a core version, see
[Functional tests](functional-tests.md).

### The one relaxation

`beStrictAboutTestsThatDoNotTestAnything="false"` is the only setting that is
weaker than PHPUnit's default, and it is not decoration: with `failOnRisky` set
to `true`, "risky" is fatal, so the two settings have to be read together.
Turning strictness back on would fail assertion-free tests such as

```php
#[Test]
public function canBeCreated(): void
{
    new Email();
}
```

from
[`academic-persons/Tests/Unit/Domain/Model/EmailTest.php:13-17`](../../packages/fgtclb/academic-persons/Tests/Unit/Domain/Model/EmailTest.php#L13-L17).
That test does state something worth stating — the constructor of a model with
no required arguments does not throw — but it has no assertion to prove it with.

The cost is real and worth knowing: a test that lost its assertions in a
refactoring passes silently. When a test *can* assert, it must; the relaxation
exists for the handful that structurally cannot.

### What `failOnDeprecation` means across two core versions

The `main` branch supports TYPO3 v13 and v14 from one code base, and CI runs
every suite once per core version. A deprecation is version specific by nature:
core raises `E_USER_DEPRECATED` for an API on the newer version while the older
one still considers it current. With `failOnDeprecation="true"` this asymmetry
becomes a red matrix leg — the v14 job fails and the v13 job stays green on
exactly the same commit.

Two consequences follow.

**A deprecation cannot be waited out.** The repository fixes the underlying code
instead of relaxing the option. Commit `336cb673f`
(*Clear remaining v14 functional deprecations*) says so explicitly:

> `failOnDeprecation` (and the other phpunit fail\* options) stay enabled; the
> underlying code is fixed for TYPO3 v13 and v14.

**Some deprecations are not ours and still fail the run.** TYPO3 v14 marks a set
of core labels `x-unused-since="14.0"`; they still resolve, through a fallback
in `LanguageService` that raises `E_USER_DEPRECATED` **on every backend form
render**. Because this repository's TCA referenced those labels, any future test
that compiles a backend form would have failed on v14 and passed on v13. That is
why
ACE-298 shipped replacement labels before such tests exist, and why
`DeprecatedCoreLabelsTrait` now guards against the next one — see
[Testing helper](testing-helper.md).

The mirror image also exists: the three APIs listed under "TYPO3 v15 blockers"
in [`AGENTS.md`](../../AGENTS.md) are deprecated on v14 and have no replacement
on v13, so they cannot be migrated while v13 is supported. They are tracked as
ACE-294 rather than silenced.

## The unit bootstrap

[`UnitTestsBootstrap.php`](../../Build/phpunit/UnitTestsBootstrap.php) builds
the smallest TYPO3 that lets a class be instantiated: it sets `TYPO3_PATH_ROOT`
and `TYPO3_PATH_WEB` when they are unset (lines 51-56), runs the testing
framework's `SystemEnvironmentBuilder` (lines 65-71), creates the four
`typo3temp` directories (lines 73-76), initialises `TYPO3_CONF_VARS` from
`ConfigurationManager::getDefaultConfiguration()` (lines 83-84), and installs a
`UnitTestPackageManager` backed by a `NullBackend` cache (lines 86-96).

There is no database and no site. A unit test that needs either is a functional
test.

One inherited oddity is worth recognising before it is "fixed": line 65 reads

```php
$hasConsolidatedHttpEntryPoint = class_exists(CoreHttpApplication::class);
```

`CoreHttpApplication` is neither imported nor namespace-qualified, so it
resolves to the global namespace, where no such class is defined in the
installed vendor tree. The condition is therefore always false and the `else`
branch always runs. The line is identical in the upstream template
(`.Build/vendor/typo3/testing-framework/Resources/Core/Build/UnitTestsBootstrap.php:54`),
so this is inherited, not introduced here — change it upstream, not in the copy.

## The functional bootstrap

[`FunctionalTestsBootstrap.php`](../../Build/phpunit/FunctionalTestsBootstrap.php)
is short, and almost all of it is the one deviation from the template
(lines 28-54). Before the usual `Testbase` calls it instantiates
`SBUERK\AvailableFixturePackages` and calls `adoptFixtureExtensions()`, which
registers the repository's test-only fixture extensions with the testing
framework's `ComposerPackageManager`. That is what lets a test name a fixture
extension by composer package name or extension key in `$testExtensionsToLoad`
— see [Fixture extensions](fixture-extensions.md).

The block carries a documented workaround: `AvailableFixturePackages::$dataFile`
holds a path that is missing a slash between vendor name and data file, so the
generated `.Build/vendor/sbuerk/fixture-packages.php` is never read. The
bootstrap sets the private property to the correct path by reflection
(lines 43-51) and marks the end of the workaround with a comment. Delete the
block only after checking that the released `sbuerk/fixture-packages` — 1.1.3 at
the time of writing — builds the path correctly; without it every fixture
extension becomes unresolvable at once.

The `class_exists()` guard around the whole block means the suite still boots
when `sbuerk/fixture-packages` is not installed. Tests that load a fixture
extension then fail, the run does not.

## See also

- [Unit tests](unit-tests.md)
- [Functional tests](functional-tests.md)
- [Fixture extensions](fixture-extensions.md)
- [Testing helper](testing-helper.md)
