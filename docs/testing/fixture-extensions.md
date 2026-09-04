# Fixture extensions

Some things cannot be set up from inside a test method: a TCA table that has to
exist before the instance is built, a service that has to be wired by dependency
injection, a template override that TypoScript must be able to find, an
`ext_localconf.php` that has to run during bootstrap. For those, the test ships
a small TYPO3 extension of its own.

Thirteen such fixture extensions exist, in six of the twelve extensions. That is
the whole population — this is a mechanism used sparingly and only where nothing
smaller works. Measured with

```bash
find packages/fgtclb/*/Tests/Functional/Fixtures/Extensions -mindepth 1 -maxdepth 1 -type d | wc -l
find packages/fgtclb/*/Tests/Functional/Fixtures/Extensions -mindepth 1 -maxdepth 1 -type d \
  | cut -d/ -f3 | sort -u | wc -l
```

## Where they live and what they are

They sit next to the tests that use them, under
`packages/fgtclb/<extension>/Tests/Functional/Fixtures/Extensions/<extension_key>/`:

| Extension key                    | Composer package name                  | Owned by                | Provides                                                                |
|----------------------------------|----------------------------------------|-------------------------|-------------------------------------------------------------------------|
| `test_base_dependency_injection` | `tests/base-test-dependency-injection` | `academic-base`         | Two services to resolve through the container, plus `Services.yaml`.    |
| `test_bitejobs_stub`             | `tests/test-bitejobs-stub`             | `academic-bite-jobs`    | An `ext_localconf.php` replacing the Guzzle handler stack.              |
| `test_contract_contact_actions`  | `tests/test-contract-contact-actions`  | `academic-persons-edit` | A `Settings.yaml` narrowing the actions of the contracts section.       |
| `test_current_color_icons`       | `tests/current-color-icons`            | `academic-base`         | Icons registered through the `currentColor` icon provider.              |
| `test_exclude_file_column`       | `tests/test-exclude-file-column`       | `academic-persons`      | A TCA override adding an `l10n_mode=exclude` file column to profiles.   |
| `test_jobcontact_schema`         | `tests/test-jobcontact-schema`         | `academic-jobs`         | `ext_tables.sql` and TCA for a legacy table an upgrade wizard migrates. |
| `test_language_files`            | `tests/language-files`                 | `academic-persons`      | An XLF pair with awkward label keys (dots, dashes).                     |
| `test_legacy_settings`           | `tests/test-legacy-settings`           | `academic-persons`      | A `Settings.yaml` in the pre-3.0 shape, the 2.x manual's override.      |
| `test_legacy_year_columns`       | `tests/test-legacy-year-columns`       | `academic-persons`      | `ext_tables.sql` re-declaring three columns an upgrade wizard migrates. |
| `test_messy_profile_factory`     | `tests/test-messy-profile-factory`     | `academic-persons`      | A deliberately misbehaving profile factory and two event listeners.     |
| `test_plugin_templates`          | `tests/plugin-templates`               | `academic-persons`      | Simplified Fluid templates and the TypoScript pointing at them.         |
| `test_public_profile_settings`   | `tests/test-public-profile-settings`   | `academic-persons`      | A `Settings.yaml` overriding the public profile layout.                 |
| `test_category_types_group`      | `tests/category-types-group`           | `typo3-category-types`  | A `CategoryTypes.yaml` registering a group, plus a test ViewHelper.     |

Each is a real, complete TYPO3 extension: a `composer.json` of type
`typo3-cms-extension`, an `ext_emconf.php`, and whatever it exists to provide.
Four of the thirteen have a `Classes/` folder with a `TESTS\…` PSR-4 root; the
other nine are pure resources.

A minimal one, complete:

```json
{
    "name": "tests/plugin-templates",
    "description": "Plugin template overrides for tests",
    "type": "typo3-cms-extension",
    "license": "GPL-2.0-or-later",
    "require": {
        "typo3/cms-core": "~13.4.0@dev || ~14.3.6@dev",
        "fgtclb/academic-persons": "~3.0.0@dev"
    },
    "extra": {
        "typo3/cms": {
            "extension-key": "test_plugin_templates",
            "version": "3.0.0-dev",
            "Package": {
                "providesPackages": []
            }
        }
    }
}
```

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

Instead, the composer plugin `sbuerk/fixture-packages` (1.1.3, a `require-dev`
of the root) discovers them by glob. The root
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
  `TESTS\TestBitejobsStub\Http\StubHttpHandler` resolvable at all. Verified in
  the generated `.Build/vendor/composer/autoload_psr4.php`, which maps
  `TESTS\TestMessyProfileFactory\`, `TESTS\TestBitejobsStub\`,
  `TESTS\CategoryTypesGroup\` and `TESTS\BaseTestDependencyInjection\` to their
  `Classes/` folders.
- The second merges the `autoload-dev` of every package, which is how each
  extension's own `FGTCLB\<Name>\Tests\` namespace reaches the root autoloader.

The plugin also writes `.Build/vendor/sbuerk/fixture-packages.php`, a plain PHP
array of every discovered fixture package with its name, type, path and
`extra.typo3/cms`. That file is what turns the packages into something the
testing framework can load — see the next section.

Both effects are produced at install time. **A new fixture extension is
invisible until the next `composerUpdate`**:

```bash
Build/Scripts/runTests.sh -t 13 -p 8.3 -s composerUpdate
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
    $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
    $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
    $this->addTestExtensionsToLoad('georgringer/numbered-pagination', 'tests/plugin-templates');
    parent::setUp();
}
```

```php
'setup' => [
    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
    'EXT:academic_persons/Configuration/TypoScript/Default/setup.typoscript',
    'EXT:test_plugin_templates/Configuration/TypoScript/setup.typoscript',
    // …
],
```

— [`AcademicPersonsListPluginTest.php:35-36`](../../packages/fgtclb/academic-persons/Tests/Functional/Plugins/AcademicPersonsListPluginTest.php#L35-L36)
and [`:57-62`](../../packages/fgtclb/academic-persons/Tests/Functional/Plugins/AcademicPersonsListPluginTest.php#L57-L62)

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

**The package name is not derivable from the extension key.** All thirteen use
the `tests/` vendor, but the second segment follows no rule: `test_plugin_templates`
is `tests/plugin-templates` (prefix dropped), `test_bitejobs_stub` is
`tests/test-bitejobs-stub` (prefix kept), and `test_base_dependency_injection`
is `tests/base-test-dependency-injection` (words reordered). Read the package
name out of the fixture's `composer.json` rather than guessing it. New fixtures
should prefer the mechanical form — the key with underscores turned into
hyphens — `test_legacy_year_columns` is `tests/test-legacy-year-columns`,
`test_public_profile_settings` is `tests/test-public-profile-settings` — but
the older ones are not going to be renamed for cosmetics.

## What a fixture extension is for, and what it is not

The thirteen existing ones show the cases that justify one:

- **Bootstrap-time configuration.** `test_bitejobs_stub` replaces
  `$GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler']` in `ext_localconf.php` so no
  functional test ever reaches the b-ite API. There is no other point at which
  that assignment happens early enough.
- **Schema and TCA.** `test_jobcontact_schema` ships `ext_tables.sql` and TCA for
  a table the upgrade wizard tests migrate away from. The table has to exist when
  the instance is built.
- **Columns the extension no longer declares.** `test_legacy_year_columns` is an
  `ext_tables.sql` of eleven lines: a second `CREATE TABLE` for
  `tx_academicpersons_domain_model_profile_information` naming only the integer
  `year`, `year_start` and `year_end` columns that 3.0.0 replaced with `DATE`
  columns. TYPO3 merges every `CREATE TABLE` for the same table, so the test
  instance gets the schema of an *updated* installation — new and old columns
  side by side, which is what the database analyzer leaves behind, since it
  never drops a removed column on its own. That is the only way to exercise an
  upgrade wizard that reads columns the extension itself has stopped declaring:
  a CSV fixture cannot import into a column that does not exist, and
  `assertCSVDataSet()` could not compare it afterwards. The wizard test loads
  the fixture; a sibling test without it pins that the wizard also copes with a
  schema where the columns are gone.
- **Dependency injection.** `test_base_dependency_injection` and
  `test_messy_profile_factory` ship `Services.yaml` plus classes, so the
  container really wires them.
- **Resources resolved through `EXT:` paths.** `test_plugin_templates` and
  `test_language_files` exist because TypoScript and `LLL:` references need a
  real extension path.
- **A TCA shape the extension no longer ships.** `test_exclude_file_column`
  adds a `file` column with `l10n_mode=exclude` to the profile table, so the
  ACE-487 pin of the translation synchronisation — a late file reference on an
  exclude column reaches the existing translation — survived the profile image
  becoming translatable (ACE-506). The column exists for every test of the one
  class that loads the fixture, which is why that pin has a class of its own.
- **Registered configuration.** `test_category_types_group` ships a
  `Configuration/CategoryTypes.yaml` so the registry is filled the way an
  installing extension fills it, `test_current_color_icons` registers icons
  with the `currentColor` icon provider the same way,
  `test_public_profile_settings` ships a `Configuration/AcademicPersons/Settings.yaml`
  that overrides the `profile` map exactly as a site package would,
  `test_contract_contact_actions` ships one that narrows the `actions` of the
  contracts section, and `test_legacy_settings` ships one in the pre-3.0
  shape — the settings are collected from every loaded package, so nothing
  smaller than a package can take part in that merge.

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
   `extra.typo3/cms.extension-key`, `version`, `Package.providesPackages: []`,
   and a `typo3/cms-core` constraint matching `monorepo-shared`.
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
