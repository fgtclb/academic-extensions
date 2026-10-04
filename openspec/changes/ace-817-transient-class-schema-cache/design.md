## Context

See `proposal.md` for the motivation. Facts on `main` at `80ffa116c`:

- Twelve abstract test cases, one per extension, and `AbstractSeedTestCase`
  and `SnapshotManifestTest` of `packages-dev/dev-site` extend
  `SBUERK\TYPO3\Testing\TestCase\FunctionalTestCase`. Every other functional
  test class extends one of them, some through further abstract classes of
  their own.
- Two of those abstract test cases declare `setUp()`:
  `AbstractAcademicPersonsTestCase` and `AbstractSeedTestCase`.
- The testing framework (9.7.0) writes the settings of the instance once per
  class, in `FunctionalTestCase::setUp()`, with
  `Testbase::setUpLocalConfiguration()`: the core factory configuration, then
  its own hard coded defaults (`NullBackend` for `hash`, `imagesizes`, `pages`
  and `rootline`), then `$configurationToUseInTestInstance`, each merged with
  `array_replace_recursive()`. It has no hook for a project wide default.
- Test classes assign `$configurationToUseInTestInstance` in their own
  `setUp()`, mostly from `frontendPluginTestConfiguration()`, then call
  `parent::setUp()`. Seven classes override that method to add the `extbase`
  backend, five of them through an alias of the trait method.
- `VariableFrontend` of v13 and v14 skips `serialize()` and `unserialize()`
  for a backend implementing `TransientBackendInterface`.
  `TransientMemoryBackend` and `NullBackend` implement it on both versions. The
  core default for `extbase` is `SimpleFileBackend`.
- PHPUnit 11 runs `#[Before]` methods before `setUp()` unless they declare a
  lower priority, and after it then.

## Goals / Non-Goals

**Goals:**

- Every functional test instance of the repository gets the transient
  `extbase` cache without a line in the test class.
- A test class that configures the `extbase` cache itself gets what it
  configured.
- A functional test case that bypasses the default fails the `unit` run.

**Non-Goals:**

- Making a test class unable to choose a persisted backend again. It may, and
  takes the risk.

## Decisions

### A base class, not a trait

`FGTCLB\TestingHelper\TestCase\FunctionalTestCase`, abstract, extends the test
case of `sbuerk/typo3-site-based-test-trait` and overrides `setUp()`:

```php
$this->configurationToUseInTestInstance = array_replace_recursive(
    self::DEFAULT_INSTANCE_CONFIGURATION,
    $this->configurationToUseInTestInstance,
);
parent::setUp();
```

The merge has to run after the `setUp()` of the test class assigned its
configuration and before the testing framework reads it. That place is a
method in the class hierarchy between the two. The abstract test cases change
one `use` statement each, `extends FunctionalTestCase` stays as it is.

Rejected: a trait with a `setUp()`. A class method replaces a trait method of
the same name without a word, and two abstract test cases declare `setUp()`, so
the trait would be silently inactive exactly where it was used, unless each of
them aliased the trait method and called it.
Rejected: a trait with a `#[Before]` method. It runs before every `setUp()`,
so the assignment of a test class replaces the default again. With a lower
priority it runs after `setUp()`, when the instance is already written.
Rejected: changing `FrontendPluginRenderingTrait::frontendPluginTestConfiguration()`,
as ACE-742 did on branch `2`. Only the classes that use the trait and assign
its result get the default, while Extbase builds class schemata in every test
that maps a record or dispatches an action, and a check could not tell from
the class whether its instance has the setting.

The class is named after the class it extends and lives in
`FGTCLB\TestingHelper\TestCase\`, beside the traits rather than in their
`FunctionalTestCase\` namespace.

### The test class wins

The default is the first argument of `array_replace_recursive()`, so a value a
test class sets replaces it. A class that wants a persisted backend sets one.
The functional test of that rule uses `NullBackend`, which is transient as well
and cannot bring the defect back.

### The check

`packages-dev/monorepo-shared/Tests/Unit/FunctionalTestBaseClassTest.php`,
beside the other repository checks. A data set per package with a
`Tests/Functional/` directory, in `packages/fgtclb/` and `packages-dev/`. Each
PHP file below it, outside `Fixtures/`, is tokenized for the class it
declares, the class is autoloaded, and every subclass of the testing
framework's `FunctionalTestCase` has to be a subclass of the base class. A
class that does not autoload is reported, a package without any functional
test case fails, and a second test asserts that the packages are found.

Reflection rather than reading `extends` from the source: a test class extends
an abstract test case that extends another one, and only the loaded hierarchy
answers the question for every depth. The autoloader of the unit suite already
knows every test namespace, and loading a test class runs nothing.

Rejected: a check in `packages-dev/testing-helper`. Its unit tests test its
build scripts, the checks over every package live in `monorepo-shared`.

### The functional tests

`packages-dev/testing-helper/Tests/Functional/TestCase/` gets two classes,
because the configuration is per class: `FunctionalTestCaseTest` asserts the
`extbase` backend in `TYPO3_CONF_VARS` and on the cache the `CacheManager`
builds, `FunctionalTestCaseConfigurationTest` the same for a class that
configures `NullBackend`. The functional glob collects `packages-dev/*`, so
they need no configuration.

## Risks / Trade-offs

- [A test needs class schemata persisted between requests] → None does today,
  every request of a test runs in the same process, and the class can
  configure a backend.
- [The defect hits a cache other than `extbase`] → Not seen. The check and the
  base class are the place to add it.
- [A functional test class extends the testing framework directly] → The unit
  check fails and names the file.
- [Core fixes the defect, forge #110909] → The setting stays harmless. It can
  go once both supported core versions ship the fix.

## Migration Plan

None. Nothing is shipped.
