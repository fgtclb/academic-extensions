# Testing helper

[`packages-dev/testing-helper/`](../../packages-dev/testing-helper) is the
composer package `fgtclb/academics-monorepo-testing-helper`. It holds nothing
but seven PHP traits — the parts of the test setup that were being copied
between extensions, each one carrying the memory of a defect that made the copy
necessary.

| Trait                                                                           | Purpose                                                             |
|---------------------------------------------------------------------------------|---------------------------------------------------------------------|
| [`ExtensionCoreVersionCompatTestsTrait`](#extensioncoreversioncompatteststrait) | Asserts the run really happens on a supported core version.         |
| [`ExtensionsLoadedTestsTrait`](#extensionsloadedteststrait)                     | Asserts an extension resolves by package name and by extension key. |
| [`FrontendPluginRenderingTrait`](#frontendpluginrenderingtrait)                 | The scaffolding every frontend plugin rendering test needs.         |
| [`PluginFlexFormDataStructureTrait`](#pluginflexformdatastructuretrait)         | Resolves a plugin FlexForm the way FormEngine does.                 |
| [`DeprecatedCoreLabelsTrait`](#deprecatedcorelabelstrait)                       | Guards TCA against core labels TYPO3 v14 retired.                   |
| [`EnsureTtContentListTypeColumnTrait`](#ensurettcontentlisttypecolumntrait)     | Re-creates `tt_content.list_type` where v14 removed it.             |
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
  `composer.json` — `3.0.0-dev` on this branch, matching every other package
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
`typo3/testing-framework`, not on `typo3/cms-core`, although every trait uses
all three. Nothing enforces that a consuming class actually provides
`$this->get()`, `$this->instancePath`, `$this->coreExtensionsToLoad` or the
assertion methods a trait calls. Using a trait in a class that does not extend a
functional test case fails at runtime, not at install time or in static
analysis.

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
const TYPO3_LOWEST_SUPPORTED_MAJOR_VERSION = 13;
const TYPO3_HIGHEST_SUPPORTED_MAJOR_VERSION = 14;
```

**When to use it.** Already everywhere: every extension carries a
`Tests/Unit/VersionCompatTest.php` *and* a
`Tests/Functional/VersionCompatTest.php` whose entire body is
`use ExtensionCoreVersionCompatTestsTrait;`.
`EXT:category_types` spells the unit one `VersionCompareTest.php`; same content.

**The trap it exists for.** `-t` selects configuration, not dependencies. Running
`-t 14 -s functional` after a `-t 13 -s composerUpdate` uses the v13 vendor tree
with the v14 exclusion group, and the resulting failures point everywhere except
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

**When to use it.** Once per extension, in a
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

## `FrontendPluginRenderingTrait`

[`Classes/FunctionalTestCase/FrontendPluginRenderingTrait.php`](../../packages-dev/testing-helper/Classes/FunctionalTestCase/FrontendPluginRenderingTrait.php)

**What it does.** Collects everything a plugin rendering test needs around the
assertion — roughly sixty lines per test class before this trait existed
(ACE-305):

| Member                              | Role                                                                    |
|-------------------------------------|-------------------------------------------------------------------------|
| `FRONTEND_PLUGIN_TEST_BASE`         | `https://www.acme.com/` — the base every helper here assumes.           |
| `frontendPluginTestConfiguration()` | The instance configuration, merged recursively with what the test adds. |
| `addCoreExtensionsToLoad()`         | Appends to `$coreExtensionsToLoad` instead of replacing it.             |
| `addTestExtensionsToLoad()`         | The same for `$testExtensionsToLoad`.                                   |
| `writeFrontendPluginTestSite()`     | Writes the site `acme` with the languages the test passes.              |
| `removeWrittenSiteConfiguration()`  | Removes it again in `tearDown()`.                                       |
| `requestFrontendPage()`             | Fires the sub request, returns the `ResponseInterface`.                 |
| `renderFrontendPage()`              | The same, asserts `200`, returns the body as a string.                  |

**When to use it.** In every test that renders a plugin. The class must also
`use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait` and declare its own
`LANGUAGE_PRESETS` — which languages a test needs is part of what it tests, so
that stays with the test.

```php
protected function setUp(): void
{
    $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
    $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
    parent::setUp();
}

protected function tearDown(): void
{
    $this->removeWrittenSiteConfiguration();
    parent::tearDown();
}
```

**The traps it exists for.** Three, all of them silent:

- `subrequestPageErrors` is switched on in the configuration. Without it the
  frontend swallows the exception of a sub request and answers a **rendered
  error page**, so a test asserting only the status code passes while the plugin
  is broken. `renderFrontendPage()` asserting `200` is only meaningful because
  of that flag.
- A written site configuration outlives the test instance, so the next test
  finds a site it did not write. Hence the explicit `removeWrittenSiteConfiguration()`
  in `tearDown()`.
- The two `add…ToLoad()` helpers exist because assigning `$testExtensionsToLoad`
  in a subclass drops everything the abstract test case declared, and the loss
  is not reported.

---

## `PluginFlexFormDataStructureTrait`

[`Classes/FunctionalTestCase/PluginFlexFormDataStructureTrait.php`](../../packages-dev/testing-helper/Classes/FunctionalTestCase/PluginFlexFormDataStructureTrait.php)

This is the most consequential trait in the package. It exists because of
ACE-293, and it is worth reading in full before changing anything about plugin
registration.

### The defect

The two core versions do not disagree about the *shape* of a plugin's FlexForm
registration. They disagree about **where it has to live**, and getting that
wrong failed twice, in two different ways, before it was understood.

| Core version | Where the data structure belongs                                                                              | How it is written                                                                       |
|--------------|---------------------------------------------------------------------------------------------------------------|-----------------------------------------------------------------------------------------|
| v14          | on the record type — `…['types'][<CType>]['columnsOverrides']['pi_flexform']['config']['ds']`, a plain string | assigned directly                                                                       |
| v13          | in the **global** column configuration, under the key `ds_pointerField` matches                               | `ExtensionManagementUtility::addPiFlexFormValue('*', $ds, $cType)`, writing `*,<CType>` |

**ACE-293, the visible failure.** The v14-shaped registration was introduced
when `addPiFlexFormValue()` was dropped for its v14 deprecation. A plain string
in `columnsOverrides` throws in `FlexFormTools` on v13 (code 1463826960), which
made all fifteen academic plugin content elements unopenable in the v13 backend.
Every gate stayed green, because nothing in the suite compiled a backend form.
The commit message of `c2e8f84de` states the lesson plainly:

> That no test caught this is the point worth keeping: nothing in the suite
> renders or compiles a backend form, so TCA that is fatal in the v13 backend
> passes every gate on both core versions.

**ACE-387, the silent one.** Putting an *array* in `columnsOverrides` stopped the
throw, so v13 records opened again — and still showed the wrong form. Core
resolves a data structure there in two steps:
`getDataStructureIdentifier()` is handed the field TCA with `columnsOverrides`
already merged in and does pick the key from it, but the identifier it builds
carries nothing but that key, and `parseDataStructureByIdentifier()` then reads
the structure back from `$GLOBALS['TCA']` — where the override never was. The
lookup landed on core's own `default` entry, a single field `xmlTitle` labelled
"The Title:", and the plugin options were unreachable. `FlexFormTools` says so
in its own docblock: the TCA for data structure definitions must not be
overridden by `columnsOverrides`.

So `columnsOverrides` is a v14 mechanism, not a shared one with two spellings.
On v13 the registration goes into the global column configuration, under the key
the `ds_pointerField` `list_type,CType` matches. A plugin registered as a
content element has an empty `list_type`, so that key is `*,<CType>` — exactly
what `addPiFlexFormValue()` writes.

The production side of both fixes is the version switch in
`addContentElementPluginFlexForm()` of
[`academic-base/Classes/TcaManipulator.php`](../../packages/fgtclb/academic-base/Classes/TcaManipulator.php);
its unit-level counterpart is the `not-core-13`/`not-core-14` pair described in
[Unit tests](unit-tests.md#core-version-aware-unit-tests).

### How the trait checks it

`resolvePluginFlexFormDataStructure(string $cType)` builds a FormEngine result
array by hand and pushes it through exactly two data providers:

```php
$result = $this->get(TcaColumnsOverrides::class)->addData($result);
$result = $this->get(TcaFlexPrepare::class)->addData($result);
```

Those two are the first in the FormEngine chain that touch `pi_flexform` —
`TcaColumnsOverrides` merges the type-specific `columnsOverrides` into the
columns, `TcaFlexPrepare` resolves the `ds` reference into the parsed structure.
Everything that can go wrong with a plugin data structure goes wrong in one of
them. Running only those two rather than a full `FormDataCompiler` is what keeps
the check cheap: **no backend user, no page tree, no request**, which is why
each extension needs only one small test file.

Two details in the hand-built `$result` are version specific:

- `'list_type' => ''` is present in `databaseRow` because v13 resolves the data
  structure through the `ds_pointerField` `list_type,CType` and needs the field
  in the row. v14 dropped the column entirely.
- `$result['tcaSchemata']` is filled from `TcaSchemaFactory::all()`, which is
  what FormEngine's `InitializeProcessedTca` puts there.

The second one carries a caveat that matters if you copy the pattern. It is
written as a version branch:

```php
if (class_exists(TcaSchemaFactory::class)) {
```

but `TYPO3\CMS\Core\Schema\TcaSchemaFactory` **also exists on TYPO3 v13.4** —
verified against the installed `typo3/cms-core` v13.4.34, which ships
`Classes/Schema/TcaSchemaFactory.php` with both `all()` and `load()`. The branch
is therefore taken on both core versions of this branch, and the key is set in
both cases. That is fine for what the trait does, but **do not read this
construct as a v14 gate and do not reuse it as one.** A genuine gate is
`(new Typo3Version())->getMajorVersion()` or a `not-core-*` group.

### The assertion

`assertPluginFlexFormIsResolved(string $cType, string $sheetName = 'sDEF')` makes
**three** assertions, one per way this has already gone wrong:

```php
self::assertArrayHasKey($sheetName, $dataStructure['sheets'] ?? [], …);
self::assertNotEmpty($resolvedFields, …);
self::assertNotSame($coreDefaultFields, $resolvedFields, …);
```

Asserting the sheet alone would pass on v14 against the empty fallback: when
`TcaFlexPrepare` swallows an unresolvable data structure there, the caller is
left with `['sheets' => ['sDEF' => []]]` — a sheet that exists and contains
nothing. Only the assertion on `ROOT.el` distinguishes "resolved" from
"silently empty".

The third one exists because the first two are not enough. On v13 an
unresolvable structure does not come back empty, it comes back as **core's
default** — and core's default has a field, so "resolved to something" is
satisfied. Seven extensions carried this test and all seven were green while
every one of them rendered the wrong form. The field names it compares against
are parsed out of
`$GLOBALS['TCA']['tt_content']['columns']['pi_flexform']['config']['ds']['default']`
rather than hard coded, so a changed core default cannot quietly turn the guard
into a no-op. TYPO3 v14 has no such fallback entry, so on v14 the third
assertion returns early and the first two carry the check.

**When to use it.** One `Tests/Functional/Tca/PluginFlexFormTest.php` per
extension that registers plugins, listing every CType in a data provider. Seven
extensions have one. Add the CType to the provider in the same change that
registers the plugin.

**Prove it can fail.** A test of this kind is worth exactly as much as its last
failed run, and both defects above passed a test that existed. Break the
registration on purpose — put the v14 assignment back on both branches of
`addContentElementPluginFlexForm()` — watch the suite go red on v13, and
restore. Done while writing this: **15 data sets across the seven extensions**
fail with *"resolved to the TYPO3 core default data structure instead of the one
the extension registers"*.

---

## `DeprecatedCoreLabelsTrait`

[`Classes/FunctionalTestCase/DeprecatedCoreLabelsTrait.php`](../../packages-dev/testing-helper/Classes/FunctionalTestCase/DeprecatedCoreLabelsTrait.php)

**What it does.** Walks the compiled `$GLOBALS['TCA']` recursively for the tables
matching the given prefixes and reports every reference to one of seven core
label keys that TYPO3 v14 retired, together with the replacement key shipped in
`EXT:academic_base/Resources/Private/Language/locallang_tca.xlf`.

**When to use it.** Once per extension that owns TCA tables:

```php
#[Group('not-core-13')]
#[Test]
public function tcaDoesNotReferenceCoreLabelsRetiredInV14(): void
{
    $this->assertTcaHasNoDeprecatedCoreLabelReferences(['tx_academicpersons_']);
}
```

— [`academic-persons/Tests/Functional/Tca/DeprecatedCoreLabelsTest.php`](../../packages/fgtclb/academic-persons/Tests/Functional/Tca/DeprecatedCoreLabelsTest.php)

**The trap it exists for.** TYPO3 v14 marks those labels `x-unused-since="14.0"`
(#107938, #108086). They still resolve, through an `.x-unused` fallback that
raises `E_USER_DEPRECATED` on every backend form render — and with
`failOnDeprecation="true"` that would turn the first backend-form test red on
v14 and leave it green on v13. The v14 language packs also no longer ship
translations for them, so a German backend fell back to English. ACE-298 shipped
21 replacement labels; this trait guards against the next one.

**It must be scoped to TYPO3 v14.** The trait's own docblock is explicit:

> **Assert this on TYPO3 v14 only** (`#[Group('not-core-13')]`). On v13 the
> labels are not deprecated at all, and core's own `TcaEnrichment` adds them to
> every table that declares `transOrigPointerField` without defining the column
> — so a v13 run reports core's labels, which are none of our business.

On v14 that same enrichment uses `core.db.general:l18n_parent`, so whatever the
scan finds there is genuinely ours. Two core-generated false positives were
observed on v13 before the assertion was scoped.

It walks the *compiled* TCA rather than the source files on purpose, so labels
assembled at runtime are covered too.

---

## `EnsureTtContentListTypeColumnTrait`

[`Classes/FunctionalTestCase/EnsureTtContentListTypeColumnTrait.php`](../../packages-dev/testing-helper/Classes/FunctionalTestCase/EnsureTtContentListTypeColumnTrait.php)

**What it does.** `ensureTtContentListTypeColumnExists()` lists the columns of
`tt_content` through the schema manager and, if `list_type` is absent, adds it:

```sql
ALTER TABLE tt_content ADD COLUMN list_type VARCHAR(255) DEFAULT '' NOT NULL
```

On TYPO3 v13 the column already exists and the method returns early.

**When to use it.** In the `setUp()` of an upgrade wizard test that migrates
`list_type` to `CType`. Three tests do:
[`academic-persons/…/ListTypeToCTypeUpgradeWizardTest.php:32`](../../packages/fgtclb/academic-persons/Tests/Functional/Upgrades/ListTypeToCTypeUpgradeWizardTest.php#L32),
[`academic-jobs/…/PluginUpgradeWizardTest.php:22`](../../packages/fgtclb/academic-jobs/Tests/Functional/Upgrades/PluginUpgradeWizardTest.php#L22)
and
[`academic-persons-edit/…/PluginContentWizardTest.php:22`](../../packages/fgtclb/academic-persons-edit/Tests/Functional/Upgrades/PluginContentWizardTest.php#L22).

**The trap it exists for.** TYPO3 v14 removed `tt_content.list_type` together
with the plugin sub-type feature. An upgrade wizard that migrates away from
`list_type` still has to be testable there — the wizard's entire purpose is to
run on installations that still have the column and its data. Without the trait
the v14 leg of those tests cannot even seed its fixture.

Note the column comparison is done on `strtolower($column->getName())`, which is
what makes it reliable across the four supported DBMS.

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

## `Frontend Test JavaScript for: academic-persons-edit`
[Frontend Tests: academic-persons-edit](academic-persons-edit-frontend-tests.md)

## See also

- [Functional tests](functional-tests.md)
- [Unit tests](unit-tests.md)
- [Fixture extensions](fixture-extensions.md)
- [PHPUnit configuration](phpunit-configuration.md)
