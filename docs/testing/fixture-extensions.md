# Fixture extensions

Some things cannot be set up from inside a test method: a TCA table that has to
exist before the instance is built, a service that has to be wired by dependency
injection, a template override that TypoScript must be able to find, an
`ext_localconf.php` that has to run during bootstrap. For those, the test ships
a small TYPO3 extension of its own.

Six such fixture extensions exist, in four of the twelve extensions. That is the
whole population — this is a mechanism used sparingly and only where nothing
smaller works.

## Where they live and what they are

They sit next to the tests that use them, under
`packages/fgtclb/<extension>/Tests/Functional/Fixtures/Extensions/<extension_key>/`:

| Extension key                    | Composer package name                  | Owned by               | Provides                                                                |
|----------------------------------|----------------------------------------|------------------------|-------------------------------------------------------------------------|
| `test_base_dependency_injection` | `tests/base-test-dependency-injection` | `academic-base`        | Two services to resolve through the container, plus `Services.yaml`.    |
| `test_jobcontact_schema`         | `tests/test-jobcontact-schema`         | `academic-jobs`        | `ext_tables.sql` and TCA for a legacy table an upgrade wizard migrates. |
| `test_language_files`            | `tests/language-files`                 | `academic-persons`     | An XLF pair with awkward label keys (dots, dashes).                     |
| `test_messy_profile_factory`     | `tests/test-messy-profile-factory`     | `academic-persons`     | A deliberately misbehaving profile factory and two event listeners.     |
| `test_plugin_templates`          | `tests/plugin-templates`               | `academic-persons`     | Simplified Fluid templates and the TypoScript pointing at them.         |
| `test_category_types_group`      | `tests/category-types-group`           | `typo3-category-types` | A `CategoryTypes.yaml` registering a group, plus a test ViewHelper.     |

Each is a real, complete TYPO3 extension: a `composer.json` of type
`typo3-cms-extension`, an `ext_emconf.php`, and whatever it exists to provide.
Three of the six have a `Classes/` folder with a `TESTS\…` PSR-4 root; the other
three are pure resources. None of them ships an `ext_localconf.php`.

A minimal one, complete:

```json
{
    "name": "tests/plugin-templates",
    "description": "Plugin template overrides for tests",
    "type": "typo3-cms-extension",
    "license": "GPL-2.0-or-later",
    "require": {
        "typo3/cms-core": "^12.4.22 || ^13.4",
        "fgtclb/academic-persons": "~2.4.0@dev"
    },
    "extra": {
        "typo3/cms": {
            "extension-key": "test_plugin_templates",
            "Package": {
                "providesPackages": []
            }
        }
    }
}
```

Note that none of the six declares `extra.typo3/cms.version`. They are not path
repositories, so `sbuerk/extended-path-repository` never reads a version off
them; only `ext_emconf.php` carries one.

— [`test_plugin_templates/composer.json`](../../packages/fgtclb/academic-persons/Tests/Functional/Fixtures/Extensions/test_plugin_templates/composer.json)

The core constraint mirrors
[`packages-dev/monorepo-shared/composer.json`](../../packages-dev/monorepo-shared/composer.json).
A fixture extension pinned to a narrower range than the branch supports would
fail the run for the other core version, so it has to be updated with the rest.

## How they are wired

This is where the mechanism differs from the more common one. The fixture
extensions are **not** path repositories and are **not** required by anything —
neither the root nor the owning extension mentions them in `require` or
`require-dev`. Adding one changes no `composer.json` other than its own.

Instead, the composer plugin `sbuerk/fixture-packages` (required by the root as
`>=0.1.1 <2.0.0` in `require-dev`) discovers them by glob. The root
[`composer.json`](../../composer.json) declares:

```json
"extra": {
    "sbuerk/fixture-packages": {
        "paths": {
            "packages/*/*/Tests/Functional/Fixtures/Extensions/*": [
                "autoload",
                "autoload-dev"
            ],
            "packages/*/*": [
                "autoload-dev"
            ]
        }
    }
}
```

Two globs, two jobs:

- The first finds every fixture extension and merges **both** its `autoload` and
  `autoload-dev` sections into the root autoloader. That is what makes
  `TESTS\TestMessyProfileFactory\Persons\MessyProfileFactory` resolvable at all.
  The three fixture extensions that ship classes declare
  `TESTS\BaseTestDependencyInjection\`, `TESTS\TestMessyProfileFactory\` and
  `TESTS\CategoryTypesGroup\`; each is mapped to its own `Classes/` folder in
  the generated `.Build/vendor/composer/autoload_psr4.php`.
- The second merges the `autoload-dev` of every package, which is how each
  extension's own `FGTCLB\<Name>\Tests\` namespace reaches the root autoloader.

The plugin also writes `.Build/vendor/sbuerk/fixture-packages.php`, a plain PHP
array of every discovered fixture package with its name, type, path and
`extra.typo3/cms`. That file is what turns the packages into something the
testing framework can load — see the next section.

Both effects are produced at install time. **A new fixture extension is
invisible until the next `composerUpdate`**:

```bash
Build/Scripts/runTests.sh -t 12 -p 8.1 -s composerUpdate
```

Skipping that produces a "package not found" style failure that looks like a
typo in `$testExtensionsToLoad`, which is the single most common way to lose an
hour here.

## How the test suite picks them up

The functional bootstrap
([`Build/phpunit/FunctionalTestsBootstrap.php:35-54`](../../Build/phpunit/FunctionalTestsBootstrap.php#L35-L54))
hands the generated data file to `SBUERK\AvailableFixturePackages` and calls
`adoptFixtureExtensions()`, which registers each fixture package with the
testing framework's `ComposerPackageManager`. Its own docblock states the
purpose:

> Automatically add fixture extensions to the `typo3/testing-framework`
> `ComposerPackageManager` to allow composer package name or extension keys of
> fixture extension in `FunctionalTestCase::$testExtensionToLoad`.

The block contains a documented workaround —
`AvailableFixturePackages::$dataFile` is built with a missing slash, so the
correct path is injected by reflection.
See [PHPUnit configuration](phpunit-configuration.md#the-functional-bootstrap)
before touching it.

## Using one in a test

A fixture extension is loaded like any other extension, by **composer package
name**, and referenced in resource paths by **extension key**:

```php
protected function setUp(): void
{
    $this->testExtensionsToLoad = array_unique([
        ...array_values($this->testExtensionsToLoad),
        ...array_values([
            'georgringer/numbered-pagination',
            'tests/plugin-templates',
        ]),
    ]);
    parent::setUp();
}
```

```php
'setup' => [
    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
    'EXT:academic_persons/Configuration/TypoScript/setup.typoscript',
    'EXT:test_plugin_templates/Configuration/TypoScript/setup.typoscript',
    // …
],
```

— [`AcademicPersonsListPluginTest.php:49-57`](../../packages/fgtclb/academic-persons/Tests/Functional/Plugins/AcademicPersonsListPluginTest.php#L49-L57)
and [`:77-82`](../../packages/fgtclb/academic-persons/Tests/Functional/Plugins/AcademicPersonsListPluginTest.php#L77-L82)

Other spellings in use:

```php
$this->testExtensionsToLoad[] = 'tests/test-jobcontact-schema';
```
— [`ContactTcaUpgradeWizardTest.php:17`](../../packages/fgtclb/academic-jobs/Tests/Functional/Upgrades/ContactTcaUpgradeWizardTest.php#L17)

```php
protected array $testExtensionsToLoad = [
    // …
    'tests/language-files',
];
```
— [`ProfileTitleProviderTest.php:27`](../../packages/fgtclb/academic-persons/Tests/Functional/PageTitle/ProfileTitleProviderTest.php#L27)

**The package name is not derivable from the extension key.** All six use the
`tests/` vendor, but the second segment follows no rule: `test_plugin_templates`
is `tests/plugin-templates` (prefix dropped), `test_jobcontact_schema` is
`tests/test-jobcontact-schema` (prefix kept), and `test_base_dependency_injection`
is `tests/base-test-dependency-injection` (words reordered). Read the package
name out of the fixture's `composer.json` rather than guessing it. New fixtures
should prefer the mechanical form — the key with underscores turned into
hyphens — but the existing six are not going to be renamed for cosmetics.

## What a fixture extension is for, and what it is not

The six existing ones show the cases that justify one:

- **Schema and TCA.** `test_jobcontact_schema` ships `ext_tables.sql` and TCA for
  a table the upgrade wizard tests migrate away from. The table has to exist when
  the instance is built.
- **Dependency injection.** `test_base_dependency_injection` and
  `test_messy_profile_factory` ship `Services.yaml` plus classes, so the
  container really wires them.
- **Resources resolved through `EXT:` paths.** `test_plugin_templates` and
  `test_language_files` exist because TypoScript and `LLL:` references need a
  real extension path.
- **Registered configuration.** `test_category_types_group` ships a
  `Configuration/CategoryTypes.yaml` so the registry is filled the way an
  installing extension fills it.

Anything that does *not* need one should not have one. Records go into a CSV
fixture and are imported with `importCSVDataSet()`; TypoScript that is only read
by one test can be a `.typoscript` file under that test's `Fixtures/` folder
without an extension around it — `academic-persons` does exactly that with
`Tests/Functional/Plugins/Fixtures/TypoScript/`. A fixture extension is loaded
for every test of every class that names it, so its side effects are wide;
prefer the narrower tool.

## Adding one

1. Create
   `packages/fgtclb/<extension>/Tests/Functional/Fixtures/Extensions/<extension_key>/`.
2. Write `composer.json` — type `typo3-cms-extension`, a `tests/…` name,
   `extra.typo3/cms.extension-key`, `Package.providesPackages: []`, and a
   `typo3/cms-core` constraint matching `monorepo-shared`
   (`^12.4.22 || ^13.4` on this branch).
3. Write `ext_emconf.php`. The `version` and the `constraints.depends.typo3`
   range are read from it by TYPO3, so keep them consistent with the composer
   file.
4. Add `Classes/` with a `TESTS\<Something>\` PSR-4 root only if classes are
   needed.
5. Run `composerUpdate` for the core version you will test on. Nothing else
   registers the package.
6. Name it in `$testExtensionsToLoad` by its composer package name.

No file outside the new folder has to change.

## See also

- [Functional tests](functional-tests.md)
- [PHPUnit configuration](phpunit-configuration.md)
- [Testing helper](testing-helper.md)
