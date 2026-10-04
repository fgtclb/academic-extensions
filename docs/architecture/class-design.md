# Class design

Conventions for classes under `packages/fgtclb/*/Classes/` and
`packages-dev/*/Classes/`. Where the codebase is inconsistent this page says so
rather than describing an intention as a rule: 374 PHP files declaring 328
classes, 11 interfaces, 20 traits and 15 enums do not follow one style yet.

The counts on this page are measured over `packages/fgtclb/*/Classes/` and
`packages-dev/*/Classes/` together, unless a section says otherwise:

```bash
find packages/fgtclb/*/Classes packages-dev/*/Classes -name '*.php' | wc -l
grep -rhoP '^(?:(?:final|abstract|readonly)\s+)*class\b' --include='*.php' \
  packages/fgtclb/*/Classes packages-dev/*/Classes | sort | uniq -c
```

## `final` by default, and where it is impossible

220 of the 328 classes are `final` (67 %). The distribution is not random: it
tracks whether the framework instantiates the class or the container does.

| Directory                                  | final | plain | abstract | % final |
|--------------------------------------------|-------|-------|----------|---------|
| `Classes/Upgrades/`                        | 17    | 0     | 0        | 100 %   |
| `Classes/Service/` and `Classes/Services/` | 32    | 2     | 0        | 94 %    |
| `Classes/EventListener/`                   | 14    | 1     | 0        | 93 %    |
| `Classes/Controller/`                      | 9     | 0     | 0        | 100 %   |
| `Classes/Domain/Model/Dto/`                | 8     | 10    | 1        | 42 %    |
| `Classes/ViewHelpers/`                     | 9     | 6     | 0        | 60 %    |
| `Classes/Domain/Model/` (excluding `Dto/`) | 1     | 23    | 0        | 4 %     |
| `Classes/Domain/Repository/`               | 0     | 16    | 0        | 0 %     |
| Everything else                            | 130   | 44    | 5        | 73 %    |

Make a new class `final` unless something concrete prevents it. Services are
replaced through the container, not through inheritance, so extensibility is
provided by swapping the implementation behind an interface — not by leaving
the class open. What a project may build on instead is stated in
[Extension points](#extension-points).

**Extbase domain models cannot be final in practice.** All 19 classes extending
`AbstractEntity` are plain `class`, and this is the framework's shape rather
than an oversight: the data mapper hydrates an instance it creates without
calling the constructor, and projects routinely extend a shipped model to add
fields. The same applies to the 16 repositories, none of which is `final`.

There is no `AbstractValueObject` subclass anywhere in the repository.

When `final` has to be dropped for a reason that is not structural, record the
reason. `academic-persons/Classes/Service/RecordSynchronizer.php` line 47 does
this and is the pattern to copy:

```php
* @final not marked as final for functional testing reasons (for now). Class should not be extended otherwise.
```

## `readonly` on properties, and on stateless service classes

`readonly` is used heavily, mostly on individual properties: 379 modifiers, of
which 366 are constructor-promoted, across 114 files. The thirteen non-promoted
declarations are the nine documented fields of
`academic-persons/Classes/Settings/AcademicPersonsSettings.php`, the three
fields `typo3-category-types/Classes/Routing/Aspect/CategoryFilterMapper.php`
sets in its constructor, and
`typo3-category-types/Classes/Collection/FilterCollection.php` line 17.

```bash
grep -rhoP '\b(private|public|protected) readonly\b' --include='*.php' \
  packages/fgtclb/*/Classes packages-dev/*/Classes | sort | uniq -c
grep -rP '\b(private|public|protected) readonly\b' --include='*.php' -n \
  packages/fgtclb/*/Classes packages-dev/*/Classes | grep -vP ';\s*$' | wc -l
```

The second command counts the promoted ones: a promoted parameter never ends
the line with a semicolon and a declared property always does.

`final readonly class` is the shape of a **stateless service that extends
nothing**, and of an immutable data object. There are 75:

```bash
grep -rh '^final readonly class' --include='*.php' \
  packages/fgtclb/*/Classes packages-dev/*/Classes | wc -l
```

The count is given rather than the list, because an enumeration of names is
what goes stale: this one read "sixteen" until 2026-09-20, when it was 29. The
instructive members are the three `RegisterAcademicPageDoktype` listeners
(partners, programs, projects), which are identical but for their extension;
the two payload data objects of `academic-persons-edit`,
`ProfileUpdatePayload` and `ProfileUpdateRequestResult`, which are the data
object half of the shape; and `ContractSelectScope` of `academic-persons`,
which is a data object whose producer, `ContractSelectScopeResolver`, carries
the same modifier as a service.

The class-level modifier says the same thing as `private readonly` on every
property, once, and the compiler enforces it for properties added later.

The split by visibility says what each is for:

| Modifier             | Count | Means                                             |
|----------------------|-------|---------------------------------------------------|
| `private readonly`   | 237   | An injected collaborator                          |
| `public readonly`    | 122   | A field of an immutable data object               |
| `protected readonly` | 20    | Either, in classes with subclasses or older style |

Use `private readonly` for every constructor-injected dependency. It states that
the service does not rebind it, which is the property half of the stateless rule
in [Dependency injection](dependency-injection.md#services-are-stateless).

`readonly class` is not a general rule, because adopting it is not a small
change where a hierarchy exists: a `readonly` class cannot extend a
non-`readonly` one and vice versa, so the whole hierarchy has to agree. With
Extbase base classes in the picture that decision is not available for models,
repositories, controllers or validators, and property level `readonly` gives
the same guarantee there without that constraint.

Extbase domain models use mutable `protected` properties with getters and
setters throughout, because the data mapper assigns by reflection. That is
required, not a deviation.

## Constructor injection, and the abstract class exception

Constructor injection with promoted properties is the default: 95 files declare
312 promoted `readonly` parameters. The fullest example by a wide margin is
`academic-persons-edit/Classes/Controller/ProfileController.php` — 36 promoted
`private readonly` dependencies and an empty constructor body. That number is a
known problem rather than a model: splitting the controller is ACE-507.

**Method injection is used where a constructor is not available to take
dependencies.** There are 13 `inject*()` methods across 7 files and **zero**
`@inject` annotations — the annotation form is not used at all, which is worth
keeping true.

The legitimate case is an abstract base class. Its constructor is part of
the API of every class extending it, including classes in projects outside this
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

`academic-persons-edit/Classes/Domain/Validator/AbstractFormDataValidator.php`
does it once, for the settings graph an Extbase validator cannot take through a
constructor.

The 6 abstract classes and what each is for:

| Class                                                                             | Purpose                                                  |
|-----------------------------------------------------------------------------------|----------------------------------------------------------|
| `academic-persons/Classes/Types/AbstractTypes.php:17`                             | Type lists loaded from extension configuration           |
| `academic-persons/Classes/DemandValues/AbstractDemandValues.php:17`               | The same pattern for demand and filter value lists       |
| `academic-persons/Classes/Profile/AbstractProfileFactory.php:28`                  | Shared profile factory state and collaborators           |
| `academic-persons-edit/Classes/Domain/Validator/AbstractFormDataValidator.php:21` | Extbase validator base pulling `AcademicPersonsSettings` |
| `academic-persons-edit/Classes/Domain/Model/Dto/AbstractFormData.php:10`          | Base for the form-data DTOs                              |
| `packages-dev/testing-helper/Classes/TestCase/FunctionalTestCase.php:31`          | Base of every functional test case of the repository     |

The plugin controllers of `academic_partners`, `academic_projects` and
`academic_programs` took the `ExtensionService`, the `FilterTypeResolver` and
the `ProgramFactsBuilder` through `final` `inject*()` methods while projects
could still subclass them, so that a subclass calling `parent::__construct()`
kept working. Since every plugin controller is `final` (ACE-803), those
services are constructor arguments like any other.

Method injection on a **concrete** class does not have this justification. Eight
of the thirteen methods are on concrete classes all the same: one each on
`ContactsController` of `academic_contacts4pages`, `JobValidator` of
`academic_jobs` and `ListSortingService` of `academic_persons_edit`, two on
`ProgramRepository` of `academic_programs` and three on `ProfileRepository` of
`academic_persons`. They are existing code, not a template for new code. The
second and third method of those two repositories follow the first of their
own file. The repositories of jobs, partners, projects and contacts take the
same collaborator through a constructor that calls `parent::__construct()`,
the Extbase `Repository` constructor that derives the model class.
`academic-persons-edit/Classes/Service/ListSortingService.php` line 29 is also
cited in
[Dependency injection](dependency-injection.md#where-the-codebase-does-not-comply)
because its injected property is nullable and therefore mutable state.
`academic-persons/Classes/Controller/ProfileController.php` used to be
another example, with three `inject*()` methods and no constructor. The moment
it needed a fourth collaborator, the four became a constructor with promoted
`private readonly` properties — an Extbase `ActionController` has no
constructor of its own, so nothing has to be forwarded. The `ProfileController`
of `academic-persons-edit` is built the same way, with 36 of them.

### `GeneralUtility::makeInstance()`

```bash
grep -rho 'GeneralUtility::makeInstance(' --include='*.php' \
  packages/fgtclb/*/Classes packages-dev/*/Classes | wc -l
```

120 call sites across the `Classes/` directories. Some are unavoidable: TCA and
FormEngine code under `Classes/Backend/` and `Classes/Tca/` (23 sites) runs
where no container-injected instance is available, and a `DeletedRestriction` or
similar throwaway object is not a service at all.

The rest are not unavoidable. `makeInstance()` appears inside domain models
(9 sites, for example `academic-partners/Classes/Domain/Model/Partner.php` lines
121 and 222), repositories (16) and controllers (8) — all places that can take a
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
**private** constructor at line 21 behind the named constructor
`TableConfiguration::create()` at line 37 — the shape to use when construction
needs validation), the nine value objects of the
`academic-persons/Classes/Settings/` graph, and `Validation` and `ValidationSet`
in `academic-base/Classes/Settings/`, the shared value objects those settings
are built from.

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
  sit under an excluded path. Twenty-seven sites
  (`grep -rn '#\[Exclude\]' --include='*.php' packages/fgtclb/*/Classes`):
  `Validation` and `ValidationSet` in `academic-base/Classes/Settings/`,
  `AfterSaveDecision` in `academic-base/Classes/Form/`, fourteen
  classes under `academic-persons/Classes/Settings/` (the value objects of the
  settings graph, of the frontend user synchronisation and of the managed
  fields, plus `LegacySettingsMigration` and `SettingsOverride`), the two value
  objects of the contract selection, `ContractSelection` and
  `ContractSelectionResult` in `academic-persons/Classes/Service/`, the five
  data objects of the import writer in `academic-persons/Classes/Import/`, the
  upgrade wizard of `academic-persons` that a site package registers itself,
  and the two routing aspects the core builds itself, `CategoryFilterMapper`
  of `typo3-category-types` and `PersonsFilterSlugMapper` of
  `academic-persons`.
  `LegacySettingsMigration` is not a settings value object but the result of
  the legacy settings overlay. It is excluded for the same reason: it is data
  the factory produces, not a service the container builds. A settings object
  that is itself a service built by a factory, `AcademicPersonsSettings` or
  `AcademicJobsSettings`, carries no `#[Exclude]`.

The `Settings/` classes show why the attribute is needed: they are immutable
data objects that happen to live outside `Domain/Model/`, so the package's
`exclude` does not reach them.

A data object that is registered but never type hinted is discarded when the
container is compiled, so the omission is invisible until someone type hints it
— and the resulting build failure names the data object, not the code that
referenced it.

### Enums

Fifteen, all backed, none pure
(`grep -rl '^enum' --include='*.php' packages/fgtclb/*/Classes packages-dev/*/Classes`):

| Enum                                                                   | Backing  |
|------------------------------------------------------------------------|----------|
| `academic-base/Classes/Form/FlashMessageCreationMode.php:10`           | `int`    |
| `academic-base/Classes/Upgrade/ConfigurationFindingKind.php:13`        | `string` |
| `academic-base/Classes/Upgrade/TemplateOverrideFindingKind.php:14`     | `string` |
| `academic-bite-jobs/Classes/Enumeration/ListView.php:10`               | `string` |
| `academic-contact4pages/Classes/Event/PageContactsOutput.php:14`       | `string` |
| `academic-persons-edit/Classes/Attributes/ListSortingMode.php:12`      | `string` |
| `academic-persons-edit/Classes/Event/ProfileEditingAction.php:25`      | `string` |
| `academic-persons/Classes/DataHandling/ProfileWriteCorrelation.php:35` | `string` |
| `academic-persons/Classes/Event/ProfileUpdateOrigin.php:21`            | `string` |
| `academic-persons/Classes/Import/ImportedRecordOutcome.php:20`         | `string` |
| `academic-persons/Classes/Import/RetirePolicy.php:20`                  | `string` |
| `academic-persons/Classes/Profile/ProfileActionType.php:17`            | `string` |
| `academic-persons/Classes/Service/ContractDisplay.php:15`              | `string` |
| `academic-programs/Classes/Enumeration/ProgramFactsPlace.php:11`       | `string` |
| `academic-projects/Classes/Domain/Model/Dto/ActiveState.php:7`         | `string` |

Back an enum whenever its values are persisted, passed through a request, or
written into TCA — a pure enum cannot survive any of those round trips.

A getter that a Fluid template reads returns the case value, not the enum.
Fluid cannot cast an enum to a string: on Fluid 4.6 (TYPO3 v13) and 5.3
(TYPO3 v14) alike, `{state}` in a text, in an attribute or concatenated into a
ViewHelper argument throws `Cannot cast object ... to string` (1273753083),
and `{state} == 'active'` is false. Only `{state.value}`, a comparison with
`f:constant` and the enum passed on alone work. That is why
`Project::getActiveState()` of `academic_projects` returns `active` or
`completed`, the value of `ActiveState`, while the enum stays the type in PHP.

## The Extbase `FileReference` trap

Verified on both trees and identical on both — `.Build/vendor/` carries
whichever version the last `composerUpdate -t 13|14` installed, and
`core-13/vendor/` and `core-14/vendor/` are pinned by their `composer.lock`:
`typo3/cms-extbase/Classes/Domain/Model/FileReference.php` is 52 lines and
declares **two** public methods:

```bash
grep -n "public function" \
  .Build/vendor/typo3/cms-extbase/Classes/Domain/Model/FileReference.php
```

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

`academic-persons-edit/Resources/Private/Partials/Profile/Image/Card.html` lines
56, 58, 61 and 63 is the only place in the repository that accesses file
reference metadata, and it is the only place using `originalResource`.
Everywhere else the Extbase `FileReference` is passed straight to `<f:image>` or
`<f:uri.image>`, which resolves it internally — no property access, no trap.

One further step down: on the **core** `FileReference`, `getProperty()` throws
`\InvalidArgumentException` (code 1314226805) when the property is missing —
verified in
`.Build/vendor/typo3/cms-core/Classes/Resource/FileReference.php`. For an
optional field use `getProperties()` and index into it, or guard with
`hasProperty()`. Do not call `getProperty()` on a field that may not be set.
No line numbers here: that file is core's and its line numbers differ between
the two supported versions, so a reader has to grep for the method anyway.

## Traits

A trait **may** share a method between classes that have nothing else in
common. It **must not** rely on the class it is used in: no property of that
class and no method of it. Everything the method needs is a parameter:

- **With a native type**, the union included, as the view is typed
  `FluidViewInterface|CoreViewInterface`.
- **An array parameter carries its shape** in the docblock, at least its key
  and value types, such as `array<string, mixed>` or `string[]`, so PHPStan
  needs no baseline entry for it.
- **`$this` is handed on as a value at most.** Passing the calling object to a
  callee that asks for one is fine; reading from it is not.

A trait written that way works in every class that uses it, and nothing in the
class depends on which of its members the trait happens to read.
`DispatchModifyPluginViewEventMethodTrait` is the example: an action hands it
its plugin action context, the view and the event dispatcher, although the view
and the dispatcher are properties of every Extbase controller.

The four traits under `packages/fgtclb/*/Classes/` follow the rule:

| Trait                                              | Takes                                                                                                                                                               |
|----------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `DispatchModifyPluginViewEventMethodTrait`         | the plugin action context, the view and the event dispatcher                                                                                                        |
| `GetCurrentContentRecordMethodTrait`               | the content object renderer                                                                                                                                         |
| `GetSelectItemsForTcaManagedTableFieldMethodTrait` | the request, the localization utility, the extension key, the table, the field and the values to drop, and hands `$this` to the item provider as the calling object |
| `TtContentListTypeColumnTrait`                     | the connection pool                                                                                                                                                 |

The seventeen traits of `packages-dev/testing-helper/` are the exception. They
are used only in test cases, sixteen of them only in functional ones, so they
call `$this->get()`, the assertions and the other helpers of the test case, and
two declare properties: one keeps a backup of the TCA, one a fixed map of
retired core labels. See [Testing helper](../testing/testing-helper.md).

## Strict types

302 of the 305 files declare `strict_types=1` (99 %) — here counted over
`packages/fgtclb/` only. New files must. Measured with
`find packages/fgtclb/*/Classes -name '*.php' | wc -l` against
`grep -rl 'declare(strict_types=1)' --include='*.php' packages/fgtclb/*/Classes | wc -l`;
`packages-dev/` and `Tests/` are not counted. The 3 that do not are worth
knowing so they are fixed rather than copied:

| File                                                                 |
|----------------------------------------------------------------------|
| `academic-partners/Classes/DataProcessing/PartnershipProcessor.php`  |
| `academic-partners/Classes/DataProcessing/PartnerProcessor.php`      |
| `academic-projects/Classes/ViewHelpers/Format/ReplaceViewHelper.php` |

Two of the three are `DataProcessing/` classes, which suggests one origin
rather than independent omissions. Two more were among them until they
gained a constructor and were fixed on the way:
`academic-contact4pages/Classes/DataProcessing/ContactsProcessor.php` (ACE-101)
and `academic-programs/Classes/DataProcessing/ProgramDataProcessor.php`, when it
started to build the program facts. And two more went with the selected
profiles and selected contracts events of `academic_persons`, which 3.0
removed in favour of the plugin view event.

## Extension points

The public API of every extension is listed on one page of the rendered manual:
[`academic-base/Documentation/Developers/ExtensionPoints/Index.rst`](../../packages/fgtclb/academic-base/Documentation/Developers/ExtensionPoints/Index.rst).
It names the events and the types they hand to a listener, the interfaces, the
services and classes a project names in its configuration or injects, the base
class of a profile factory, the two controller traits of `academic_base` and the
domain models, and next to the PHP the templates, settings, TypoScript and
TSconfig keys, label keys and `CategoryTypes.yaml`. The page is a whitelist: a
class it does not list is not API, whether it is `final` or not. It is written
for integrators, who read the manual rather than this repository.

- **`@api` and the page name the same classes.** Every class, interface,
  trait and enum the page lists carries `@api` in its docblock, and nothing
  else does. A class added to the page gets the tag in the same change, and
  the other way round. A change that breaks or deprecates anything of a tagged
  class needs a `Breaking-` or a `Deprecation-` changelog entry, which is what
  the tag is for: it sits where the change is made. A class carries `@api` or
  `@internal`, never both.
- **The page names a class that is not API without its namespace**, as
  `PartnerController`, never as `\FGTCLB\AcademicPartners\Controller\PartnerController`.
  The test below reads every namespaced `:php:` name on the page as a claim of
  API.
- **Domain models are API**, and a project extends one by registering a
  subclass as its XCLASS: Extbase creates models through
  `GeneralUtility::getClassName()`. `academic:upgrade:check` reports that
  XCLASS as a notice rather than a warning, see
  [Upgrade checks](upgrade-checks.md). Repositories are not API.
- **Every plugin controller is `final`, and none is API.** A project extends a
  plugin through the events the
  [extension points page](../../packages/fgtclb/academic-base/Documentation/Developers/ExtensionPoints/Index.rst)
  lists, never through a subclass or an XCLASS of its controller, and a new
  controller is `final` from the start (ACE-803).
- **Every plugin action that renders a view dispatches the plugin view
  event**, `ModifyPluginViewEvent` of `academic_base`, through the trait
  method `dispatchModifyPluginViewEvent()`, once on every path that renders.
  The action hands it its plugin action context, the view and the event
  dispatcher, see [Traits](#traits).
  A new action calls it too, and gets a row in the test that renders every
  plugin; see [Plugin view event](plugin-view-event.md). No plugin gets a view
  event of its own. The profile editing of `academic_persons_edit` is the one
  exception: it dispatches no plugin view event, and offers each of its writes
  to `BeforeProfileEditingWriteEvent` instead.
- **An action builds its plugin action context once**, before its first event
  and after its settings are settled, and hands that one object to every event
  it dispatches, the plugin view event included (ACE-767).

Events follow one shape:

- **`final`**, in `Classes/Event/`.
- **Named `Modify…Event`** when a listener may change something,
  **`Before…Event`** when a listener may refuse something that is about to
  happen, and change it as well, and **`After…Event`** when the event
  announces something that happened. `BeforeProfileEditingWriteEvent` of
  `academic_persons_edit` is dispatched before every write of the profile
  editor, and a refusal stops the write.
  `BeforeProfileMappedFromFrontendUserEvent` of `academic_persons` is
  dispatched before the frontend user synchronisation maps the data of a
  frontend user, and a skip stops the creation or the update of the profile.
  Three events predate the rule and stay, because a rename or a move breaks
  every listener for nothing: `ChooseProfileFactoryEvent` of
  `academic_persons`, its `@internal`
  `Service/Event/ModifyProfileCommandEnvironmentStateBuildContextForFrontendUserEvent`,
  and `AfterSaveJobEvent` of `academic_jobs`, which lets a listener change the
  redirect and the confirmation message that follow the save.
- **A setter only for what a listener may change**; everything else is a
  getter over a `readonly` property.
- **An event a plugin action dispatches carries the plugin action context of
  `academic_base`**, `PluginControllerActionContextInterface`, through
  `getPluginControllerActionContext()`. The copy in `academic_persons` is
  deprecated and goes in 4.0 (ACE-442, ACE-747); a new event never declares
  it.
- **Dispatched, tested and listed.** An event is listed on the page with the
  place that dispatches it and what a listener may change, both checked in the
  source, and a test proves that a listener's change arrives. A documented
  event that is never dispatched is the defect ACE-445 describes.

[`ExtensionPointTest`](../testing/unit-tests.md#the-extension-points) in
`packages-dev/monorepo-shared` holds what a test can: every event class is
`final` and created by production code, every domain model carries `@api`,
every plugin controller is `final`, and the page and the tags name the same
classes.

## Static analysis

PHPStan runs at **level 8** in both `Build/phpstan/Core13/phpstan.neon` and
`Build/phpstan/Core14/phpstan.neon` (line 13 of each). Level 8 is what makes the
nullability of a property meaningful, so a `?Foo $foo = null` that is really
always set has to be justified rather than assumed.

Note that `paths` is `../../../packages` only: **`packages-dev/` is not analysed
by PHPStan**. All three packages there, including the functional test base
class and traits in `packages-dev/testing-helper/`, are outside the gate. Keep
that in mind when changing them. Lint and the tests themselves are the only
checks they get.

## See also

- [Dependency injection](dependency-injection.md)
- [Core version aware code](core-version-aware-code.md)
- `AGENTS.md` — repository conventions and database query rules
