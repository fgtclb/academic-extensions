# Fixture extensions

Some things cannot be set up from inside a test method: a TCA table that has to
exist before the instance is built, a service that has to be wired by dependency
injection, a template override that TypoScript must be able to find, an
`ext_localconf.php` that has to run during bootstrap. For those, the test ships
a small TYPO3 extension of its own.

Seventy-seven such fixture extensions exist, in eleven of the twelve extensions.
That is the whole population — this is a mechanism used sparingly and only
where nothing smaller works. Measured with

```bash
find packages/fgtclb/*/Tests/Functional/Fixtures/Extensions -mindepth 1 -maxdepth 1 -type d | wc -l
find packages/fgtclb/*/Tests/Functional/Fixtures/Extensions -mindepth 1 -maxdepth 1 -type d \
  | cut -d/ -f3 | sort -u | wc -l
```

## Where they live and what they are

They sit next to the tests that use them, under
`packages/fgtclb/<extension>/Tests/Functional/Fixtures/Extensions/<extension_key>/`:

| Extension key                               | Composer package name                             | Owned by                 | Provides                                                                      |
|---------------------------------------------|---------------------------------------------------|--------------------------|-------------------------------------------------------------------------------|
| `academic_test_configuration`               | `tests/academic-test-configuration`               | `academic-base`          | An academic extension with the stale configuration the upgrade check reports. |
| `test_base_dependency_injection`            | `tests/base-test-dependency-injection`            | `academic-base`          | Two services to resolve through the container, plus `Services.yaml`.          |
| `test_bitejobs_listener`                    | `tests/test-bitejobs-listener`                    | `academic-bite-jobs`     | Listeners of the B-ITE request and result events, and a recorder.             |
| `test_bitejobs_stub`                        | `tests/test-bitejobs-stub`                        | `academic-bite-jobs`     | An `ext_localconf.php` replacing the Guzzle handler stack.                    |
| `test_category_types_frontend_icons`        | `tests/category-types-frontend-icons`             | `typo3-category-types`   | Types and groups per frontend icon case, and a `FrontendIcons.php` entry.     |
| `test_category_types_group`                 | `tests/category-types-group`                      | `typo3-category-types`   | A `CategoryTypes.yaml` registering a group, plus a test ViewHelper.           |
| `test_category_types_icons`                 | `tests/category-types-icons`                      | `typo3-category-types`   | Four category types, one per branch of the icon registrar, and three groups.  |
| `test_category_types_summary_override`      | `tests/category-types-summary-override`           | `typo3-category-types`   | A page TSconfig override of the page module category summary template.        |
| `test_category_types_titles`                | `tests/category-types-titles`                     | `typo3-category-types`   | Two types of an undeclared group, one titled literally, one translated.       |
| `test_category_types_undeclared_group`      | `tests/category-types-undeclared-group`           | `typo3-category-types`   | A type in a group no package declares, then one in a group the file declares. |
| `test_contract_contact_actions`             | `tests/test-contract-contact-actions`             | `academic-persons-edit`  | A `Settings.yaml` narrowing the actions of the contracts section.             |
| `test_contract_publish_column`              | `tests/test-contract-publish-column`              | `academic-persons`       | The contract column `publish` of 2.x, as before the database compare.         |
| `test_contract_publish_renamed`             | `tests/test-contract-publish-renamed`             | `academic-persons`       | The contract column `publish` as the database compare renames it.             |
| `test_contract_publish_wizard`              | `tests/test-contract-publish-wizard`              | `academic-persons`       | The `publish` upgrade wizard registered in a site package's `Services.yaml`.  |
| `test_current_color_icons`                  | `tests/current-color-icons`                       | `academic-base`          | Icons registered through the `currentColor` icon provider.                    |
| `test_editor_icon_replacement`              | `tests/editor-icon-replacement`                   | `academic-persons-edit`  | A shared editor icon replaced in `FrontendIcons.php`, another in `Icons.php`. |
| `test_editor_write_listener`                | `tests/test-editor-write-listener`                | `academic-persons-edit`  | A listener of the editor write event, refusing or replacing as a test says.   |
| `test_exclude_file_column`                  | `tests/test-exclude-file-column`                  | `academic-persons`       | A TCA override adding an `l10n_mode=exclude` file column to profiles.         |
| `test_frontend_icon_api`                    | `tests/frontend-icon-api`                         | `academic-base`          | A shared icon replaced, and one frontend icon per case the icon API decides.  |
| `test_frontend_icons`                       | `tests/frontend-icons`                            | `academic-base`          | Frontend icons from a file and a listener, one also in `Icons.php`.           |
| `test_frontend_icons_override`              | `tests/frontend-icons-override`                   | `academic-base`          | A `FrontendIcons.php` replacing an icon of the above and the placeholder.     |
| `test_frontend_readonly`                    | `tests/test-frontend-readonly`                    | `academic-persons`       | Frontend-only field locks, also loaded by the `academic-persons-edit` tests.  |
| `test_frontend_user_sync`                   | `tests/test-frontend-user-sync`                   | `academic-persons`       | A `Settings.yaml` synchronisation map and the `fe_users` columns it reads.    |
| `test_frontend_user_sync_events`            | `tests/test-frontend-user-sync-events`            | `academic-persons`       | Listeners of both synchronisation events, and a factory creating no profile.  |
| `test_frontend_user_sync_relations`         | `tests/test-frontend-user-sync-relations`         | `academic-persons`       | A synchronisation map assigning the organisational unit and function type.    |
| `test_frontend_user_sync_relations_by_name` | `tests/test-frontend-user-sync-relations-by-name` | `academic-persons`       | A synchronisation map matching units by name and creating function types.     |
| `test_hidden_content_types`                 | `tests/hidden-content-types`                      | `academic-base`          | Two content types hidden by page TSconfig, one in the academic group.         |
| `test_icon_rules`                           | `tests/icon-rules`                                | `academic-base`          | Icons following every icon rule, in both registries and category types.       |
| `test_job_icons`                            | `tests/job-icons`                                 | `academic-jobs`          | Job icons replaced in `FrontendIcons.php`, and without effect in `Icons.php`. |
| `test_job_validation_override`              | `tests/job-validation-override`                   | `academic-jobs`          | A jobs `Settings.yaml` naming nine fields, a TCA override, two TCA listeners. |
| `test_jobcontact_schema`                    | `tests/test-jobcontact-schema`                    | `academic-jobs`          | `ext_tables.sql` and TCA for a legacy table an upgrade wizard migrates.       |
| `test_language_files`                       | `tests/language-files`                            | `academic-persons`       | An XLF pair with awkward label keys (dots, dashes).                           |
| `test_legacy_settings`                      | `tests/test-legacy-settings`                      | `academic-persons`       | A `Settings.yaml` in the pre-3.0 shape, the 2.x manual's override.            |
| `test_literal_helptext`                     | `tests/test-literal-helptext`                     | `academic-persons-edit`  | A `Settings.yaml` with literal help texts for a contract and a contact field. |
| `test_managed_fields`                       | `tests/test-managed-fields`                       | `academic-persons`       | A `Settings.yaml` naming managed fields of four record types.                 |
| `test_managed_fields_editor`                | `tests/test-managed-fields-editor`                | `academic-persons-edit`  | Managed fields that leave one contact row editable and lock another one.      |
| `test_managed_fields_mistake`               | `tests/test-managed-fields-mistake`               | `academic-persons`       | A misspelled managed field, also loaded by the `academic-persons-edit` tests. |
| `test_messy_profile_factory`                | `tests/test-messy-profile-factory`                | `academic-persons`       | A deliberately misbehaving profile factory and two event listeners.           |
| `test_page_contacts_listener`               | `tests/test-page-contacts-listener`               | `academic-contact4pages` | A listener of the page contacts event, removing the contact a test names.     |
| `test_partner_list_events`                  | `tests/test-partner-list-events`                  | `academic-partners`      | Two listeners on the partner demand and list events, and a list template.     |
| `test_partners_frontend_icons`              | `tests/partners-frontend-icons`                   | `academic-partners`      | A region icon replaced in `FrontendIcons.php`, a type with a `frontendIcon`.  |
| `test_partners_stub`                        | `tests/test-partners-stub`                        | `academic-partners`      | An `ext_localconf.php` replacing the Guzzle handler stack.                    |
| `test_partners_titled_category_type`        | `tests/partners-titled-category-type`             | `academic-partners`      | A partner type with a translated title, and the shipped region retitled.      |
| `test_plugin_action_context`                | `tests/test-plugin-action-context`                | `academic-persons`       | A listener recording the content element of an event's plugin context.        |
| `test_plugin_templates`                     | `tests/plugin-templates`                          | `academic-persons`       | Simplified Fluid templates and the TypoScript pointing at them.               |
| `test_plugin_view_event`                    | `tests/test-plugin-view-event`                    | `academic-base`          | Listeners recording the view event and every context, a leftover, probes.     |
| `test_position_fields`                      | `tests/test-position-fields`                      | `academic-persons`       | A `Settings.yaml` listing every field of the position line.                   |
| `test_profile_icon_replacement`             | `tests/profile-icon-replacement`                  | `academic-persons`       | A shared profile icon replaced in `FrontendIcons.php`, one in `Icons.php`.    |
| `test_profile_partial_overrides`            | `tests/test-profile-partial-overrides`            | `academic-persons`       | Partial overrides in two paths, a card passing a page, an old list template.  |
| `test_profile_placeholders`                 | `tests/test-profile-placeholders`                 | `academic-persons`       | Profile image placeholders of a site package, one per gender.                 |
| `test_profile_query_constraints`            | `tests/test-profile-query-constraints`            | `academic-persons`       | Listeners narrowing and counting the queries, and one replacing the demand.   |
| `test_profile_update_recorder`              | `tests/test-profile-update-recorder`              | `academic-persons`       | A listener recording every profile update announcement, frontend included.    |
| `test_profile_view_modes`                   | `tests/test-profile-view-modes`                   | `academic-persons`       | A project view mode of the profile lists, with its TypoScript.                |
| `test_program_events`                       | `tests/test-program-events`                       | `academic-programs`      | Listeners on the program demand, list and page data events, a list template.  |
| `test_programs_category_type_priority`      | `tests/programs-category-type-priority`           | `academic-programs`      | A `CategoryTypes.yaml` raising the priority of a type of the programs group.  |
| `test_programs_extra_category_type`         | `tests/programs-extra-category-type`              | `academic-programs`      | A `CategoryTypes.yaml` adding one type to the programs group.                 |
| `test_programs_frontend_icons`              | `tests/programs-frontend-icons`                   | `academic-programs`      | Fact icons replaced in `FrontendIcons.php` and `Icons.php`, a `frontendIcon`. |
| `test_programs_removed_category_type`       | `tests/programs-removed-category-type`            | `academic-programs`      | A `CategoryTypes.yaml` removing a type from the programs group.               |
| `test_programs_titled_category_type`        | `tests/programs-titled-category-type`             | `academic-programs`      | A program type with a translated title, and the shipped degree retitled.      |
| `test_project_list_events`                  | `tests/test-project-list-events`                  | `academic-projects`      | Two listeners on the project demand and list events, and a list template.     |
| `test_project_profile_column_removed`       | `tests/project-column-removed`                    | `academic-persons-edit`  | A listener after the persons settings removing a project column.              |
| `test_project_profile_fields`               | `tests/test-project-profile-fields`               | `academic-persons-edit`  | Project columns of every type a project field takes, one managed, a listener. |
| `test_projects_frontend_icons`              | `tests/projects-frontend-icons`                   | `academic-projects`      | Competence field icon in `FrontendIcons.php`, a type with a `frontendIcon`.   |
| `test_projects_titled_category_type`        | `tests/projects-titled-category-type`             | `academic-projects`      | A project type with a translated title, a shipped type retitled.              |
| `test_public_profile_settings`              | `tests/test-public-profile-settings`              | `academic-persons`       | A `Settings.yaml` overriding the public profile layout.                       |
| `test_settings_copy`                        | `tests/test-settings-copy`                        | `academic-persons`       | A copy of the contract fields that leaves the room out, removes one with `~`. |
| `test_settings_removal`                     | `tests/test-settings-removal`                     | `academic-persons`       | A delta removing one profile field with `~` and copying nothing.              |
| `test_study_plan_icons`                     | `tests/study-plan-icons`                          | `academic-study-plan`    | A shared glyph replaced in `FrontendIcons.php`, another in `Icons.php`.       |
| `test_tca_override_after_settings`          | `tests/tca-override-after-settings`               | `academic-persons`       | TCA overrides and listeners changing columns and a type the settings set.     |
| `test_upgrade_check`                        | `tests/test-upgrade-check`                        | `academic-base`          | The extension whose templates the upgrade check compares an override with.    |
| `test_upgrade_check_project`                | `tests/test-upgrade-check-project`                | `academic-base`          | A project site package overriding templates of the fixture above.             |
| `test_upgrade_check_shared`                 | `tests/test-upgrade-check-shared`                 | `academic-base`          | A shared partial package the checked fixture extension requires.              |
| `test_visibility_switch_disabled`           | `tests/test-visibility-switch-disabled`           | `academic-persons-edit`  | A `Settings.yaml` disabling the profile visibility switch.                    |
| `test_visibility_switch_readonly`           | `tests/test-visibility-switch-readonly`           | `academic-persons-edit`  | A `Settings.yaml` making the profile visibility switch read-only.             |
| `test_visibility_switch_removed`            | `tests/test-visibility-switch-removed`            | `academic-persons-edit`  | A `Settings.yaml` removing the profile visibility switch with `~`.            |
| `test_wizard_content_elements`              | `tests/wizard-content-elements`                   | `academic-base`          | Three academic content types and two in groups of TYPO3, for the wizard.      |

Each is a real, complete TYPO3 extension: a `composer.json` of type
`typo3-cms-extension`, an `ext_emconf.php`, and whatever it exists to provide.
Twenty-two of the seventy-seven have a `Classes/` folder with a `TESTS\…` PSR-4
root. The other fifty-five are pure resources. The `ext_emconf.php` is checked
like every other one: its `depends` names extension keys, and a fixture
extension may name another fixture extension, which a real extension may not —
see [Unit tests](unit-tests.md#the-ext_emconfphp-dependency-keys).

A minimal one, complete:

```json
{
    "name": "tests/plugin-templates",
    "description": "Plugin template overrides for tests",
    "type": "typo3-cms-extension",
    "license": "GPL-2.0-or-later",
    "require": {
        "typo3/cms-core": "~13.4.35 || ~14.3.7",
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
([`Build/phpunit/FunctionalTestsBootstrap.php:29-54`](../../Build/phpunit/FunctionalTestsBootstrap.php#L29-L54))
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

### The loading order in a test instance

A fixture that replaces something of an extension, an icon or a settings key,
has to load after it, and in a test instance that order is not always the one
a site gets. The testing framework sorts the packages of a classic mode
instance from their dependencies, and the dependencies come from the
`depends` of `ext_emconf.php` on TYPO3 v13, which names every academic
extension, and from the `require` of `composer.json` on TYPO3 v14, because
every package here declares `version` and `providesPackages`. TYPO3 v14 then
drops every requirement on a package the root composer install knows
(`PackageManager::isComposerDependency()` checks the names of
`InstalledVersions`, `Package::ignoreDependencyInPackageConstraint()` drops
them), which is every `fgtclb/*` package and none of the `tests/*` ones. A
v14 test instance therefore orders the academic extensions and the fixtures
by nothing but their keys, plus whatever edge a requirement on another
fixture adds, and such an edge moved `academic_base` behind the two
`test_frontend_icons*` fixtures until it was dropped.

A site is not affected, composer orders its packages. A fixture that has to
load after an extension therefore gets a key that sorts after it, requires no
other fixture, and its test asserts the order before it asserts the
replacement, as `IconViewHelperOverrideTest::theSitePackageLoadsAfterTheExtensionAndAcademicBase()`
does. `test_job_icons` sorts after `academic_jobs` the same way, and so do
`test_programs_frontend_icons` after `academic_programs` and
`test_frontend_icon_api` after `academic_base`. The icons
`test_profile_icon_replacement`, `test_editor_icon_replacement` and
`test_study_plan_icons` replace are shared icons of `academic_base`, so each
sorts after `academic_base` and after the extension that renders them,
`academic_persons`, `academic_persons_edit` and `academic_study_plan`.
`test_category_types_frontend_icons` needs no order: the category type icons
reach the frontend registry through its collect event, which is applied before
any `Configuration/FrontendIcons.php`, so its own file replaces one of them in
any position. `test_partners_frontend_icons` and `test_projects_frontend_icons`
replace category type icons only and need no order for the same reason.

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

**The package name is not derivable from the extension key.** All of them use
the `tests/` vendor, but the second segment follows no rule: `test_plugin_templates`
is `tests/plugin-templates` (prefix dropped), `test_bitejobs_stub` is
`tests/test-bitejobs-stub` (prefix kept), and `test_base_dependency_injection`
is `tests/base-test-dependency-injection` (words reordered). Read the package
name out of the fixture's `composer.json` rather than guessing it. New fixtures
should prefer the mechanical form — the key with underscores turned into
hyphens — `test_public_profile_settings` is
`tests/test-public-profile-settings` — but the older ones are not going to be
renamed for cosmetics.

## What a fixture extension is for, and what it is not

The existing ones show the cases that justify one:

- **Bootstrap-time configuration.** `test_bitejobs_stub` replaces
  `$GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler']` in `ext_localconf.php` so no
  functional test ever reaches the b-ite API. There is no other point at which
  that assignment happens early enough.
- **Schema and TCA.** `test_jobcontact_schema` ships `ext_tables.sql` and TCA for
  a table the upgrade wizard tests migrate away from. The table has to exist when
  the instance is built.
- **Dependency injection.** `test_base_dependency_injection` and
  `test_messy_profile_factory` ship `Services.yaml` plus classes, so the
  container really wires them. `test_profile_query_constraints` is the same
  case for an event: its listeners register through TYPO3's
  `#[AsEventListener]`, which only means anything once the container has seen
  the class, so the documented way to write a listener is the way the test
  registers one. `test_partner_list_events`, `test_program_events` and
  `test_project_list_events` are the same case for the list plugin events, one
  fixture per extension: the partner tests never load `academic_projects` and
  the project tests never load `academic_partners`, so one fixture listening to
  the events of all three would drag an extension into every run that has no
  business being there. `test_page_contacts_listener` does the same for the
  page contacts event of `academic_contacts4pages`, and
  `test_bitejobs_listener` for the request and result events of
  `academic_bite_jobs`. Its recording listener keeps those events and the
  plugin view event of the job list in a static property, so a test reads what
  a listener was handed during a frontend request too.
  `test_frontend_user_sync_events` needs a package for two reasons: its
  listeners of the synchronisation events register through
  the container, and its `frontendUserSync` map, which reads a value one of
  them adds, is merged into the settings only from a loaded package.
- **Resources resolved through `EXT:` paths.** `test_plugin_templates` and
  `test_language_files` exist because TypoScript and `LLL:` references need a
  real extension path. `test_profile_partial_overrides` is the same case for a
  partial override: proving that overriding one small partial reaches every
  plugin takes a real partial root path, and the fixture ships the TypoScript
  that registers it for the persons plugins **and** for
  `academic_contacts4pages`. Like `test_profile_query_constraints` it stays
  inert until a test includes that TypoScript, so the class can load it once and
  still render the shipped templates in every other test. It ships **two**
  partial directories, because a test of the grid override has to run without
  the name override standing in its way — a second directory is cheaper than a
  second fixture extension. For the same reason it carries a list template of a
  project written before the letter navigation knew which letters lead
  somewhere, in a template directory of its own.
- **A TCA shape the extension no longer ships.** `test_exclude_file_column`
  adds a `file` column with `l10n_mode=exclude` to the profile table, so the
  ACE-487 pin of the translation synchronisation — a late file reference on an
  exclude column reaches the existing translation — survived the profile image
  becoming translatable (ACE-506). The column exists for every test of the one
  class that loads the fixture, which is why that pin has a class of its own.
- **Registered configuration.** `test_category_types_group` ships a
  `Configuration/CategoryTypes.yaml` so the registry is filled the way an
  installing extension fills it, `test_current_color_icons` registers icons
  with the `currentColor` icon provider the same way, `test_frontend_icons`
  registers icons for the frontend icon registry of `academic_base` in a
  `Configuration/FrontendIcons.php` and from a listener, with one icon in its
  `Configuration/Icons.php` as well and one backend-only icon there, and
  `test_frontend_icons_override` replaces two of them, which takes a package
  that loads later, `test_frontend_icon_api` replaces a shared icon and
  registers one frontend icon per case the frontend icon API serves or
  refuses, `test_category_types_frontend_icons` declares
  category types and groups for every frontend icon case and replaces one of
  their icons in its own `Configuration/FrontendIcons.php`,
  `test_profile_icon_replacement`, `test_editor_icon_replacement`,
  `test_job_icons`, `test_study_plan_icons` and `test_programs_frontend_icons`
  replace icons of the public profile, the profile editor, the job views, the
  study plan and the program facts in their `Configuration/FrontendIcons.php`,
  which only a package that loads after the package registering them can, and
  others in their
  `Configuration/Icons.php`, which the frontend does not read,
  `test_partners_frontend_icons` and `test_projects_frontend_icons` replace a
  shipped category type icon for the frontend and declare a type with a
  `frontendIcon` of its own, `test_public_profile_settings` ships a
  `Configuration/AcademicPersons/Settings.yaml` that overrides the `profile`
  map exactly as a site package would, `test_contract_contact_actions` ships
  one that narrows the `actions` of the contracts section,
  `test_legacy_settings` ships one in the pre-3.0 shape, and
  `test_frontend_user_sync` and the two `test_frontend_user_sync_relations*`
  one each with a `frontendUserSync` map plus the `ext_tables.sql` for the
  `fe_users` columns that map reads. The settings are collected from every
  loaded package, so nothing smaller than a package can take part in that
  merge.

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
   and a `typo3/cms-core` constraint matching `monorepo-shared`. The `version`
   and `providesPackages` are not optional: from TYPO3 v14 on,
   `PackageManager::isComposerOnlyCapable()` merges `ext_emconf.php` into the
   manifest unless both are declared, and its constraints then override the
   composer `require`. The upgrade check tests of `academic_base` failed on v14
   alone that way.
3. Write `ext_emconf.php`. The `version` and the `constraints.depends` ranges
   of `typo3` and `core` are read from it by TYPO3, so keep them consistent
   with the composer file. It carries no comment, `bin/set-version` rewrites the file through
   packwright and drops every comment, and a unit test fails on one, see
   [Unit tests](unit-tests.md#the-form-binset-version-writes). Write it in
   packwright's form with `pkw extemconf:normalize <file>`.
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
