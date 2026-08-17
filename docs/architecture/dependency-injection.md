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
| `packages/fgtclb/academic-base`          | **yes**        | yes             |
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
| `packages-dev/dev-site`                  | –              | –               |
| `packages-dev/monorepo-shared`           | –              | –               |
| `packages-dev/testing-helper`            | –              | –               |

So: **11 `Services.yaml`, 2 `Services.php`**, and both packages carrying a
`Services.php` carry a `Services.yaml` next to it. Four packages have neither —
`academic-persons-sync` ships only domain models under `Classes/Domain/`, and
none of the three `packages-dev/` packages has a `Classes/` requiring
registration: two hold constraints and test traits, and `dev-site` holds seed
definitions and instance configuration rather than code.

Three further `Services.yaml` files exist under `Tests/Functional/Fixtures/
Extensions/` in `academic-base`, `academic-persons` and `typo3-category-types`.
They belong to fixture extensions, not to shipped code.

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
which is why the two styles coexist without friction. Beyond the header, eight
files carry real definitions: three `event.listener` tags, three
`data.processor` tags, two `extbase.type_converter` tags, two `console.command`
tags, four factory-produced services, one interface alias, and eight
`public: true` markers — on repositories, registries, a settings object and
that alias. Three
files — `academic-bite-jobs`, `academic-contact4pages` and
`academic-study-plan` — are the header and nothing else.

Two spellings are worth knowing because they are inconsistent across the
packages and the difference is not cosmetic:

- `resource: '../Classes/*'` (ten files) versus `resource: '../Classes'`
  (`academic-contact4pages`)
- `exclude: '../Classes/Domain/Model/*.php'` (five files) versus
  `'../Classes/Domain/Model/*'` (two) versus `'../Classes/Domain/Model'` (one)
  versus no `exclude` at all — `academic-bite-jobs`, `academic-persons-edit`
  and `academic-study-plan` exclude nothing

The `exclude` is what keeps Extbase domain models out of the container. A model
that is registered but never type hinted is dropped again when the container is
compiled, so omitting it breaks nothing and warns about nothing — until someone
type hints the model, and the container then fails to build with an error
pointing at the model rather than at the code that referenced it.

`academic-base/Configuration/Services.yaml` lines 11–12 additionally carry two
**commented out** excludes for `../Classes/Core12/*` and `../Classes/Core13/*`.
That job is done by the `#[Exclude]` attribute on the classes themselves
instead — see
[Core version aware code](core-version-aware-code.md#the-folder-split-in-academic-base).

### The two `Services.php` files

Neither is the boilerplate `defaults()` + `load()` file the preference implies.
Each exists for something YAML cannot express, and does only that.

[`packages/fgtclb/academic-persons/Configuration/Services.php`](../../packages/fgtclb/academic-persons/Configuration/Services.php)
registers autoconfiguration for three interfaces (lines 20–26):

```php
return static function (ContainerConfigurator $container, ContainerBuilder $containerBuilder): void {
    $containerBuilder->registerForAutoconfiguration(TypesInterface::class)->setPublic(true);
    $containerBuilder->registerForAutoconfiguration(DemandValuesInterface::class)->setPublic(true);
    $containerBuilder->registerForAutoconfiguration(ProfileFactoryInterface::class)
        ->setPublic(true)
        ->setShared(true);
};
```

[`packages/fgtclb/academic-base/Configuration/Services.php`](../../packages/fgtclb/academic-base/Configuration/Services.php)
does the core version aware wiring of the `Classes/Core12/` and
`Classes/Core13/` folders: it repeats the `_defaults` and the `Classes/*` load,
then registers the two per-version services, one factory argument and two
interface aliases. It is the only file in the repository that has to be a PHP
one — the namespace prefix is computed at runtime.

In both packages the registration of the ordinary classes still happens in
`Services.yaml`. TYPO3 loads both files when both are present.

### Attributes are already in use

Contrary to the note in `AGENTS.md` that these extensions do not use attributes,
they are used in production code across seven packages:

| Attribute            | Sites   | Examples                                                                    |                                                |
| -------------------- | ------- | --------------------------------------------------------------------------- |                                                |
| `#[Exclude]`         | 12      | the eight `Classes/Core12                                                   | Core13/` types and `EnvironmentBuilderFactory` |
| `#[Autoconfigure]`   | 9       | `academic-base/Classes/Service/ArrayObjectMapper.php:24` (`public: true`)   |                                                |
| `#[Autowire]`        | 7       | same file, line 28 — `#[Autowire(service: 'academic-base.serializer')]`     |                                                |
| `#[AsAlias]`         | 2       | `academic-persons/Classes/Service/RecordSynchronizer.php:21`                |                                                |
| `#[AsCommand]`       | 1       | `academic-partners/Classes/Command/GeocodeCommand.php:23`                   |                                                |

`#[AsCommand]` there is Symfony's **Console** attribute
(`Symfony\Component\Console\Attribute\AsCommand`), not a DI one; the two
commands in `academic-persons` are still registered with `console.command` tags
in YAML. `#[AsTaggedItem]`, `#[AsController]` and `#[AsEventListener]` have zero
sites.

Nine of the twelve `#[Exclude]` sites are the core version split in
`academic-base`, where the attribute keeps the wrong version's classes out of
the compiled container. The remaining three are data objects in
`academic-persons/Classes/Settings/` — a different job for the same attribute,
described in [Class design](class-design.md#keep-data-objects-out-of-the-container).

The `#[Autowire]` example is the clearest illustration of the two styles working
together: `academic-base/Configuration/Services.yaml` lines 15–17 define a
factory-produced service under the string id `academic-base.serializer`, and the
consuming class pins it to one constructor argument by attribute. A string id
cannot be autowired by type, so it has to be named somewhere — naming it on the
argument keeps that fact next to the code that depends on it.

Four of the seven `#[Autowire]` sites do the same for a core cache
(`cache.core`, `cache.typoscript`).

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
  core version aware registration, and definitions for services whose class this
  repository does not own.

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
lines 21–27: two attributes, one injected collaborator, no instance state.

```php
#[AsAlias(id: RecordSynchronizerInterface::class, public: true)]
#[Autoconfigure(public: true)]
class RecordSynchronizer implements RecordSynchronizerInterface
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}
```

The three event listeners follow the same shape — a single `__invoke()` and
promoted `private readonly` dependencies:
`academic-jobs/Classes/EventListener/GenerateJobSlug.php`,
`academic-persons-edit/Classes/EventListener/GenerateSlugForProfile.php` and
`.../SyncChangesToTranslations.php`.

### Where the codebase does not comply

Stating this plainly, because a rule presented as universally followed is a rule
nobody checks:

- [`academic-bite-jobs/Classes/Services/BiteJobsService.php`](../../packages/fgtclb/academic-bite-jobs/Classes/Services/BiteJobsService.php)
  line 21 holds `protected $responseBody;`, written in `fetchBiteJobs()` and
  read afterwards. The code says so itself, at line 19:
  `@todo Response state on a service class ? A really really bad idea.`
- [`academic-persons-edit/Classes/Service/ListSortingService.php`](../../packages/fgtclb/academic-persons-edit/Classes/Service/ListSortingService.php)
  line 26 keeps a nullable `PersistenceManagerInterface` filled by a
  `#[Required] injectPersistenceManager()` method (line 29), while its own
  docblock at line 18 reads `@note Service must be kept stateless.`
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
checked against **both** before it is used. On this branch that check is
sharper than on `main`, because TYPO3 v12 predates most of the attribute API.

**What was checked, and how.** No TYPO3 v12 vendor tree is installed in this
checkout. The v12 column below was read out of the `typo3/cms-core`,
`typo3/cms-backend`, `typo3/cms-extbase` and `symfony/dependency-injection`
**dist archives in `.cache/composer/files/`**, at the versions this branch
resolves to (`typo3/cms-core` v12.4.45, matching `core-12/composer.lock`). That
is the shipped source of those releases, but it is not an installed tree, and
it is not proof of what a given project ends up with. The v13 column was read
from `.Build/vendor/` at TYPO3 v13.4.34 — a version this branch supports,
although that particular tree was installed for the `main` branch and is not
this branch's dependency set.

### Symfony attributes are not identical across the two versions

TYPO3 v12.4 and v13.4 both allow `symfony/dependency-injection` `^6.4 || ^7.0`,
and which one is installed follows from the PHP version: Symfony 7 requires PHP
8.2, so a v12 run on PHP 8.1 gets Symfony 6.4. The tracked lock files show both
in use — `core-12/composer.lock` pins v6.4.43, `core-13/composer.lock` pins
v7.4.16.

The 6.4 line ships 17 DI attributes, the 7.4 line 21. Everything this
repository uses is in both:

| Attribute                                                      | 6.4 | 7.4 | Use                                                    |
|----------------------------------------------------------------|-----|-----|--------------------------------------------------------|
| `#[Autoconfigure]`                                             | yes | yes | Publish, mark non-shared, add tags                     |
| `#[AsAlias]`                                                   | yes | yes | Register the default implementation of an interface    |
| `#[Autowire]`                                                  | yes | yes | Pin a service, parameter or expression to one argument |
| `#[Exclude]`                                                   | yes | yes | Keep a class out of the container                      |
| `#[AsTaggedItem]`, `#[AutowireIterator]`, `#[AutowireLocator]` | yes | yes | Tagged collections                                     |
| `#[AsDecorator]`, `#[Target]`, `#[When]`                       | yes | yes | Less common, present on both                           |

These five are **7.x only** and must not be used while v12 is supported:
`#[Lazy]`, `#[AutowireInline]`, `#[AutowireMethodOf]`, `#[WhenNot]` and
`#[AutoconfigureResourceTag]`. Going the other way, `#[MapDecorated]` exists
only on 6.4 — it was removed in Symfony 7 — so it is equally unusable.

### TYPO3 attributes diverge more sharply

| Attribute                                    | v12.4.45 | v13.4.34 | Safe on both |
|----------------------------------------------|----------|----------|--------------|
| `TYPO3\CMS\Core\Attribute\AsAllowedCallable` | yes      | yes      | **yes**      |
| `TYPO3\CMS\Core\Attribute\WebhookMessage`    | yes      | yes      | **yes**      |
| `TYPO3\CMS\Backend\Attribute\AsController`   | yes      | yes      | **yes**      |
| `TYPO3\CMS\Install\Attribute\UpgradeWizard`  | yes      | yes      | **yes**      |
| `TYPO3\CMS\Core\Attribute\AsEventListener`   | **no**   | yes      | no           |

`TYPO3\CMS\Core\Attribute\` holds exactly two files on v12.4.45 —
`AsAllowedCallable.php` and `WebhookMessage.php`. `AsEventListener` does not
appear anywhere in the v12 `typo3/cms-core` package.

`TYPO3\CMS\Extbase\Attribute\*` exists on neither version; the annotation form
`TYPO3\CMS\Extbase\Annotation\*` is current on both and is what this branch
uses. There is no `TYPO3\CMS\Core\Attribute\Autoconfigure` and no
`TYPO3\CMS\Core\Attribute\AsCommand` on either version; use the Symfony
attributes for those.

## Registering event listeners

`main` carries a rule reading "never use Symfony's `#[AsEventListener]`, always
TYPO3's". On this branch the second half of that rule is not available: TYPO3's
attribute does not exist on v12, so a listener registered with it would simply
be a fatal error there.

**Register event listeners with the `event.listener` YAML tag.** All three
production listeners do
(`academic-jobs/Configuration/Services.yaml` lines 11–15 and
`academic-persons-edit/Configuration/Services.yaml` lines 10–20):

```yaml
FGTCLB\AcademicJobs\EventListener\GenerateJobSlug:
  tags:
    - name: event.listener
      identifier: generateJobSlug
      event: FGTCLB\AcademicJobs\Event\AfterSaveJobEvent
```

Always set `identifier` explicitly — it is what `before`/`after` ordering in
other extensions refers to, and an auto-derived one changes when the class is
renamed.

The first half of the `main` rule still holds, and matters more here because
the safe alternative is a tag rather than another attribute:

| Class                                                         | v12.4.45 | v13.4.34 | Emits tag               |
|---------------------------------------------------------------|----------|----------|-------------------------|
| `TYPO3\CMS\Core\Attribute\AsEventListener`                    | **no**   | yes      | `event.listener`        |
| `Symfony\Component\EventDispatcher\Attribute\AsEventListener` | yes      | yes      | `kernel.event_listener` |

Symfony's attribute is importable on both versions, so nothing stops it being
reached for when the TYPO3 one turns out to be missing. It produces
`kernel.event_listener`, which is read by Symfony's `RegisterListenersPass` — a
compiler pass TYPO3 does not register. Nothing reads the tag, so **the container
builds cleanly and the listener is never called**. There is no error, no warning
and no failing test unless a test asserts the effect of the listener. That
silence is the entire reason for the rule.

Note that `academic-persons`' own user manual documents the TYPO3 attribute as
the way integrators register a listener against its events, in
`packages/fgtclb/academic-persons/Documentation/Changelog/2.4/Feature-DispatchModifyTcaSelectFieldItemsEventInItemsProcFunc.rst`.
That is aimed at a project, which knows its own core version; it is not a
licence to use the attribute in these extensions.

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
