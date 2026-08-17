# Class design

Conventions for classes under `packages/fgtclb/*/Classes/` and
`packages-dev/*/Classes/`. Where the codebase is inconsistent this page says so
rather than describing an intention as a rule — 234 PHP files declaring 211
classes, 9 interfaces, 10 traits and 4 enums do not follow one style yet.

## `final` by default, and where it is impossible

89 of the 211 classes are `final` (42 %). The distribution is not random: it
tracks whether the framework instantiates the class or the container does.

| Directory                                  | final  | plain   | abstract | % final  |
|--------------------------------------------|--------|---------|----------|----------|
| `Classes/Upgrades/`                        | 9      | 0       | 0        | 100 %    |
| `Classes/Service/` and `Classes/Services/` | 9      | 3       | 0        | 75 %     |
| `Classes/EventListener/`                   | 2      | 1       | 0        | 67 %     |
| `Classes/Controller/`                      | 9      | 5       | 1        | 60 %     |
| `Classes/Domain/Model/Dto/`                | 5      | 10      | 1        | 31 %     |
| `Classes/ViewHelpers/`                     | 2      | 8       | 0        | 20 %     |
| `Classes/Domain/Model/` (excluding `Dto/`) | 0      | 23      | 0        | 0 %      |
| `Classes/Domain/Repository/`               | 0      | 16      | 0        | 0 %      |
| Everything else                            | 53     | 50      | 4        | 49 %     |
| **Total**                                  | **89** | **116** | **6**    | **42 %** |

Make a new class `final` unless something concrete prevents it. Services are
replaced through the container, not through inheritance, so extensibility is
provided by swapping the implementation behind an interface — not by leaving
the class open.

**Extbase domain models cannot be final in practice.** All 19 classes extending
`AbstractEntity` are plain `class`, and this is the framework's shape rather
than an oversight: the data mapper hydrates an instance it creates without
calling the constructor, and projects routinely extend a shipped model to add
fields. The same applies to the 16 repositories, none of which is `final`.

There is no `AbstractValueObject` subclass anywhere in the repository.

When `final` has to be dropped for a reason that is not structural, record the
reason. `academic-persons/Classes/Service/RecordSynchronizer.php` line 19 does
this and is the pattern to copy:

```php
* @final not marked as final for functional testing reasons (for now). Class should not be extended otherwise.
```

## `readonly` on properties, not on classes

**There is not a single `readonly class` declaration in this repository.**
`readonly` is used heavily, but always on individual properties: 161 modifiers,
of which 160 are constructor-promoted. The one non-promoted declaration is
`typo3-category-types/Classes/Collection/FilterCollection.php` line 15.

The split by visibility says what each is for:

| Modifier             | Count | Means                                             |
|----------------------|-------|---------------------------------------------------|
| `private readonly`   | 99    | An injected collaborator                          |
| `public readonly`    | 41    | A field of an immutable data object               |
| `protected readonly` | 21    | Either, in classes with subclasses or older style |

Use `private readonly` for every constructor-injected dependency. It states that
the service does not rebind it, which is the property half of the stateless rule
in [Dependency injection](dependency-injection.md#services-are-stateless).

`readonly class` is not used, and adopting it is not a small change: a
`readonly` class cannot extend a non-`readonly` one and vice versa, so the whole
hierarchy has to agree. With Extbase base classes in the picture that decision
is not available for models, repositories, controllers or validators. Property
level `readonly` gives the same guarantee where it matters without that
constraint.

Extbase domain models use mutable `protected` properties with getters and
setters throughout, because the data mapper assigns by reflection. That is
required, not a deviation.

## Constructor injection, and the abstract class exception

Constructor injection with promoted properties is the default: 64 files declare
159 promoted `readonly` parameters. The fullest example is
`academic-persons-edit/Classes/Controller/ContractController.php` lines 35–44 —
eight promoted `private readonly` dependencies and an empty constructor body.

**Method injection is used where a constructor is not available to take
dependencies.** There are 21 `inject*()` methods across 9 files and **zero**
`@inject` annotations — the annotation form is not used at all, which is worth
keeping true.

The legitimate case is an abstract base class. Its constructor is part of the
API of every class extending it, including classes in projects outside this
repository, so adding a dependency there breaks all of them. Method injection
keeps the constructor free:

```php
protected Context $context;

#[Required]
public function injectContext(Context $context): void
{
    $this->context = $context;
}
```

`academic-persons-edit/Classes/Controller/AbstractActionController.php` lines
62–100 does this six times, which is what lets its six `final` subclasses each
declare their own constructor.

The 6 abstract classes and what each is for:

| Class                                                                             | Purpose                                                   |
|-----------------------------------------------------------------------------------|-----------------------------------------------------------|
| `academic-persons/Classes/Types/AbstractTypes.php:17`                             | Type lists loaded from extension configuration            |
| `academic-persons/Classes/DemandValues/AbstractDemandValues.php:17`               | The same pattern for demand and filter value lists        |
| `academic-persons/Classes/Profile/AbstractProfileFactory.php:28`                  | Shared profile factory state and collaborators            |
| `academic-persons-edit/Classes/Controller/AbstractActionController.php:46`        | Shared services for the six edit controllers, `@internal` |
| `academic-persons-edit/Classes/Domain/Validator/AbstractFormDataValidator.php:17` | Extbase validator base pulling `AcademicPersonsSettings`  |
| `academic-persons-edit/Classes/Domain/Model/Dto/AbstractFormData.php:13`          | Base for the form-data DTOs                               |

Method injection on a **concrete** class does not have this justification.
`academic-persons/Classes/Controller/ProfileController.php` (three `inject*()`
methods, no constructor) and
`academic-persons-edit/Classes/Service/ListSortingService.php` line 29 are
existing code, not templates for new code — the latter is also cited in
[Dependency injection](dependency-injection.md#where-the-codebase-does-not-comply)
because its injected property is nullable and therefore mutable state.

### `GeneralUtility::makeInstance()`

91 call sites across the `Classes/` directories. Some are unavoidable: TCA and
FormEngine code under `Classes/Backend/` and `Classes/Tca/` (22 sites) runs
where no container-injected instance is available, and a `DeletedRestriction` or
similar throwaway object is not a service at all.

The rest are not unavoidable. `makeInstance()` appears inside domain models
(9 sites, for example `academic-partners/Classes/Domain/Model/Partner.php` lines
121 and 184), repositories (7) and controllers (15) — all places that can take a
constructor argument instead. Prefer injection; reach for `makeInstance()` when
there is genuinely no container, and not as a shortcut around editing a
constructor.

## Data objects are not services

Models, DTOs, value objects and collections represent data. They are created
with `new`, by a factory, or by the persistence layer — never fetched from the
container.

The immutable data objects in this repository share one recognisable shape:
`final class` with promoted `public readonly` fields.

```php
final class SynchronizerContext
{
    public function __construct(
        public readonly RecordSynchronizerInterface $recordSyncronizer,
        public readonly Site $site,
        public readonly SiteLanguage $defaultLanguage,
        public readonly array $allowedSiteLanguages,
        public readonly string $tableName,
        public readonly int $uid,
    ) {}
```

Others: `academic-base/Classes/Tca/TableConfiguration.php` (13 fields, and a
**private** constructor behind the named constructor
`TableConfiguration::create()` at line 37 — the shape to use when construction
needs validation), and the four
`academic-persons/Classes/Settings/` classes.

Not everything under `Domain/Model/Dto/` is immutable, and that is deliberate:
the `*Demand` and `*FormData` classes are mapping targets that Extbase property
mapping and form submission write into, so they are mutable by necessity. Do not
"fix" them into `readonly`.

### Keep data objects out of the container

A directory registered by `$services->load()` or a YAML `resource:` cannot tell
a service from a data object. Two mechanisms keep them out, and both are in use:

- The `exclude:` key in `Configuration/Services.yaml`, which is how the Extbase
  models are excluded in most packages.
- Symfony's `#[Exclude]` attribute on the class, for data objects that do not
  sit under an excluded path. Three sites, all in
  `academic-persons/Classes/Settings/`: `Validation.php:12`,
  `ValidationSet.php:13`, `ProfileInformationType.php:12`.

The `Settings/` classes show why the attribute is needed: they are immutable
data objects that happen to live outside `Domain/Model/`, so the package's
`exclude` does not reach them.

A data object that is registered but never type hinted is discarded when the
container is compiled, so the omission is invisible until someone type hints it
— and the resulting build failure names the data object, not the code that
referenced it.

### Enums

Four, all backed, none pure:

| Enum                                                              | Backing  |
|-------------------------------------------------------------------|----------|
| `academic-jobs/Classes/SaveForm/FlashMessageCreationMode.php:7`   | `int`    |
| `academic-persons/Classes/Profile/ProfileActionType.php:14`       | `string` |
| `academic-persons-edit/Classes/Attributes/ListSortingMode.php:12` | `string` |
| `academic-projects/Classes/Domain/Model/Dto/ActiveState.php:7`    | `string` |

Back an enum whenever its values are persisted, passed through a request, or
written into TCA — a pure enum cannot survive any of those round trips.

## The Extbase `FileReference` trap

Verified against the installed TYPO3 v13.4.34 tree:
`.Build/vendor/typo3/cms-extbase/Classes/Domain/Model/FileReference.php` is 52
lines and declares **two** public methods:

```php
public function setOriginalResource(\TYPO3\CMS\Core\Resource\FileReference $originalResource): void   // line 37
public function getOriginalResource(): \TYPO3\CMS\Core\Resource\FileReference                        // line 43
```

Everything else it has is inherited from `AbstractEntity` →
`AbstractDomainObject`: `getUid()`, `getPid()`, `setPid()` and the
underscore-prefixed internal API.

It has **no** `getTitle()`, `getAlternative()`, `getDescription()`, `getLink()`,
`getName()`, `getPublicUrl()`, `getProperty()` or `getProperties()`.

The consequence in Fluid is the trap, because Fluid does not raise an error for
a property it cannot resolve — it renders an empty string. So
`{profile.image.title}` produces silently empty markup: an `alt` attribute that
is present and blank, a caption block that renders as an empty element. Nothing
in a test or a log points at it.

Go through `originalResource` to reach the core
`TYPO3\CMS\Core\Resource\FileReference`, which does expose those getters:

```html
alt="{profile.image.originalResource.alternative}"
title="{profile.image.originalResource.title}"
```

`academic-persons-edit/Resources/Private/Partials/Profile/Show/Image.html` lines
50, 51, 56 and 57 is the only place in the repository that accesses file
reference metadata, and it is the only place using `originalResource`.
Everywhere else the Extbase `FileReference` is passed straight to `<f:image>` or
`<f:uri.image>`, which resolves it internally — no property access, no trap.

One further step down: on the **core** `FileReference`, `getProperty()` throws
`\InvalidArgumentException` (code 1314226805) when the property is missing —
verified at `.Build/vendor/typo3/cms-core/Classes/Resource/FileReference.php`
lines 112–119. For an optional field use `getProperties()` (line 141) and index
into it, or guard with `hasProperty()` (line 101). Do not call `getProperty()`
on a field that may not be set.

## Strict types

226 of the 234 files declare `strict_types=1`. New files must. The 8 that do not
are worth knowing so they are fixed rather than copied:

| File                                                                  |
|-----------------------------------------------------------------------|
| `academic-partners/Classes/DataProcessing/PartnershipProcessor.php`   |
| `academic-partners/Classes/DataProcessing/PartnerProcessor.php`       |
| `academic-contact4pages/Classes/DataProcessing/ContactsProcessor.php` |
| `academic-programs/Classes/DataProcessing/ProgramDataProcessor.php`   |
| `academic-persons/Classes/Event/ModifySelectedProfilesEvent.php`      |
| `academic-persons/Classes/Event/ModifySelectedContractsEvent.php`     |
| `academic-persons/Classes/Settings/Validation.php`                    |
| `academic-projects/Classes/ViewHelpers/Format/ReplaceViewHelper.php`  |

Four of the eight are `DataProcessing/` classes, which suggests one origin
rather than eight independent omissions.

## Static analysis

PHPStan runs at **level 8** in both `Build/phpstan/Core13/phpstan.neon` and
`Build/phpstan/Core14/phpstan.neon` (line 13 of each). Level 8 is what makes the
nullability of a property meaningful, so a `?Foo $foo = null` that is really
always set has to be justified rather than assumed.

Note that `paths` is `../../../packages` only: **`packages-dev/` is not analysed
by PHPStan**. All three packages there, including the functional test traits in
`packages-dev/testing-helper/`, are outside the gate. Keep that in mind when
changing them — lint and the tests themselves are the only checks they get.

## See also

- [Dependency injection](dependency-injection.md)
- [Core version aware code](core-version-aware-code.md)
- `AGENTS.md` — repository conventions and database query rules
