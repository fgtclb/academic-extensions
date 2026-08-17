# Core version aware code

Branch `2` (`2.4.x-dev`) supports **TYPO3 v12 and v13 from one code base**. Code
that cannot be written for both at once is *core version aware*: it asks the
running TYPO3 version and picks a form, or it exists once per version and only
the matching variant is used.

This page documents what this branch does today. Both options are in use here —
the folder split is not hypothetical on this branch.

## Four mechanisms are in use

They differ by *where* the difference sits, not by preference:

| Mechanism                                  | Used in                                                        | Count                  |
|--------------------------------------------|----------------------------------------------------------------|------------------------|
| Class folder split                         | `academic-base/Classes/Core12/` and `Classes/Core13/`          | 4 files per version    |
| Version dependent resource path            | `packages/fgtclb/*/Configuration/TCA/Overrides/tt_content.php` | 5 files, 12 call sites |
| Version switch inside a configuration file | `packages/fgtclb/*/ext_localconf.php`                          | 3 files                |
| Version switch inside a PHP class          | `academic-base/Classes/Extbase/Property/TypeConverter/`        | 1 file, 1 switch       |

All of them switch on `(new Typo3Version())->getMajorVersion()`. There is no
`EXT_CONSTANTS.php` anywhere in this branch.

## The folder split in `academic-base`

[`packages/fgtclb/academic-base/Classes/Core12/`](../../packages/fgtclb/academic-base/Classes/Core12/)
and `Classes/Core13/` each hold the same four files:

| File                                         | Kind      |
|----------------------------------------------|-----------|
| `Environment/StateManager.php`               | class     |
| `Environment/FrontendEnvironmentBuilder.php` | class     |
| `Environment/State.php`                      | class     |
| `Environment/ExtendedStateInterface.php`     | interface |

Three properties make this work, and all three are worth copying:

**The version folders sit *inside* `Classes/`.** The namespace still gets the
core version as its third level — `FGTCLB\AcademicBase\Core12\Environment\` —
but because the folder is below `Classes/`, the existing PSR-4 root in
`composer.json` already covers it. No second and third `autoload.psr-4` entry
has to be maintained.

**Only the matching folder is wired into the container.** Composer autoloads
both on every core version, which is unavoidable and harmless as long as a
class from the wrong version is never *instantiated*. The selection happens in
[`packages/fgtclb/academic-base/Configuration/Services.php`](../../packages/fgtclb/academic-base/Configuration/Services.php),
which builds the namespace prefix from the running major version:

```php
$coreVersionRelatedBaseNamespace = 'FGTCLB\\AcademicBase\\Core' . $majorVersion . '\\';
$services
    ->set($coreVersionRelatedBaseNamespace . 'Environment\\StateManager')
    ->autoconfigure()
    ->autowire()
    ->public();
```

The two per-version services are then bound to the shared interfaces with an
alias (`StateManagerInterface`) and to a constructor argument of
`EnvironmentBuilderFactory` with `service()`. Consumers only ever type hint the
interfaces in `Classes/Environment/`.

The string concatenation is deliberate — the file says so in a comment. A
`sprintf()` with a literal class-string would give PHPStan a class name it can
resolve, and it would then complain about the version it is not analysing.

**Every class in the version folders carries `#[Exclude]`.** Without it the
`$services->load('FGTCLB\\AcademicBase\\', '../Classes/*')` in
`Configuration/Services.yaml` would pull *both* versions into the container at
compile time, and the wrong one references core API that does not exist. The
classes then have to be re-enabled explicitly in `Services.php`, which is what
the `->autoconfigure()->autowire()->public()` calls above are for. The docblock
of each class records this; see
[`Classes/Core13/Environment/StateManager.php`](../../packages/fgtclb/academic-base/Classes/Core13/Environment/StateManager.php)
lines 16–29.

Note the shape this produces, because it is the reusable part:

1. An **interface** in `Classes/` — `StateManagerInterface`,
   `EnvironmentBuilderInterface`, `StateInterface`.
2. Shared behaviour in **traits** in `Classes/` —
   `StateManagerExecuteMethodTrait`,
   `StateManagerRootStateInterfaceHelperMethodsTrait`.
3. One implementation per core version, selected in `Services.php`.

Steps 1 and 2 are worth having without any split.

**These specific classes are on their way out.** All of them are marked
`@deprecated since academic_base 2.4.0, will be removed in academic_base 3.0.0`
in favour of the standalone `fgtclb/environment-state-manager` extension, which
`academic-base` already requires. The split is documented here as the pattern,
not as a place to add code.

## A version dependent resource path

TCA is loaded by TYPO3 from a fixed path and cannot be swapped per core
version, but a *file reference inside it* can. Five
`Configuration/TCA/Overrides/tt_content.php` files resolve the major version
once at the top and interpolate it into the FlexForm data structure path:

```php
$typo3MajorVersion = (new Typo3Version())->getMajorVersion();
// ...
ExtensionManagementUtility::addPiFlexFormValue(
    '*',
    sprintf('FILE:EXT:academic_partners/Configuration/FlexForms/Core%s/ListSettings.xml', $typo3MajorVersion),
    'academicpartners_list',
);
```

12 call sites across
`academic-persons` (6), `academic-jobs` (2), `academic-partners` (2),
`academic-programs` (1) and `academic-bite-jobs` (1) do this, against 9 FlexForm
files per core version — 18 XML files in total, under
`Configuration/FlexForms/Core12/` and `Core13/` (`academic-jobs` spells the
directory `Flexforms/`).

What actually differs between the two copies is the TCA `items` array of the
`select` fields: the `Core12/` files use indexed keys
(`<numIndex index="0">` label, `<numIndex index="1">` value), the `Core13/`
files use the associative `<label>` / `<value>` form. Associative keys were
introduced in TYPO3 v12.3 (Feature #99739) and indexed keys deprecated in the
same release (Deprecation #99739), with a TCA migration in place. Since this
branch requires `^12.4.22`, both forms resolve on both core versions; the
reason for keeping two full copies rather than one associative one is not
recorded in the tree.

`academic-projects/Configuration/TCA/Overrides/tt_content.php` line 12 also
assigns `$typo3MajorVersion`, but never reads it — that extension's FlexForm is
a single `Configuration/FlexForms/ProjectSettings.xml`. It is a leftover
assignment, not a switch.

## A version switch inside a configuration file

Three `ext_localconf.php` files carry a real conditional:

```php
// Starting with TYPO3 v13.0 Configuration/user.tsconfig in an Extension is automatically loaded during build time
// @see https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/13.0/Deprecation-101807-ExtensionManagementUtilityaddUserTSConfig.html
if ($versionInformation->getMajorVersion() < 13) {
    ExtensionManagementUtility::addUserTSConfig('
        @import \'EXT:academic_programs/Configuration/user.tsconfig\'
    ');
}
```

`academic-partners`, `academic-programs` and `academic-projects` each have one.
`ExtensionManagementUtility::addUserTSConfig()` is `@deprecated since TYPO3
v13.0, will be removed in TYPO3 v14.0` — verified in the installed v13 tree at
`.Build/vendor/typo3/cms-core/Classes/Utility/ExtensionManagementUtility.php`
line 626 — so the guard both restores the v12 behaviour and keeps the
deprecation from firing on v13.

Two properties make this readable rather than corrosive:

- The switch guards **one call**, at the top of the file, rather than being
  scattered through it.
- It names the **changelog issue**, so the reason can be looked up rather than
  recalled. The changelogs ship with `typo3/cms-core` under
  `Documentation/Changelog/`.

Dropping the call instead of guarding it is not equivalent: v13 loads
`Configuration/user.tsconfig` by itself, v12 does not and would silently lose
the page TSconfig.

## A switch inside a class

Exactly one class under any `Classes/` directory carries a version switch:
[`packages/fgtclb/academic-base/Classes/Extbase/Property/TypeConverter/FileUploadConverter.php`](../../packages/fgtclb/academic-base/Classes/Extbase/Property/TypeConverter/FileUploadConverter.php)
line 350, in `configureProperties()`:

```php
if ((new Typo3Version())->getMajorVersion() < 13) {
    $this->sourceTypes = ['array'];
    $this->targetType = ExtbaseFileReference::class;
    $this->priority = 10;
}
```

Registering an Extbase type converter through class properties was deprecated
in TYPO3 v12 (Deprecation #94117) in favour of the `extbase.type_converter`
service tag, which `academic-base/Configuration/Services.yaml` lines 20–25
provides. The properties are still set on v12 because code there may read them
back through the deprecated accessors. The method carries a `@todo` naming its
exit condition — dropping it together with the call when v12 support ends.

## The rule

**Keep a one- or two-line difference as a version switch. Reach for a folder
split only when a whole class has to differ.**

The switches above are each one condition applied to one call or one small set
of values, next to a comment explaining the core behaviour and a `@todo` or a
changelog link. Splitting a class in two to express that would cost more than
it saves, and it would double the code to delete when v12 support ends.

The threshold is not the number of switches but their reach: once a class needs
different *dependencies*, different *method signatures*, or an API that does
not exist on the other version at all, a switch cannot express it and the class
has to exist twice — which is exactly why `academic-base` has the split and
nothing else does.

## Static analysis

PHPStan runs per core version, selected by `runTests.sh -t 12|13`, against
`Build/phpstan/Core12/` or `Build/phpstan/Core13/`. Both are level 8
(`phpstan.neon` line 11 of each).

The two `phpstan.neon` files differ in exactly one place — the last two
`excludePaths` entries (lines 19–20), where each configuration excludes the
*other* version's folders:

| File                    | Difference                                                            |
|-------------------------|-----------------------------------------------------------------------|
| `phpstan.neon`          | `excludePaths` — Core12 excludes `Core13/`, Core13 excludes `Core12/` |
| `phpstan-baseline.neon` | separate baselines (226 lines on Core12, 236 on Core13)               |
| `phpstan-constants.php` | none — byte identical, it only defines `ORIGINAL_ROOT`                |

The exclusion covers both `Classes/*/Core1x/*` and `Tests/*/Core1x/*`, and it
is load bearing: analysing v13-only sources against an installed v12 reports
API that legitimately does not exist there.

Because `paths` is `../../../packages` as a whole (line 14) rather than named
subdirectories, a new `Core12/` or `Core13/` folder inside any package is
picked up automatically — but the matching `excludePaths` pattern already
covers it too, since both entries are globs over `packages/fgtclb/*/`.

Note that `paths` also means **`packages-dev/` is not analysed by PHPStan at
all** — all three packages there, including the functional test traits in
`packages-dev/testing-helper/`, sit outside the gate on both core versions. A
core version aware helper added there would get no static analysis; only
`lintPhp` and the tests themselves would cover it.

Both core versions must be checked before opening a pull request, each after
its own `composerUpdate` — `-t` selects configuration only, never the installed
vendor tree.

## APIs that cannot be modernised yet

**There is no such list on this branch.** The three APIs tracked on `main` as
ACE-294 (`Extbase\Annotation\*`, `Install\Updates\*` /
`Install\Attribute\UpgradeWizard`, and `Core\Service\FlexFormService`) are all
deprecated by TYPO3 **v14**, and their replacements only appear in v14. On a
v12 + v13 branch none of that applies: every one of them is a current,
non-deprecated API on both supported versions, and this branch uses all three —
17 `Extbase\Annotation` imports in 13 files, 9 upgrade wizards in 5 extensions,
and one `FlexFormService` call site in
`academic-bite-jobs/Classes/Services/BiteJobsService.php` lines 11 and 33.

That analysis lives on `main`, where it is relevant. Do not port it here.

The one API split that *does* bind this branch is the reverse direction —
something that exists on v13 and not on v12. The case that matters in practice
is `TYPO3\CMS\Core\Attribute\AsEventListener`, which does not exist anywhere in
TYPO3 v12; see
[Dependency injection](dependency-injection.md#attributes-safe-on-both-core-versions).

## Core version aware tests

`Build/Scripts/runTests.sh` always passes
`--exclude-group not-core-${CORE_VERSION}` for the selected core version —
lines 665 (functional), 753 (unit) and 759 (`unitRandom`). A test tagged
`not-core-13` therefore runs on **v12 only**, and `not-core-12` runs on v13
only. Nothing else has to be wired up.

Read the group names as "do not run this on core N". On a branch supporting two
versions that makes `not-core-13` the *older* half, which is the opposite of
what the same string means on `main`.

Two shapes are in use.

**A whole test class that only applies to one version** goes into a
`Core12/`/`Core13/` subfolder of `Tests/Functional`, mirroring the source
split:

- [`packages/fgtclb/academic-base/Tests/Functional/Core12/Environment/StateManagerTest.php`](../../packages/fgtclb/academic-base/Tests/Functional/Core12/Environment/StateManagerTest.php)
- [`packages/fgtclb/academic-base/Tests/Functional/Core13/Environment/StateManagerTest.php`](../../packages/fgtclb/academic-base/Tests/Functional/Core13/Environment/StateManagerTest.php)

The folder alone does **not** exclude anything from phpunit — the test
discovery globs `Tests/Functional/` recursively. Each test method in those
classes still carries the group: three `#[Group('not-core-13')]` methods in the
`Core12/` class (lines 43, 100 and 163) and three `#[Group('not-core-12')]`
methods in the `Core13/` one (lines 43, 105 and 183). The folder is what keeps
PHPStan off the wrong version.

**A single method, or a class that only differs in fixtures**, carries the
group on the method. This is the shape to use when both versions need
asserting, because the two methods then sit next to each other and neither can
be forgotten:
[`packages/fgtclb/academic-base/Tests/Functional/Environment/EnvironmentBuilderFactoryTest.php`](../../packages/fgtclb/academic-base/Tests/Functional/Environment/EnvironmentBuilderFactoryTest.php)
lines 36 and 49 assert that the factory returns the `Core12` builder on v12 and
the `Core13` builder on v13.

The group names are also produced from constants rather than typed, in
[`packages-dev/testing-helper/Classes/FunctionalTestCase/ExtensionCoreVersionCompatTestsTrait.php`](../../packages-dev/testing-helper/Classes/FunctionalTestCase/ExtensionCoreVersionCompatTestsTrait.php)
lines 11–33:

```php
const TYPO3_LOWEST_SUPPORTED_MAJOR_VERSION = 12;
const TYPO3_HIGHEST_SUPPORTED_MAJOR_VERSION = 13;

#[Group('not-core-' . TYPO3_HIGHEST_SUPPORTED_MAJOR_VERSION)]
#[Test]
public function verifyLowestSupportedMajorVersion(): void
```

The trait is used by the `VersionCompatTest` classes in the extensions and
asserts that the running core version is one of the two supported ones. When
the supported range changes, the two constants are the single place to edit.

Note that `--exclude-group` composes with the DBMS exclusion on functional runs
(`--exclude-group not-${DBMS},not-core-${CORE_VERSION}`, line 665), so a test
can be restricted along both axes independently.

## See also

- [Dependency injection](dependency-injection.md)
- [Class design](class-design.md)
- `AGENTS.md` — repository conventions, version support and backport targets
- `Build/Scripts/runTests.sh` — the `-t` core version selector
