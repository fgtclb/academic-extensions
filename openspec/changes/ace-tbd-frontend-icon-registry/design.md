## Context

See `proposal.md` for the motivation. Read on `main` (428cf1a32) and in both
installed cores, 13.4.35 under `core-13/vendor/typo3` (`c13` below) and 14.3.7
under `core-14/vendor/typo3` (`c14`). The analysis behind it is in
`.agent/reports/frontend-icons/01`, `06` and `09`.

How core discovers and caches `Configuration/Icons.php`, the same on both
cores apart from line numbers:

- Every package's service provider maps the service id `icons` to
  `AbstractServiceProvider::configureIcons()` (c13 `:67`, c14 `:68`), which
  requires `<package>/Configuration/Icons.php` through the static
  `requireFile()` and `array_merge()`s an array result onto an `\ArrayObject`
  (c13 `:172-183`, c14 `:199-210`). Package loading order decides, a later
  package replaces an identifier wholesale, a non-array result is ignored.
- `ServiceProvider::configureIconRegistry()` (c13 `:350-374`, c14 `:470-494`)
  reads `cache.core` (`PhpFrontend` + `SimpleFileBackend`, group `system`, c13
  `DefaultConfiguration.php:191-201`, c14 `:210-220`, with the comment that it
  "must not be abused by third party extensions") under
  `PackageDependentCacheIdentifier->withPrefix('Icons')`, builds a missing
  entry from `icons`, writes `'return ' . var_export(...) . ';'` and then
  calls `registerIcon()` per entry. An entry without `provider` gets
  `IconRegistry::detectIconProvider()` (`svg` suffix: `SvgIconProvider`,
  otherwise `BitmapIconProvider`, c13 `:552-558`, c14 `:551-557`), an entry
  with neither is skipped. `registerIcon()` throws 1437425803 for a provider
  that is not an `IconProviderInterface` (c13 `:335-349`, c14 `:334-348`).
- `PackageDependentCacheIdentifier` is byte identical on both cores, `@internal`
  but published as the public alias `package-dependent-cache-identifier` and
  built by the core service provider (c13 `ServiceProvider.php:101, 462-465`,
  c14 `:125, 617`). Its key is the prefix plus `xxh3(TYPO3 version . project
  path . package manager cache identifier)`, so a changed package set gives a
  new key without a flush. Nothing in it depends on file contents.
- Warmup: the core listener provider adds `IconRegistry::warmupCaches()` for
  `CacheWarmupEvent` (c13 `:288-295`, c14 `:393-399`). It rebuilds the
  `cache.assets` entry of the core icon set for the group `system` (c13
  `IconRegistry.php:560-576`, c14 `:559-575`), the `Icons_` entry is warmed as
  a side effect of instantiating the registry. `cache:warmup` boots fully
  before it dispatches the event (c13 `CacheWarmupCommand.php:72-106`, c14
  `:87-121`). `CacheWarmupEvent::hasGroup()` and TYPO3's `#[AsEventListener]`
  (class or method target) are the same on both cores.

How core renders an icon:

- `IconFactory::getIcon()` (c13 `:46`, c14 `:46-76`) returns a runtime cached
  `Icon` for the same identifier, size, overlay and state, substitutes
  `default-not-found` for an identifier that is neither registered nor
  deprecated (c13 `:74`, c14 `:60`), creates the icon with identifier, size,
  state and the `spinning`/`bidi` options (`createIcon()`, c13 `:495-512`, c14
  `:462-478`), and resolves the provider as `$container->has($p) ?
  $container->get($p) : GeneralUtility::makeInstance($p)` (c13 `:82`, c14
  `:68`). The overlay is another `getIcon()` with `IconSize::OVERLAY`
  (`@internal` case).
- v14 tags every `IconProviderInterface` as `icon.provider` and publishes it
  (c14 `Configuration/Services.php:32, 151`), so the container calls the
  `injectSvgDocumentFactory()`/`injectSvgDocumentService()` setters of
  `AbstractSvgIconProvider` (c14 `:48, 53`). A provider created with `new` on
  v14 fails on the first inline render on an uninitialised property. v13 has
  neither tag nor setters, `has()` answers `false` for
  `CurrentColorSvgIconProvider` and `makeInstance()` is enough.
- `Icon::render()` and the protected `Icon::wrappedIcon()` are byte identical
  on both cores (c13 `:260, 277`, c14 `:218, 235`): the wrapper `span` with
  `t3js-icon icon icon-size-<size> icon-state-<state> icon-<identifier>`
  (plus `icon-spin`, `icon-bidi`), `title`, `data-identifier`,
  `aria-hidden="true"`, the inner `<span class="icon-markup">` and the overlay
  `span`. `Icon` is neither final nor `@internal`, `getMarkup()` is
  `@internal`.
- `core:icon` (`cms-core/Classes/ViewHelpers/IconViewHelper.php`, byte
  identical on both cores) is `final`, takes `identifier` (required), `size`
  (default `IconSize::SMALL->value`), `overlay`, `state` (default
  `IconState::STATE_DEFAULT->value`), `alternativeMarkupIdentifier`, `title`,
  fetches `IconFactory` through `GeneralUtility::makeInstance()`, calls
  `setTitle()` on the runtime cached instance only when a title is given, and
  returns `$icon->render($alternativeMarkupIdentifier)`. A later call of the
  same icon without a title therefore renders the earlier title (read from the
  code, not rendered, report 01, 1.2).
- `default-not-found.svg` below `cms-core/Resources/Public/Icons/T3Icons/svgs/default/`
  is byte identical on both cores. Core itself renders the placeholder through
  the sprite set, whose markup differs between v13 and v14 (report 01, 1.3).

The model in this repository, verified: `academic-base/Classes/Settings/SettingsFileLoader.php`
is a `final` service with `#[Autowire(service: 'cache.core')] private readonly
PhpFrontend $cache` and `PackageManager`, loops
`PackageManager::getActivePackages()` in loading order, skips a missing file
and a non-array result, and writes `return <var_export>;`. It has no warmup
listener and takes fixed cache identifiers from its callers
(`AcademicJobsSettingsFactory::CACHE_IDENTIFIER = 'AcademicJobs_Settings_v3'`),
which need a flush after a package change. The loader below follows it for
the service shape and the package loop, and core for the key and the warmup.
`typo3-category-types/Classes/Loader/CategoryTypeLoader.php` is not a model: it
memoises in a property (`docs/architecture/dependency-injection.md`, "Where the
codebase does not comply").

## Goals / Non-Goals

**Goals:**

- A frontend registry that core's registry never reads and that never reads
  core's, on v13 and v14 without a version switch.
- Output of the new ViewHelper byte identical to `core:icon` on the same core
  for the same provider and options, so the follow-up changes move icons
  without touching CSS, TypeScript or rendered-markup tests.
- Stateless services, attribute configuration, nothing per request on `$this`.

**Non-Goals:**

- No extension moves an icon or switches a template in this change. The moves
  are #113 to #116, the guard against `core:icon` in frontend templates #117.
- No fallback to the core registry, for no identifier. A frontend template
  that needs a core icon registers its file in `FrontendIcons.php`.
- No JSON or JavaScript icon API, no middleware, no allow list (#618).
- No markup change. A leaner frontend markup would be its own Breaking change.
- No runtime `registerIcon()`, no icon aliases, no `deprecated` handling (the
  option reaches the provider unread), no listing API on the registry.
- No global Fluid namespace (v13 needs `SYS.fluid.namespaces`, which v14.1
  deprecates for `Configuration/Fluid/Namespaces.php`).
- No upgrade check finding for an `Icons.php` entry that should move. That
  belongs to the changes that move icons, if at all.

## Decisions

### Five classes in `academic_base`, one listener, one placeholder file

| Class                                      | Shape                                                                                                                                     |
|--------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------|
| `Imaging\FrontendIconRegistry`             | `final readonly`, `@internal`, `#[Autoconfigure(public: true)]` for the functional tests and the testing helper trait                     |
| `Imaging\FrontendIconFactory`              | `final readonly`, `@internal`, `#[Autoconfigure(public: true)]` for the same reason                                                       |
| `Imaging\FrontendIcon`                     | `final`, extends `TYPO3\CMS\Core\Imaging\Icon`, `@internal`, overrides nothing                                                            |
| `Event\CollectFrontendIconsEvent`          | `final`, `@api`, created with `new` by the registry                                                                                       |
| `ViewHelpers\IconViewHelper`               | `final`, `@internal`, the factory injected through the constructor                                                                        |
| `EventListener\WarmUpFrontendIconRegistry` | `final readonly`, `@internal`, TYPO3's `#[AsEventListener('academic-base/warm-up-frontend-icon-registry')]`, `__invoke(CacheWarmupEvent)` |

`Configuration/Services.yaml` loads `../Classes/*` with autowire and
autoconfigure already, so no configuration file changes. The ViewHelper is
public and not shared through the `fluid.viewhelper` autoconfiguration of
`EXT:fluid` on both cores, like `CategoryTypeTitleViewHelper`.

Only the event is API. The registry and the factory stay `@internal`, because
no project needs them in PHP today and promoting them later is additive.
Rejected: `@api` on the registry for projects that register from PHP. That is
exactly what the event is for, and an injectable mutable registry is the core
shape this change avoids.

### The registry is the loader: built once, read from `cache.core`

`FrontendIconRegistry` injects `#[Autowire(service: 'cache.core')] PhpFrontend`,
`PackageManager`, `EventDispatcherInterface` and
`PackageDependentCacheIdentifier`. Its public surface is
`isRegistered(string): bool`, `getIconConfiguration(string): ?array`
(`['provider' => class-string, 'options' => array]`) and `warmup(): void`.

Every lookup calls `$cache->require($key)` with
`$key = $packageDependentCacheIdentifier->withPrefix('AcademicFrontendIcons')->toString()`.
A missing entry is built and written with `set($key, 'return ' .
var_export($icons, true) . ';')`, the shape core and `SettingsFileLoader`
write. `warmup()` builds and writes unconditionally.

The build, in this order:

1. Dispatch `new CollectFrontendIconsEvent()` and take its icons.
2. For each `PackageManager::getActivePackages()` in loading order, require
   `Configuration/FrontendIcons.php` inside a `static` closure (no `$this`,
   the isolation of core's `requireFile()`), ignore a non-array result, and
   `array_merge()` it onto the result so far. A file entry therefore replaces
   a contributed one of the same identifier, and a later file an earlier one.
3. Normalise as `configureIconRegistry()` does: take `provider` out of the
   options, detect it from a `source` when missing with the same suffix rule
   as `detectIconProvider()` (a private copy, the registry must not be
   instantiated for it), skip an entry with neither and an entry that is not
   an array, and throw an `\InvalidArgumentException` with a new code that
   names the identifier for a provider that is not an `IconProviderInterface`.

The cached value is already normalised, so a lookup does no work beyond the
`require`. The registry holds no state beyond its injected services. A lookup
per icon is one `require` of an OPcache'd file whose literal array is
immutable, and the factory below asks the registry once per icon and request.

Rejected:

- An own cache `academic_base_icons` with `PhpFrontend` +
  `SimpleFileBackend` in group `system`. It respects the core comment, but
  costs a cache configuration and a service definition for one array, and
  flushes and warms exactly like `cache.core`. Core's own `Icons_` entry, both
  loaders of this repository and the decision of 2026-10-03 use `cache.core`.
- A fixed key like the `SettingsFileLoader` callers. An activated or removed
  extension would keep the old registry until a flush. The `@internal`
  identifier class is the price, see Risks.
- A memo in a property (stateful, forbidden for a new service) or in
  `cache.runtime` at registry level (a second invalidation path for a gain the
  factory's runtime cache already takes).

### Contributions from code through a build-time event

`CollectFrontendIconsEvent::addIcon(string $identifier, string $providerClass,
array $options = []): void` validates the provider immediately (an
`\InvalidArgumentException` naming the identifier, a new code) and stores
`['provider' => $providerClass] + $options`, a later call for the same
identifier replacing the earlier one. `getIcons(): array` returns what was
collected, in the file format. The event lives for one build, so it is the
only mutable object.

Applied before the files, so a site package's `FrontendIcons.php` wins over a
contribution whatever the package order (decision of 2026-10-03). #113 uses it
for the category type icons.

Rejected: a `registerIcon()` on `BootCompletedEvent`, as `category_types` does
for the core registry today. It makes the registry stateful, repeats the work
on every request including CLI, and hides the result from a request that did
not dispatch the event.

### Warmup through a listener of `CacheWarmupEvent`

`WarmUpFrontendIconRegistry` calls `warmup()` when
`$event->hasGroup('system')`, mirroring `IconRegistry::warmupCaches()`.
`cache:warmup` boots fully first, so the listeners of
`CollectFrontendIconsEvent` are registered. Invalidation is core's: every flush
of group `system` (`cache:flush`, `cache:flush -g system`, the Install Tool),
and a new key whenever `PackageStates.php` (classic mode) or the package
cache identifier derived from `composer.lock` (composer mode) changes.

Rejected: a method listener on the registry, as core does. A listener class
with one `__invoke()` is the shape of every listener in this repository
(`docs/architecture/dependency-injection.md`).

### The factory mirrors `IconFactory::getIcon()`, without the shared instance

`FrontendIconFactory::getIcon(string $identifier, IconSize $size =
IconSize::MEDIUM, ?string $overlayIdentifier = null, ?IconState $state =
null): FrontendIcon` injects the registry, `Psr\Container\ContainerInterface`
(the alias of the service container on both cores, c13 `Services.yaml:204`,
c14 `:158`) and `#[Autowire(service: 'cache.runtime')] FrontendInterface`.

1. Runtime cache key `academic-frontend-icon-` + `xxh3(identifier . size .
   overlay . state)`. A hit returns `clone $cached`.
2. An identifier the registry does not know becomes `default-not-found`
   (`FrontendIconFactory::NOT_FOUND_IDENTIFIER`). If that is not registered
   either, a `\LogicException` with a new code says so, which only a site
   package that registers `default-not-found` without provider and source can
   cause.
3. `new FrontendIcon()`, then identifier, size, `$state ?? IconState::STATE_DEFAULT`,
   `spinning` and `bidi` from the options, and the overlay as
   `getIcon($overlayIdentifier, IconSize::OVERLAY)`, all as `createIcon()`.
4. Provider: `$container->has($p) ? $container->get($p) :
   GeneralUtility::makeInstance($p)`, then `prepareIconMarkup($icon,
   $options)`.
5. Store the prepared icon in the runtime cache and return a clone.

The clone keeps core's per-request reuse, so an icon repeated in every row of
the profile editing view is read and sanitised once, while `setTitle()` only
ever reaches the copy the caller holds. `title` is the only mutable state a
caller sets, and the cached overlay icon is never mutated after preparation.

Rejected:

- `new $provider()` or `makeInstance()` alone. Breaks every inline render on
  v14, see Context. This is the test that has to run on v14.
- Returning the cached instance like core. Repeats the title leak of
  `core:icon` (report 01, 1.2).
- No runtime cache. Re-reads and re-sanitises the same SVG for every
  occurrence on a page.
- Reusing `IconFactory` with another registry. Its constructor takes the
  concrete `IconRegistry` (no interface), whose constructor loads the backend
  icon set.

### `FrontendIcon` inherits the core wrapper unchanged

`FrontendIcon extends Icon` and overrides nothing, so `render()` and
`wrappedIcon()` produce exactly the core markup on both cores. Providers keep
type hinting `Icon` and accept it. The subclass is the place a later, leaner
frontend markup overrides `wrappedIcon()` as a change of its own, without
touching providers or the factory, and it keeps an XCLASS of core's `Icon`
(created by core through `makeInstance()`) out of the frontend, because the
factory creates it with `new`.

Rejected: core's `Icon` directly. Works today, but the later markup change
would need a new class and a factory change at that point, and the frontend
would follow an XCLASS of the backend icon.

### The ViewHelper copies `core:icon` argument for argument

`ViewHelpers\IconViewHelper` registers the same six arguments with the same
types, defaults and descriptions (pointing to the frontend registry), sets
`$escapeOutput = false`, and renders
`$factory->getIcon($identifier, IconSize::from($size), $overlay,
IconState::tryFrom($state))`, `setTitle()` when a title is given, and
`render($alternativeMarkupIdentifier)`. An invalid size raises the same
`\ValueError` as `core:icon`.

Fluid namespace `http://typo3.org/ns/FGTCLB/AcademicBase/ViewHelpers`, declared
per template. A template that already declares it keeps its prefix (`p` in the
two form partials of `academic_base`), every other template declares `ab`:
`xmlns:ab="http://typo3.org/ns/FGTCLB/AcademicBase/ViewHelpers"` and
`<ab:icon identifier="…" />`. No template of this change uses it outside the
fixtures.

Rejected: a global namespace. It needs `SYS.fluid.namespaces` on v13 and
`Configuration/Fluid/Namespaces.php` on v14.1+, a version switch for a
convenience, and whether the v14.1 deprecation fires at runtime was not
verified.

### The placeholder is core's drawing, registered by `academic_base`

`academic-base/Configuration/FrontendIcons.php` ships exactly one entry:

```php
'default-not-found' => [
    'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
    'source' => 'EXT:core/Resources/Public/Icons/T3Icons/svgs/default/default-not-found.svg',
],
```

The core `SvgIconProvider`, not the `currentColor` one, because the file is
drawn in fixed colours. Default markup `<img>`, `inline` the inlined file
(sanitised on v14, `<script>` stripped on v13), like any other icon of that
provider. Both cores resolve the `EXT:core` path for both markups. The
rendered icon carries `data-identifier="default-not-found"` and
`icon-default-not-found`, so the
two-assertion test rule of `docs/architecture/icons.md` ("Keeping a template's
icons resolvable") keeps working for frontend templates unchanged. A site
package that depends on `academic_base` replaces it like any other icon.

It is the same drawing as in the backend, but not the same markup: core
renders its placeholder from the sprite set, whose markup differs between v13
and v14. The frontend placeholder is the same on both cores.

Rejected: empty markup (a typo ships invisibly), a copy of the file in
`academic_base` (one more SVG to keep in step for nothing), and the core
sprite icon (backend CSS, different per core).

### Testing helper trait

`packages-dev/testing-helper/Classes/FunctionalTestCase/FrontendIconsAssertionTrait.php`,
for this change's own tests and the moves of #113 to #116:

| Method                                             | Asserts                                                                                        |
|----------------------------------------------------|------------------------------------------------------------------------------------------------|
| `assertFrontendIconIsRegisteredWithProvider()`     | in the frontend registry, with the given provider                                              |
| `assertFrontendIconIsNotABackendIcon()`            | unknown to the core `IconRegistry`, for an icon that moved                                     |
| `assertIconIsRegisteredInBothRegistries()`         | in both, with the same provider and options                                                    |
| `assertFrontendIconMarkupFollowsTheTextColour()`   | `currentColor`, no hexadecimal colour, no `<style>`, no `id`, as `ColourSchemeAwareIconsTrait` |
| `assertRenderedFrontendIconCarriesItsIdentifier()` | rendered through the factory: `data-identifier` of the icon, no `default-not-found`            |

A second trait rather than a registry parameter on
`ColourSchemeAwareIconsTrait`, whose fifth method walks TCA and is backend by
definition (report 09, 3.3).

## Risks / Trade-offs

- [`PackageDependentCacheIdentifier` is `@internal`] → Byte identical on both
  cores and the basis of core's own `Icons_` key. A unit test builds it from a
  `PackageManager` double and pins that the key changes with the package
  cache identifier, so a core change surfaces in the suite. Fallback if core
  removes it: a fixed key plus the documented `system` flush.
- [`cache.core` against its own comment] → Deliberate, see Decisions. One
  entry, group `system`, no database table, the committed SQLite templates
  are untouched.
- [`IconSize::OVERLAY` is `@internal`] → Needed for byte identity of the
  overlay. Present on both cores. The identity test with an overlay catches a
  change.
- [The markup follows core's `wrappedIcon()` on every core update] → That is
  the contract. The identity test compares with `core:icon`, and one literal
  assertion of the wrapper classes and attributes makes an upstream change
  visible before it reaches site CSS.
- [A listener of `CollectFrontendIconsEvent` that throws] → No entry is
  written and every frontend icon render fails until it is fixed. The same as
  a broken `Icons.php` for the backend, and loud rather than silent.
- [A `FrontendIcons.php` edit in place is not seen until a `system` flush] →
  The same as `Icons.php`. The configuration chapter and the Feature entry say
  so.
- [Performance is reasoned, not measured] → Task 8.1 measures the profile
  editing page with both ViewHelpers before the follow-up changes rely on it.

## Migration Plan

Nothing to migrate in this change. Integrators may ship
`Configuration/FrontendIcons.php` now. It has an effect once a template
renders through the new ViewHelper, which the follow-up changes introduce
extension by extension with their own Breaking or Important entries.

## Open Questions

- Whether a later PHP consumer (a data processor that hands icons to
  JavaScript, the successor of #618) should get the factory as `@api`. It
  changes neither the specs nor these tasks.
