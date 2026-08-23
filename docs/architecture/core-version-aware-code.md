# Core version aware code

Branch `main` (`3.0.0-dev`) supports **TYPO3 v13 and v14 from one code base**.
Code that cannot be written for both at once is *core version aware*: it asks
the running TYPO3 version and picks a form, or it exists once per version and
only the matching variant is used.

This page documents what this repository does today, which is deliberately the
smaller of those two options.

## There is no `Core13/` / `Core14/` split here

Verified across the whole repository: no `Core13/` or `Core14/` directory
exists under `packages/` or `packages-dev/`. Every core version difference is
currently resolved **inside** the file that has it.

Four mechanisms are in use, and they differ by *where* the difference sits —
not by preference:

| Mechanism                                  | Used in                                    | Count              |
|--------------------------------------------|--------------------------------------------|--------------------|
| Version switch inside a PHP class          | `packages/fgtclb/*/Classes/`               | 1 file, 2 switches |
| Version switch inside a configuration file | `packages/fgtclb/*/Configuration/`         | 12 files           |
| Version switch inside an event listener    | `packages/fgtclb/*/Classes/EventListener/` | 3 files            |
| Version dependent constant                 | `packages/fgtclb/*/EXT_CONSTANTS.php`      | 2 files            |

All of them switch on `(new Typo3Version())->getMajorVersion()`.

### A switch inside a class

Exactly one class under any `Classes/` directory carries a version switch:
[`packages/fgtclb/academic-base/Classes/TcaManipulator.php`](../../packages/fgtclb/academic-base/Classes/TcaManipulator.php).
It has two, and both exist because the two core versions want incompatible
input for the same job.

**`addContentElementPlugin()` — line 137.** The signature of
`ExtensionManagementUtility::addPlugin()` changed. On v13 it takes
`($item, $type, $extensionKey)` and only registers a CType when `$type` is
`ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT`. TYPO3 v14 removed the
`list_type` sub-type concept: `addPlugin()` takes `($item, $flexForm)` and
always registers the plugin value as a first-class CType, so passing the old
arguments is a signature error. The wrapper builds an argument array and
spreads it, so the argument count is resolved at runtime and static analysis
does not flag either version:

```php
$arguments = [$item];
if ((new Typo3Version())->getMajorVersion() < 14) {
    $arguments[] = ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT;
    $arguments[] = $extensionKey;
}
ExtensionManagementUtility::addPlugin(...$arguments);
```

**`addContentElementPluginFlexForm()` — line 168.** The FlexForm `ds` shape
differs, and **neither version tolerates the other's**:

```php
$GLOBALS['TCA']['tt_content']['types'][$cType]['columnsOverrides']['pi_flexform']['config']['ds']
    = (new Typo3Version())->getMajorVersion() >= 14
        ? $dataStructure
        : ['default' => $dataStructure];
```

The two failure modes are not symmetric, which is why this is worth spelling
out (the reasoning is recorded in the method's own docblock, lines 145–158):

- **v13 given a string** resolves `ds` through `ds_pointerField` and requires
  an array. It throws from `FlexFormTools` (code 1463826960) and the content
  element can no longer be opened in the backend at all — loud, and obvious.
- **v14 given an array** resolves the data structure through the record type of
  the TCA schema and requires the string. It throws `InvalidTcaException` (code
  1751796940) — but `TcaFlexPrepare` catches it, so the backend silently
  renders an **empty** FlexForm tab. Nothing fails visibly; the plugin just
  loses its settings.

A wrong guess in this one place is therefore not caught by "the backend still
loads". Both directions are covered by tests, see
[Core version aware tests](#core-version-aware-tests).

### A switch inside a configuration file

TCA, TypoScript and `ext_localconf.php` are loaded by TYPO3 from a fixed path
and cannot be swapped per core version. A difference there has to be resolved
in the file. 12 files under `packages/fgtclb/*/Configuration/` do this, and
they follow a consistent shape: build the array, adjust it at the end, return
it. For example
[`packages/fgtclb/academic-jobs/Configuration/TCA/tx_academicjobs_domain_model_job.php`](../../packages/fgtclb/academic-jobs/Configuration/TCA/tx_academicjobs_domain_model_job.php)
lines 386–394:

```php
// The 'searchFields' TCA ctrl option was removed in TYPO3 v14 (Breaking #106972);
// v14 makes suitable field types searchable by default. Keep the explicit
// inclusion list on v13, which still evaluates 'searchFields'.
// @todo Remove once TYPO3 v13 support is dropped.
if ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() < 14) {
    $tcaConfiguration['ctrl']['searchFields'] = '...';
}

return $tcaConfiguration;
```

Three properties make this readable rather than corrosive, and they are worth
copying:

- The switch is **at the end**, applied to the finished array, not scattered
  through it.
- It carries a **`@todo` naming its exit condition**. A version switch without
  one becomes permanent.
- It names the **changelog issue**, so the reason can be looked up rather than
  recalled. The changelogs ship with `typo3/cms-core` under
  `Documentation/Changelog/`.

Dropping the option instead of guarding it is not equivalent: v14 removed it,
but v13 still evaluates it and would search nothing without it.

### A switch inside an event listener

`academic_programs`, `academic_projects` and `academic_partners` each register a
page type, and each needs both forms of it: the TCA option `allowedRecordTypes`
that TYPO3 v14 reads, and the `PageDoktypeRegistry` that v13 still resolves
allowed tables through. Only the first of the two can live in
`Configuration/TCA/Overrides/pages.php`. The second is done by a listener on
`BootCompletedEvent`, one per extension, e.g.
[`packages/fgtclb/academic-programs/Classes/EventListener/RegisterAcademicPageDoktype.php`](../../packages/fgtclb/academic-programs/Classes/EventListener/RegisterAcademicPageDoktype.php):

```php
public function __invoke(BootCompletedEvent $event): void
{
    if ((new Typo3Version())->getMajorVersion() >= 14) {
        return;
    }

    $this->pageDoktypeRegistry->add(PageTypes::TYPE_ACADEMIC_PROGRAM, ['allowedTables' => '*']);
}
```

**Why not in the TCA override.** On v13 the first call to
`PageDoktypeRegistry->add()` runs `initializeTca()`, which collects every table
declaring `security.ignorePageTypeRestriction` — `tt_content`, `sys_template`,
`backend_layout` — from `TcaSchemaFactory` and then latches
`$tcaHasBeenInitialized`. A TCA override runs *before*
`TcaSchemaFactory::load()`, which `Bootstrap::init()` calls on the line right
before it dispatches `BootCompletedEvent`, so the factory is still empty, the allow list of the `default` page type never gains
`tt_content`, and the DataHandler refuses content elements on every standard
page — but only while the TCA cache is cold, which is every functional test,
every CLI import after a cache flush, and the first backend request after one
(ACE-462). Warm, the override does not run at all and the page type is simply
not registered, so it falls back to the `default` allow list instead of `*`.

**Why not `ext_tables.php`.** That file is loaded after the TCA and would fix
both halves — it is what `PageDoktypeRegistry`'s own class docblock suggests —
but TYPO3 v14.3 deprecates *loading an `ext_tables.php` at all*, per extension
and independently of its content (`ExtTablesFactory`, lines 73 and 110). Since
the functional suites run with `failOnDeprecation`, shipping one turns the whole
v14 suite red and writes two deprecations per request into every v14
installation's log. `BootCompletedEvent` is dispatched one line after
`TcaSchemaFactory::load()`, on every request and in every context, and carries
no deprecation on either version.

The v14 copy of `add()` does none of the latching; it assigns and raises the v15
deprecation. That is why the guard is a `Typo3Version` check and not a feature
detection: the method exists on both versions, so there is nothing to detect.
The listener itself is registered on both versions through TYPO3's
`#[AsEventListener]` and returns immediately on v14 — cheaper and simpler than
making the service registration itself version aware.

### A constant, when the difference is inside an attribute argument

The fourth mechanism exists because the other three are unavailable. PHP attribute
arguments must be constant expressions, so a version switch cannot be written
inside one. The Extbase `#[Cascade]` attribute takes the array form
`['value' => 'remove']` on v13 and the plain string `'remove'` on v14, and
passing the wrong one is fatal.

[`packages/fgtclb/academic-persons/EXT_CONSTANTS.php`](../../packages/fgtclb/academic-persons/EXT_CONSTANTS.php)
lines 28–34 resolves this by defining the value once, before the models are
loaded:

```php
defined('ACADEMIC_PERSONS_CASCADE_REMOVE')
    || define(
        'ACADEMIC_PERSONS_CASCADE_REMOVE',
        (new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() >= 14
            ? 'remove'
            : ['value' => 'remove']
    );
```

Domain models then write `#[Cascade(ACADEMIC_PERSONS_CASCADE_REMOVE)]`. The
file is loaded twice on purpose — through `autoload.files` in `composer.json`
(Composer mode) and through a `require_once` in `ext_localconf.php` (Classic
mode) — and the `defined()` guard makes the double include harmless.

`academic_persons` and `academic_jobs` each ship one, with their own constant
name. Both are excluded from PHPStan (`Build/phpstan/Core*/phpstan.neon` line
20) and mirrored for analysis in `Build/phpstan/Core*/phpstan-constants.php`,
because the constant's *type* differs per core version and static analysis has
to be told which one it is looking at.

## The rule

**Keep a one- or two-line difference as a version switch. Reach for a folder
split only when a whole class has to differ.**

The switches above are each one condition applied to one value, next to a
comment explaining the core behaviour and a `@todo` naming when it goes away.
Splitting a class in two to express that would cost more than it saves, and it
would double the code to delete when v13 support ends.

The threshold is not the number of switches but their reach: once a class needs
different *dependencies*, different *method signatures*, or an API that does
not exist on the other version at all, a switch cannot express it and the class
has to exist twice.

## What a folder split would look like

Not used in this repository — documented so the shape is agreed on before
someone needs it. The technique can be looked up in
`web-vision/deepltranslate-core` or `fgtclb/environment-state-manager`.

Two additional class folders, one per supported core version of the branch,
with the core version as the **third namespace level**:

```json
"autoload": {
    "psr-4": {
        "FGTCLB\\AcademicBase\\": "Classes/",
        "FGTCLB\\AcademicBase\\Core13\\": "Core13/",
        "FGTCLB\\AcademicBase\\Core14\\": "Core14/"
    }
}
```

Composer autoloads **all** of them on every core version. That is unavoidable
and harmless, as long as a class is never *instantiated* on the wrong version.
The selection therefore happens in the dependency injection container, in
`Configuration/Services.php`, which loads only the folder matching the running
major version:

```php
// TYPO3 core-version specific sources: only the folder matching the running
// TYPO3 major version is loaded. The concrete services are published and
// wired through Symfony dependency injection attributes on the classes
// themselves (#[AsAlias], #[Autoconfigure], #[Autowire]).
$majorVersion = (new Typo3Version())->getMajorVersion();
$services->load(
    sprintf('FGTCLB\\AcademicBase\\Core%d\\', $majorVersion),
    sprintf(__DIR__ . '/../Core%d/*', $majorVersion),
);
```

The pattern that makes this work is not the split itself:

1. Declare an **interface** in `Classes/` — consumers only ever type hint it.
2. Put shared behaviour in an **abstract base class** in `Classes/`.
3. Implement it once per core version, each registering itself as the default
   implementation of the interface with `#[AsAlias]`.

Steps 1 and 2 are worth having without any split. Note that adopting this in an
existing extension here also means adopting a `Services.php` that loads
directories, which most extensions in this repository do not currently have —
see [Dependency injection](dependency-injection.md).

## Static analysis

PHPStan runs per core version, selected by `runTests.sh -t 13|14`, against
`Build/phpstan/Core13/` or `Build/phpstan/Core14/`. Both are level 8.

The two `phpstan.neon` files are **byte identical**; what differs between the
directories is:

| File                    | Difference                                                             |
|-------------------------|------------------------------------------------------------------------|
| `phpstan.neon`          | none — identical, `paths` is `../../../packages` in both               |
| `phpstan-baseline.neon` | separate baselines (216 lines on Core13, 231 on Core14)                |
| `phpstan-constants.php` | the `#[Cascade]` constant: array form on Core13, string form on Core14 |

Because `paths` points at `packages` as a whole rather than at named
subdirectories, a new `Core13/` or `Core14/` folder inside a package would be
picked up automatically — no `paths` entry to add. The entry that *would* be
needed is an `excludePaths` one, so that each configuration does **not** analyse
the other version's folder: analysing v14-only sources against an installed v13
reports API that legitimately does not exist there.

Note that `paths` also means **`packages-dev/` is not analysed by PHPStan at
all** — all three packages there, including the functional test traits in
`packages-dev/testing-helper/`, sit outside the gate on both core versions. A
core version aware helper added there would get no static analysis; only
`lintPhp` and the tests themselves would cover it.

Both core versions must be checked before opening a pull request, each after
its own `composerUpdate` — `-t` selects configuration only, never the installed
vendor tree.

## APIs that cannot be modernised yet

Three APIs on `main` have newer replacements that **do not exist on TYPO3 v13**,
so none of them can be migrated while the branch still supports v13. They are
tracked as **ACE-294** (epic) with ACE-295, ACE-296 and ACE-297.

Static analysis and IDE inspections will keep offering the replacements. Ignore
them here: a "helpful" import rewrite is a fatal error on v13.

**What could be verified.** The test vendor tree at `.Build/vendor/typo3/` is
currently installed at **TYPO3 v13.4.34**
(`.Build/vendor/typo3/cms-core/Classes/Information/Typo3Version.php` line 22),
so only the v13 half of each claim is verifiable there. The v14 column below
was checked against the development instance `core-14/vendor/`, which its
tracked `composer.lock` pins to **v14.3.6**.

In all three cases the old name still works on v14 — deprecated, not removed —
so the code compiles and runs on both versions today. What blocks the migration
is the **replacement**, which does not exist on v13.

| API                                                    | On v13.4.34 | On v14.3.6                        | Replacement — absent on v13                       |
|--------------------------------------------------------|-------------|-----------------------------------|---------------------------------------------------|
| `Extbase\Annotation\*`                                 | present     | deprecated, kept as class alias   | `Extbase\Attribute\*`                             |
| `Install\Updates\*`, `Install\Attribute\UpgradeWizard` | present     | deprecated, kept as subclass shim | `Core\Upgrades\*`, `Core\Attribute\UpgradeWizard` |
| `Core\Service\FlexFormService`                         | present     | deprecated, kept as class alias   | `FlexFormTools::convertFlexFormContentToArray()`  |

TYPO3 v14 keeps a deprecated class available by one of **two** mechanisms, and
they look different on disk. Check both before concluding a class is gone —
looking only in the owning package's `Classes/` directory is misleading:

1. **A class alias.** The package declares
   `extra.typo3/class-alias-loader.class-alias-maps` in its `composer.json`,
   pointing at `Migrations/Code/ClassAliasMap.php`. Composer's
   `typo3/class-alias-loader` plugin merges those into
   `vendor/composer/autoload_classaliasmap_static.php`, where the keys are
   **lowercased** — a case-sensitive grep for the original class name finds
   nothing there.
2. **A subclass shim** in a `DeprecatedClasses/` directory, autoloaded by a
   `classmap` entry rather than by PSR-4, so the file path does not follow the
   namespace.

Details, so each row can be re-checked rather than trusted:

- **Extbase annotations** — mechanism 1. `cms-extbase/Classes/Annotation/`
  exists on v13 and not on v14, but
  `cms-extbase/Migrations/Code/ClassAliasMap.php`
  lines 19–24 alias all six old names onto `Extbase\Attribute\*`, and those
  entries are present in the generated map. `cms-extbase/Classes/Attribute/`
  does not exist on v13, so the new names cannot be used. 10 imports in 6
  files, all under `packages/fgtclb/*/Classes/`.
- **`FlexFormService`** — mechanism 1.
  `cms-core/Migrations/Code/ClassAliasMap.php`
  line 19 aliases it onto `FlexFormTools`. The method this repository needs,
  `convertFlexFormContentToArray()`, exists on `FlexFormTools` only on v14
  (line 298); on v13 the class exists without it. One call site:
  `packages/fgtclb/academic-bite-jobs/Classes/Services/BiteJobsService.php`
  lines 11 and 33.
- **Upgrade wizards** — mechanism 2. 9 wizards in 5 extensions import
  `TYPO3\CMS\Install\Attribute\UpgradeWizard`,
  `TYPO3\CMS\Install\Updates\UpgradeWizardInterface` and
  `TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite`. On v14 these classes
  are **not** under `cms-install/Classes/` any more; they live in
  `cms-core/DeprecatedClasses/ext-install/` — nine files under `Updates/` plus
  `Attribute/UpgradeWizard.php` — and are autoloaded through
  `"classmap": ["DeprecatedClasses/ext-install/"]` in `cms-core/composer.json`.
  Each is a one-line shim over the new class, for example:

  ```php
  namespace TYPO3\CMS\Install\Updates;

  use TYPO3\CMS\Core\Upgrades\UpgradeWizardInterface as CoreInterface;

  /**
   * @deprecated since v14.0, will be removed in TYPO3 v15.0. Use \TYPO3\CMS\Core\Upgrades\UpgradeWizardInterface instead.
   */
  interface UpgradeWizardInterface extends CoreInterface {}
  ```

  The wizards therefore load and work unchanged on v14. `Core\Upgrades\*` and
  `Core\Attribute\UpgradeWizard` exist on v14.3.6 and not on v13.4.34, which is
  what blocks the migration, exactly as for the other two rows.

The deprecation of the upgrade wizard shims is **docblock only** — there is no
`trigger_error()` anywhere under `cms-core/DeprecatedClasses/`. It therefore
does not interact with `failOnDeprecation` in the test suites, and no test will
report it. The v15 removal date has to be tracked deliberately; nothing in the
build will raise it.

There are two ways out of the epic, and the choice belongs to it rather than to
an individual change: drop v13 support first (expected), or introduce the
folder split above — disproportionate for roughly 19 call sites.

**Not on this list:** references to core labels marked `x-unused-since="14.0"`.
They look similar, but they are labels on this repository's *own* TCA, so
shipping its own text resolves them today on both core versions (**ACE-298**).

## Core version aware tests

`Build/Scripts/runTests.sh` always passes
`--exclude-group not-core-${CORE_VERSION}` for the selected core version —
lines 659 (functional), 751 (unit) and 757
(`unitRandom`). A test tagged `not-core-13` therefore runs on v14 only, and
vice versa. Nothing else has to be wired up.

Two shapes are in use.

**A whole class that only applies to one version** carries the group on the
class:

```php
#[Group('not-core-13')]
final class AcademicJobsNewJobFormUploadTest extends AbstractAcademicJobsTestCase
```

[`packages/fgtclb/academic-jobs/Tests/Functional/Plugins/AcademicJobsNewJobFormUploadTest.php`](../../packages/fgtclb/academic-jobs/Tests/Functional/Plugins/AcademicJobsNewJobFormUploadTest.php)
line 32, and
[`packages/fgtclb/academic-persons-edit/Tests/Functional/Plugins/AcademicPersonsEditProfileImageUploadTest.php`](../../packages/fgtclb/academic-persons-edit/Tests/Functional/Plugins/AcademicPersonsEditProfileImageUploadTest.php)
line 29. Both carry a docblock naming the core behaviour that forces the
exclusion and a `@todo` to drop the group when v13 support ends.

**A single method, or a class that only differs in fixtures**, carries the
group on the method. This is the shape to use when both versions need
asserting, because the two methods then sit next to each other and neither can
be forgotten:
[`packages/fgtclb/academic-base/Tests/Unit/TcaManipulatorTest.php`](../../packages/fgtclb/academic-base/Tests/Unit/TcaManipulatorTest.php)
lines 565 and 584 pin exactly the FlexForm `ds` shapes described above —
`pluginFlexFormIsAssignedAsArrayOnCoreV13()` with `#[Group('not-core-14')]` and
`pluginFlexFormIsAssignedAsStringOnCoreV14()` with `#[Group('not-core-13')]`.

The `not-core-13` group also appears on four `DeprecatedCoreLabelsTest` classes
(`academic-partners`, `academic-persons`, `academic-contact4pages`,
`academic-jobs`, each at line 19), whose shared trait
[`packages-dev/testing-helper/Classes/FunctionalTestCase/DeprecatedCoreLabelsTrait.php`](../../packages-dev/testing-helper/Classes/FunctionalTestCase/DeprecatedCoreLabelsTrait.php)
documents why the assertion is v14 only.

`AGENTS.md` also describes a `Core13/`/`Core14/` subfolder of `Tests/Unit` or
`Tests/Functional` for a whole version specific test class. That is policy, not
practice: no such directory exists yet, and the class-level group above is what
is actually used.

Note that `--exclude-group` composes with the DBMS exclusion on functional runs
(`--exclude-group not-${DBMS}`, same line 659), so a test can be restricted
along both axes independently.

## See also

- [Dependency injection](dependency-injection.md)
- [Class design](class-design.md)
- `AGENTS.md` — repository conventions, version support and backport targets
- `Build/Scripts/runTests.sh` — the `-t` core version selector
