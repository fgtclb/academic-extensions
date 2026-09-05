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
`extbase.type_converter` tag, two `console.command` tags, factory-produced
services, one interface alias, and several `public: true` markers on
repositories and registries.

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
they are used in production code across nine of the twelve packages
(`academic-base`, `academic-jobs`, `academic-partners`, `academic-persons`,
`academic-persons-edit`, `academic-programs`, `academic-projects`,
`academic-study-plan` and `typo3-category-types`):

Measured with
`grep -rhoP '#\[<name>[(\]]' --include='*.php' packages/fgtclb/*/Classes packages-dev/*/Classes | wc -l`:

| Attribute            | Sites | Examples                                                                     |
|----------------------|-------|------------------------------------------------------------------------------|
| `#[Autoconfigure]`   | 10    | `academic-base/Classes/Service/ArrayObjectMapper.php:24` (`public: true`)    |
| `#[Autowire]`        | 6     | same file, line 28 — `#[Autowire(service: 'academic-base.serializer')]`      |
| `#[AsAlias]`         | 3     | `academic-persons/Classes/Service/RecordSynchronizer.php:49`                 |
| `#[Exclude]`         | 11    | `academic-base/Classes/Settings/Validation.php:23` and the settings graph    |
| `#[AsEventListener]` | 3     | `academic-partners/Classes/EventListener/RegisterAcademicPageDoktype.php:33` |
| `#[AsCommand]`       | 1     | `academic-partners/Classes/Command/GeocodeCommand.php:23`                    |

`#[AsCommand]` there is Symfony's **Console** attribute
(`Symfony\Component\Console\Attribute\AsCommand`), not a DI one; the other two
commands in `academic-persons` are still registered with `console.command` tags
in YAML. The `#[AsEventListener]` sites are TYPO3's attribute (see below), one
per `RegisterAcademicPageDoktype` listener in `academic-partners`,
`academic-programs` and `academic-projects`. `#[AsTaggedItem]` and
`#[AsController]` have zero sites.

For the eleven `#[Exclude]` sites and why `LegacySettingsMigration` is among
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

The seven event listeners follow the same shape — a single `__invoke()` and
promoted `private readonly` dependencies (or a `readonly class`). Four are
registered by YAML tag: `academic-jobs/Classes/EventListener/GenerateJobSlug.php`,
`academic-persons/Classes/EventListener/UpdateProfileImageMetadata.php`,
`academic-persons-edit/Classes/EventListener/GenerateSlugForProfile.php` and
`.../SyncChangesToTranslations.php`. Three are registered by attribute — the
`RegisterAcademicPageDoktype` of `academic-partners`, `academic-programs` and
`academic-projects`.

### Where the codebase does not comply

Stating this plainly, because a rule presented as universally followed is a rule
nobody checks:

- [`academic-bite-jobs/Classes/Services/BiteJobsService.php`](../../packages/fgtclb/academic-bite-jobs/Classes/Services/BiteJobsService.php)
  line 21 holds `protected $responseBody;`, written in `fetchBiteJobs()` and
  read afterwards. The code says so itself, at line 19:
  `@todo Response state on a service class ? A really really bad idea.`
- [`academic-persons-edit/Classes/Service/ListSortingService.php`](../../packages/fgtclb/academic-persons-edit/Classes/Service/ListSortingService.php)
  line 26 keeps a nullable `PersistenceManagerInterface` filled by a
  `#[Required] injectPersistenceManager()` method, while its own docblock at
  line 18 reads `@note Service must be kept stateless.`
- `academic-jobs/Classes/Loader/AcademicJobsSettingsLoader.php:15` and
  `typo3-category-types/Classes/Loader/CategoryTypeLoader.php:16` memoize their
  built registry in a nullable property. Both are `load()` factories for a
  shared, `public: true` registry, so the cached value is process-wide.
- `academic-persons/Classes/Profile/AbstractProfileFactory.php` lines 34 and 38
  hold genuine runtime state (`$autoCreateProfiles`,
  `$userGroupsToCreateProfilesFor`) filled from `initializeObject()`. Its
  subclass `ProfileFactory` is registered `#[Autoconfigure(public: true,
  shared: true)]` (line 25) — explicitly shared while stateful.

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
`Install\Attribute\UpgradeWizard` row is the one all eleven upgrade wizards use:
on v14 it survives as a deprecated subclass shim in
`cms-core/DeprecatedClasses/ext-install/`, so it still works, but its
replacement `Core\Attribute\UpgradeWizard` is absent on v13. See
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

Both spellings are in use here: four listeners are registered by YAML tag
(`academic-jobs/Configuration/Services.yaml`,
`academic-persons/Configuration/Services.yaml` and
`academic-persons-edit/Configuration/Services.yaml`, which carries two), and the
three `RegisterAcademicPageDoktype` listeners of `academic-partners`,
`academic-programs` and `academic-projects` carry
`#[AsEventListener(identifier: '…/register-page-doktype')]` on the class. The
two are equivalent — the attribute is only a shorter spelling of the same tag —
and new listeners should prefer the attribute. Note that `academic-persons`' own
user manual already documents the TYPO3 attribute as the way integrators register
a listener against its events, in
`packages/fgtclb/academic-persons/Documentation/Changelog/2.4/Feature-DispatchModifyTcaSelectFieldItemsEventInItemsProcFunc.rst`
lines 41–43.

## Other rules

- **Do not inject the container.** Inject the concrete collaborator, or a
  tagged locator/iterator when the set of implementations is open.
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
