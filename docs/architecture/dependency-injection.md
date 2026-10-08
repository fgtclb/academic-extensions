# Dependency injection

Every extension in this repository wires its services through the TYPO3
dependency injection container, which is Symfony's container with TYPO3
compiler passes on top. This page records the preference, what the codebase
actually does today, and the two rules that are not negotiable.

## Where the codebase stands

`AGENTS.md` states a preference for `Configuration/Services.php` (PHP form)
over `Services.yaml`, and for Symfony attributes over service definitions. The
measured reality is different in one direction and better than expected in the
other, so the preference is best read as a direction of travel rather than a
description.

| Package                                  | `Services.php` | `Services.yaml` |
|------------------------------------------|----------------|-----------------|
| `packages/fgtclb/academic-base`          | –              | yes             |
| `packages/fgtclb/academic-bite-jobs`     | –              | yes             |
| `packages/fgtclb/academic-contact4pages` | –              | yes             |
| `packages/fgtclb/academic-jobs`          | –              | yes             |
| `packages/fgtclb/academic-partners`      | –              | yes             |
| `packages/fgtclb/academic-persons`       | **yes**        | yes             |
| `packages/fgtclb/academic-persons-edit`  | –              | yes             |
| `packages/fgtclb/academic-persons-sync`  | –              | –               |
| `packages/fgtclb/academic-programs`      | –              | yes             |
| `packages/fgtclb/academic-projects`      | –              | yes             |
| `packages/fgtclb/academic-study-plan`    | –              | yes             |
| `packages/fgtclb/typo3-category-types`   | –              | yes             |
| `packages-dev/monorepo-shared`           | –              | –               |
| `packages-dev/testing-helper`            | –              | –               |
| `packages-dev/dev-site`                  | –              | –               |

So: **11 `Services.yaml`, 1 `Services.php`**, and `academic-persons` is the only
package carrying both. Four packages have neither — `academic-persons-sync`
ships only domain models under `Classes/Domain/`, and none of the three
`packages-dev/` packages has a `Classes/` folder requiring registration at all.
`packages-dev/dev-site/` ships no PHP at all.

### What the YAML files contain

All 11 share the same header and differ only after it:

```yaml
services:
  _defaults:
    autowire: true
    autoconfigure: true
    public: false

  FGTCLB\AcademicJobs\:
    resource: '../Classes/*'
    exclude: '../Classes/Domain/Model/*'
```

`autoconfigure: true` on the defaults is what makes PHP attributes work at all,
which is why the two styles coexist without friction. Beyond the header, seven
files carry real definitions: `event.listener` tags, `data.processor` tags, an
`extbase.type_converter` tag, three `console.command` tags, factory-produced
services, one interface alias, and several `public: true` markers on
repositories, registries and one data processor.

Two spellings are worth knowing because they are inconsistent across the
packages and the difference is not cosmetic:

- `resource: '../Classes/*'` versus `resource: '../Classes'`
- `exclude: '../Classes/Domain/Model/*'` versus `'../Classes/Domain/Model'`
  versus no `exclude` at all
  (`academic-bite-jobs` and `academic-study-plan` exclude nothing)

The `exclude` is what keeps Extbase domain models out of the container. A model
that is registered but never type hinted is dropped again when the container is
compiled, so omitting it breaks nothing and warns about nothing — until someone
type hints the model, and the container then fails to build with an error
pointing at the model rather than at the code that referenced it.

### The one `Services.php`

[`packages/fgtclb/academic-persons/Configuration/Services.php`](../../packages/fgtclb/academic-persons/Configuration/Services.php)
is not the boilerplate `defaults()` + `load()` file the preference implies. It
exists for the things YAML cannot express: registering autoconfiguration for an
interface, and — since ACE-504 — a compiler pass that registers
`Report\LegacySettingsStatus` only when EXT:reports is active. The
autoconfiguration part:

```php
return static function (ContainerConfigurator $container, ContainerBuilder $containerBuilder): void {
    $containerBuilder->registerForAutoconfiguration(TypesInterface::class)->setPublic(true);
    $containerBuilder->registerForAutoconfiguration(DemandValuesInterface::class)->setPublic(true);
    $containerBuilder->registerForAutoconfiguration(ProfileFactoryInterface::class)
        ->setPublic(true)
        ->setShared(true);
};
```

The registration of the classes themselves still happens in that package's
`Services.yaml`. TYPO3 loads both files when both are present.

The compiler pass is there because of a timing fact worth knowing: a
`Services.php` runs while the container is being built, and at that point
neither `ExtensionManagementUtility::isLoaded()` nor the `PackageManager`
service is available — `Bootstrap` hands the package manager to
`ExtensionManagementUtility` only after `createDependencyInjectionContainer()`
has returned, and the core's `ContainerBuilder` registers its synthetic
services after every package's `Services.*` was loaded. A compiler pass runs
after all of that, so `hasDefinition(StatusRegistry::class)` — a definition only
EXT:reports' own `Services.yaml` makes — is an order-independent "is reports
active" check on both core versions. The pass is added with priority 500 in the
before-optimization stage: Symfony's `ResolveInstanceofConditionalsPass` runs at
priority 100 of that same stage, and a definition registered after it stays
untagged even with `setAutoconfigured(true)`. The class is excluded from the
`resource` load of `Services.yaml` for the same reason it needs the pass —
the interface it implements does not exist without EXT:reports.

### Attributes are already in use

Contrary to the note in `AGENTS.md` that these extensions do not use attributes,
they are used in production code across ten of the twelve packages
(`academic-base`, `academic-contact4pages`, `academic-jobs`,
`academic-partners`, `academic-persons`, `academic-persons-edit`,
`academic-programs`, `academic-projects`, `academic-study-plan` and
`typo3-category-types`):

Measured with
`grep -rhoP '#\[<name>[(\]]' --include='*.php' packages/fgtclb/*/Classes packages-dev/*/Classes | wc -l`:

| Attribute            | Sites | Examples                                                                     |
|----------------------|-------|------------------------------------------------------------------------------|
| `#[Autoconfigure]`   | 19    | `academic-base/Classes/Service/ArrayObjectMapper.php:24` (`public: true`)    |
| `#[Autowire]`        | 7     | same file, line 28 — `#[Autowire(service: 'academic-base.serializer')]`      |
| `#[AsAlias]`         | 3     | `academic-persons/Classes/Service/RecordSynchronizer.php:49`                 |
| `#[Exclude]`         | 27    | `academic-base/Classes/Settings/Validation.php:23` and the settings graph    |
| `#[AsEventListener]` | 13    | `academic-partners/Classes/EventListener/RegisterAcademicPageDoktype.php:33` |
| `#[AsCommand]`       | 3     | `academic-partners/Classes/Command/GeocodeCommand.php:23`                    |

`#[AsCommand]` there is Symfony's **Console** attribute
(`Symfony\Component\Console\Attribute\AsCommand`), not a DI one, on the
geocoding command, on `academic-base/Classes/Command/UpgradeCheckCommand.php`
and on `academic-projects/Classes/Command/MigrateProjectDepartmentsCommand.php`;
the other three commands in `academic-persons` are still registered with
`console.command` tags in YAML. The `#[AsEventListener]` sites are TYPO3's
attribute (see below): the `RegisterAcademicPageDoktype` and the
`AddPageModuleCategorySummary` listener of each of `academic-partners`,
`academic-programs` and `academic-projects`, `ApplySettingsToTca` of
`academic-persons` and of `academic-jobs`, which apply the settings of their
extension to the compiled TCA, `WarmUpFrontendIconRegistry` of
`academic-base`, which builds the frontend icon registry when the system caches
are warmed up, `AddCategoryTypeFrontendIcons` of `typo3-category-types`,
which contributes the category type icons to that registry, and
`FlushProfileViewCaches` of `academic-persons`, which carries the attribute on
each of its three methods, one per persistence event of Extbase.
`#[AsTaggedItem]` and `#[AsController]` have zero sites.

For the twenty-two `#[Exclude]` sites and why `LegacySettingsMigration` is among
them, see [Class design](class-design.md#keep-data-objects-out-of-the-container).

The `#[Autowire]` example is the clearest illustration of the two styles working
together: `academic-base/Configuration/Services.yaml` lines 13–15 define a
factory-produced service under the string id `academic-base.serializer`, and the
consuming class pins it to one constructor argument by attribute. A string id
cannot be autowired by type, so it has to be named somewhere — naming it on the
argument keeps that fact next to the code that depends on it.

### Which style to use

Match the surrounding extension. Adding a `Services.php` to a package that has a
working `Services.yaml` is churn, and the two files are loaded together, so a
split registration is harder to follow than either style alone. What is worth
doing on **new** code, in any package:

- Put service *metadata* on the class with attributes — `#[Autoconfigure]`,
  `#[AsAlias]`, `#[Autowire]`, `#[Exclude]` — rather than in a definition block.
  It cannot drift away from the class it describes, and it survives a rename.
- Keep in the configuration file only what cannot live on a class: the
  `_defaults`, the `resource`/`exclude` load, `registerForAutoconfiguration()`,
  and definitions for services whose class this repository does not own.

## Services are stateless

**New services must be stateless. Existing services must not gain new state.**

The reason is the container's lifecycle, not style. A service is shared by
default: the container builds it once and hands the same instance to every
consumer for the rest of the process. Anything a method stores on `$this`
therefore outlives the call that produced it and is visible to the next caller
— which in TYPO3 is routinely a different record, a different language overlay
or a different frontend user. Under a persistent process (a CLI worker, a long
running import command) the same instance can span an entire run.

The failure mode is a wrong value, not an exception, and it depends on call
order. It surfaces as "the second profile shows the first one's data" long
after the change that caused it.

Pass what a method needs as arguments and return what it produces. Inject
collaborators through the constructor and keep them `private readonly`.

### What compliant code looks like

[`packages/fgtclb/academic-persons/Classes/Service/RecordSynchronizer.php`](../../packages/fgtclb/academic-persons/Classes/Service/RecordSynchronizer.php)
lines 49–71: two attributes, two injected collaborators, no instance state.

```php
#[AsAlias(id: RecordSynchronizerInterface::class, public: true)]
#[Autoconfigure(public: true)]
class RecordSynchronizer implements RecordSynchronizerInterface
{
    public function __construct(
        private readonly DataHandlerExecutionContext $executionContext,
        private readonly LoggerInterface $logger,
    ) {}
```

The sixteen event listener classes follow the same shape, promoted
`private readonly` dependencies (or a `readonly class`) and a single
`__invoke()`, apart from `AssignContractOrganisationalUnitSorting` and
`FlushProfileViewCaches`, which have one method per persistence event. Five are
registered by YAML tag:
`academic-jobs/Classes/EventListener/GenerateJobSlug.php`,
`academic-persons/Classes/EventListener/UpdateProfileImageMetadata.php`,
`.../AssignContractOrganisationalUnitSorting.php`,
`academic-persons-edit/Classes/EventListener/GenerateSlugForProfile.php` and
`.../SyncChangesToTranslations.php`. Eleven are registered by attribute: the
`RegisterAcademicPageDoktype` and the `AddPageModuleCategorySummary` of
`academic-partners`, `academic-programs` and `academic-projects`,
`ApplySettingsToTca` of `academic-persons` and `academic-jobs`,
`WarmUpFrontendIconRegistry` of `academic-base`,
`AddCategoryTypeFrontendIcons` of `typo3-category-types` and
`FlushProfileViewCaches` of `academic-persons`.

### Where the codebase does not comply

Stating this plainly, because a rule presented as universally followed is a rule
nobody checks:

- [`academic-persons-edit/Classes/Service/ListSortingService.php`](../../packages/fgtclb/academic-persons-edit/Classes/Service/ListSortingService.php)
  line 26 keeps a nullable `PersistenceManagerInterface` filled by a
  `#[Required] injectPersistenceManager()` method, while its own docblock at
  line 18 reads `@note Service must be kept stateless.`
- `typo3-category-types/Classes/Loader/CategoryTypeLoader.php:19` memoizes its
  built registry in a nullable property. It is the `load()` factory of a
  shared, `public: true` registry, so the cached value is process-wide.
- `academic-persons/Classes/Profile/AbstractProfileFactory.php` lines 34 and 38
  hold genuine runtime state (`$autoCreateProfiles`,
  `$userGroupsToCreateProfilesFor`) filled from `initializeObject()`. Its
  subclass `ProfileFactory` is registered `#[Autoconfigure(public: true,
  shared: true)]` (line 27) — explicitly shared while stateful.

None of these are cleared by the rule. They are the reason for it. Do not use
them as precedent, and do not add state to them.

Note that a genuinely configurable service is possible, but it must be declared
`#[Autoconfigure(shared: false)]` so each retrieval returns a fresh instance.
No service in this repository is declared that way today.

## Attributes safe on both core versions

Not every attribute exists in every supported version, so an attribute has to be
checked against **both** before it is used. The following was verified by
listing the attribute directories of both installed trees — `.Build/vendor/`
carries whichever version the last `composerUpdate -t 13|14` installed, and
`core-13/vendor/` and `core-14/vendor/` carry the two development instances, so
the check is `composerUpdate` for one version, list, `composerUpdate` for the
other, list again. The versions listed below are 13.4.34 and 14.3.6.

**Symfony `Symfony\Component\DependencyInjection\Attribute\*` — identical on
both trees** (21 attributes each), so all of these are safe:

| Attribute                                                         | Use                                                    |
|-------------------------------------------------------------------|--------------------------------------------------------|
| `#[Autoconfigure]`                                                | Publish, mark non-shared, add tags                     |
| `#[AsAlias]`                                                      | Register the default implementation of an interface    |
| `#[Autowire]`                                                     | Pin a service, parameter or expression to one argument |
| `#[Exclude]`                                                      | Keep a class out of the container                      |
| `#[AsTaggedItem]`, `#[AutowireIterator]`, `#[AutowireLocator]`    | Tagged collections                                     |
| `#[AsDecorator]`, `#[Lazy]`, `#[Target]`, `#[When]`, `#[WhenNot]` | Less common, all present on both                       |

**TYPO3 attributes — verified per tree**, because these are where the two
versions diverge:

| Attribute                                                            | v13.4.34 | v14.3.6         | Safe on both                   |
|----------------------------------------------------------------------|----------|-----------------|--------------------------------|
| `TYPO3\CMS\Core\Attribute\AsEventListener`                           | yes      | yes             | **yes**                        |
| `TYPO3\CMS\Core\Attribute\AsAllowedCallable`                         | yes      | yes             | **yes**                        |
| `TYPO3\CMS\Core\Attribute\WebhookMessage`                            | yes      | yes             | **yes**                        |
| `TYPO3\CMS\Backend\Attribute\AsController`                           | yes      | yes             | **yes**                        |
| `TYPO3\CMS\Install\Attribute\UpgradeWizard`                          | yes      | deprecated shim | works, but do not add new uses |
| `TYPO3\CMS\Core\Attribute\UpgradeWizard`                             | **no**   | yes             | no                             |
| `TYPO3\CMS\Core\Attribute\AsModuleAccessGate`                        | **no**   | yes             | no                             |
| `TYPO3\CMS\Core\Attribute\AsNonSchedulableCommand`                   | **no**   | yes             | no                             |
| `TYPO3\CMS\Backend\Attribute\AsAvatarProvider`, `AsSidebarComponent` | **no**   | yes             | no                             |

`TYPO3\CMS\Extbase\Attribute\*` does not exist on v13 at all. The
`Install\Attribute\UpgradeWizard` row is the one all seventeen upgrade wizards use:
on v14 it survives as a deprecated subclass shim in
`cms-core/DeprecatedClasses/ext-install/`, so it still works, but its
replacement `Core\Attribute\UpgradeWizard` is absent on v13. Wizards were
added since anyway, each a deliberate exception to "do not add new uses" and
migrated together with the others under ACE-294. Two examples:
`academic-bite-jobs/Classes/Upgrades/ListViewFlexFormUpgradeWizard.php` repairs
content elements a 2.1 rename broke, and
`academic-persons/Classes/Upgrades/MigrateContractPublishToHiddenUpgradeWizard.php`
is shipped unregistered, see
[A service a site package registers](#a-service-a-site-package-registers). See
[Core version aware code](core-version-aware-code.md#apis-that-cannot-be-modernised-yet)
for both.

There is no `TYPO3\CMS\Core\Attribute\Autoconfigure` and no
`TYPO3\CMS\Core\Attribute\AsCommand` on either version; use the Symfony
attributes for those.

## Never use Symfony's `#[AsEventListener]`

Both classes exist and both are importable, so nothing stops the wrong one being
used:

| Class                                                         | Present on v13.4.34 and v14.3.6 | Emits tag               |
|---------------------------------------------------------------|---------------------------------|-------------------------|
| `TYPO3\CMS\Core\Attribute\AsEventListener`                    | yes                             | `event.listener`        |
| `Symfony\Component\EventDispatcher\Attribute\AsEventListener` | yes                             | `kernel.event_listener` |

**Always the TYPO3 one.** TYPO3's attribute declares
`public const TAG_NAME = 'event.listener'` (line 26 in both trees), and
`typo3/cms-core/Configuration/Services.php` lines 25–39 registers it for
autoconfiguration so the attribute is turned into that tag. TYPO3's
`ListenerProviderPass` then collects exactly that tag and feeds it to the
`ListenerProvider`.

Symfony's attribute produces `kernel.event_listener`, which is read by Symfony's
`RegisterListenersPass` — a compiler pass TYPO3 does not register. Nothing reads
the tag, so **the container builds cleanly and the listener is never called**.
There is no error, no warning and no failing test unless a test asserts the
effect of the listener. That silence is the entire reason for the rule.

The TYPO3 attribute takes `identifier`, `event`, `method`, `before` and `after`.
Always set `identifier` explicitly — it is what `before`/`after` ordering in
other extensions refers to, and an auto-derived one changes when the class is
renamed.

Both spellings are in use here: five listener classes are registered by YAML
tag (`academic-jobs/Configuration/Services.yaml`,
`academic-persons/Configuration/Services.yaml` and
`academic-persons-edit/Configuration/Services.yaml`, which carry one, two and
two), ten carry `#[AsEventListener]` on the class: the
`RegisterAcademicPageDoktype` and `AddPageModuleCategorySummary` listeners of
`academic-partners`, `academic-programs` and `academic-projects`, for example
`#[AsEventListener(identifier: '…/register-page-doktype')]`,
`ApplySettingsToTca` of `academic-persons` and `academic-jobs`,
`WarmUpFrontendIconRegistry` of `academic-base` and
`AddCategoryTypeFrontendIcons` of `typo3-category-types`, and
`FlushProfileViewCaches` of `academic-persons` carries it on each of its three
methods, the method name and the event then come from the method itself. The
two are equivalent — the attribute is only a shorter spelling of the same tag —
and new listeners should prefer the attribute. Note that `academic-persons`' own
user manual already documents the TYPO3 attribute as the way integrators register
a listener against its events, in
`packages/fgtclb/academic-persons/Documentation/Changelog/2.4/Feature-DispatchModifyTcaSelectFieldItemsEventInItemsProcFunc.rst`
lines 41–43.

## A data processor with a collaborator has to be published

TypoScript names a data processor by the identifier of its `data.processor` tag
or by class name:

```typoscript
page.10.dataProcessing.400 = academic-page-contacts
page.10.dataProcessing.400 = FGTCLB\AcademicContacts4pages\DataProcessing\ContactsProcessor
```

TYPO3 resolves that name in three steps (`ContentDataProcessor::process()` and
`getDataProcessor()`, identical on v13 and v14): the identifier through the
tagged service locator of `DataProcessorRegistry`, then, if the container knows
the name, **that service**, and otherwise `GeneralUtility::makeInstance()` on
the class. The shipped TypoScript uses the identifier, which needs no
publishing. Installations name the class in their own TypoScript, though, and a
processor whose service is private then takes the last path and is constructed
with no arguments. So a constructor dependency is only injected on every path
when the processor is published:

```php
#[Autoconfigure(public: true)]
class ContactsProcessor implements DataProcessorInterface
{
    public function __construct(
        private readonly PageContactsProvider $pageContactsProvider,
    ) {}
}
```

`academic_programs` publishes `ProgramDataProcessor` for the same reason, in
its `Configuration/Services.yaml`, although the extension itself names it by its
tag identifier `program-data`.

The tag of `ContactsProcessor` sits in `Configuration/Services.yaml` as well,
not in an `#[AutoconfigureTag]` attribute on the class. An attribute tag is
registered as an `_instanceof` rule, so a project's subclass would carry the
same identifier, and of two services with one identifier the tagged locator
silently keeps the first. `public: true` is inherited the same way, which is
what a subclass needs.

This is the "TYPO3 API entry point" exception of the rule below, not a reason to
publish services in general. Two consequences are worth knowing before reaching
for it:

- The processor stays **non-`final`**, and `process()` keeps its signature
  without a return type, because the 3.0 changelog describes how projects
  subclass it. A subclass named in TypoScript is only constructed correctly when
  the project's own `Services.yaml` registers it as a **public** service, or
  tags it `data.processor` with an identifier of its own and TypoScript names
  that identifier. Autowiring alone is not enough: both lookups ask the
  container's `has()`, which does not see a private service, so TYPO3 takes
  the `makeInstance` path and the missing constructor argument is fatal.
  `ModifyPageContactsEvent` changes the contacts without a subclass.
- `ContactsProcessor` shares `PageContactsProvider` with `ContactsController`,
  which is the point of injecting it: the rule about which contacts a visitor
  sees, and the event that changes them, exist once, and the content element
  and the page template cannot drift apart.

## A view helper with a collaborator

A view helper is a service like any other: it may take its collaborators through
the constructor, and TYPO3 resolves it from the container. No configuration is
needed for that. `EXT:fluid` registers `ViewHelperInterface` for
autoconfiguration with the tag `fluid.viewhelper`, and a compiler pass makes
every tagged service public and not shared
(`typo3/cms-fluid/Configuration/Services.php`; the view helper part of that file
is the same on v13.4 and v14.3).
A view helper is therefore a fresh instance per use, and the package's
`resource:` load is all it takes.

`academic-persons/Classes/ViewHelpers/ContractsViewHelper.php` is the example:
it injects the stateless `ContractSelector` and the core `Context`, and only
adapts template arguments to them. The rule about which contracts a profile
shows lives in the service, so a template, a test and any later PHP caller
apply the same one.

## A value per request belongs to the request

Some results of a rendering have to reach the end of the request: a view helper
that shows contracts valid today knows the next day on which that changes, and
the page cache entry must not outlive it. Collecting such a value in a shared
service - and reading it back in a listener of `ModifyCacheLifetimeForPageEvent`
- is per-request state in a service that lives for the process, exactly what
[Services are stateless](#services-are-stateless) excludes.

Core already has a request-scoped object for it. The request attribute
`frontend.cache.collector` is a `CacheDataCollectorInterface` (TYPO3 v13.3,
Feature #102422, the same on v13 and v14), and its
`restrictMaximumLifetime()` keeps the smallest lifetime it is given; the page
cache entry is written with that lifetime. `ContractsViewHelper` takes the
request from its rendering context and limits the lifetime there. Without the
attribute - a rendering outside a frontend page - there is nothing to limit,
and the view helper does nothing.

## A service a site package registers

Some services must exist without being active. The example is
`MigrateContractPublishToHiddenUpgradeWizard` of `academic_persons` (ACE-775).
It carries a removed contract flag into `hidden`, which is right for a project
whose own code gave the flag a meaning, and hides every contract of any other
installation, where each carries the default "not published". Listed in the
upgrade wizards of every installation, it would be one click, or one
`upgrade:run` without a name, away from doing that.

The class carries Symfony's `#[Exclude]` next to TYPO3's `#[UpgradeWizard]`.
The `resource:` load of the package's `Services.yaml` then registers it only as
an abstract definition tagged `container.excluded`, which the compiled container
drops, so nothing is tagged `install.upgradewizard` and the core does not know
the wizard. A site package that wants it declares the class in its own
`Services.yaml`:

```yaml
services:
  FGTCLB\AcademicPersons\Upgrades\MigrateContractPublishToHiddenUpgradeWizard:
    autowire: true
    autoconfigure: true
```

Autoconfiguration reads the `UpgradeWizard` attribute and adds the tag with the
identifier, on TYPO3 v13.4 (`cms-install/Configuration/Services.php`) and v14.3
(`cms-core/Configuration/Services.php`) alike. The declaration replaces the
excluded definition in either loading order: `FileLoader` does not register an
excluded class whose id is already defined, and a later definition overwrites
it.

The attribute sits on the class rather than in the `exclude:` list of
`Services.yaml`, so the reason the service is inactive is stated where it is
defined. Two functional tests pin both sides: one that the registry does not
know the identifier, and one with a fixture extension that declares the class,
where it does. The Important changelog entry of the extension shows the
declaration.

## Other rules

- **Do not inject the container.** Inject the concrete collaborator, or a
  tagged locator/iterator when the set of implementations is open. The one
  exception is `FrontendIconFactory` of `academic-base`, which takes an icon
  provider from the container exactly as core's `IconFactory` does,
  `has() ? get() : makeInstance()`. A locator does not fit: the providers are
  named by class in configuration files, the `icon.provider` tag exists on
  TYPO3 v14 only, and core's own `SvgIconProvider` is built by a service
  provider rather than tagged. See [Icons](icons.md#the-frontend-icon-registry).
- **Keep services private.** `public: false` is the default in every
  `Services.yaml` header here. Publish only what has to be fetched from the
  container — TYPO3 API entry points and, occasionally, functional tests — and
  make it deliberate rather than habitual. Several repositories and registries
  in this repository are `public: true`; that is not a pattern to copy without
  a reason.
- **Data objects are not services.** Models, DTOs and value objects are created
  with `new`, by a factory, or by the persistence layer. See
  [Class design](class-design.md).

## See also

- [Class design](class-design.md)
- [Core version aware code](core-version-aware-code.md)
- `AGENTS.md` — repository conventions
