## 1. Verify the premises

- [ ] 1.1 After `composerUpdate` for each core version, re-read the core facts
  `design.md` (Context) relies on and confirm them on that core:
  `AbstractServiceProvider::configureIcons()`,
  `ServiceProvider::configureIconRegistry()`, `IconRegistry::registerIcon()`
  and `detectIconProvider()`, the provider resolution in
  `IconFactory::getIcon()`, `Icon::render()`/`wrappedIcon()` and
  `core:icon` byte identical between the two cores (`diff`),
  `PackageDependentCacheIdentifier`, `CacheWarmupEvent::hasGroup()`, and
  `cms-core/Resources/Public/Icons/T3Icons/svgs/default/default-not-found.svg`
  present. If one does not hold, stop and update this change with
  `/opsx:update`.
- [ ] 1.2 In a throwaway functional test, render an icon of the
  `currentColor` provider with the `inline` markup through a provider created
  with `new`, once per core version. Confirm that v14 fails on an
  uninitialised property and v13 renders. This is the premise of the
  container lookup, and the red proof of task 3.4 depends on it.
- [ ] 1.3 Confirm with `grep` that every template declaring
  `http://typo3.org/ns/FGTCLB/AcademicBase/ViewHelpers` uses the prefix `p`,
  and that no academic extension registers a global Fluid namespace.

## 2. Fixture extensions

- [ ] 2.1 Add `academic-base/Tests/Functional/Fixtures/Extensions/test_frontend_icons`
  (`tests/frontend-icons`) as `docs/testing/fixture-extensions.md` ("Adding
  one") describes. Its `Configuration/FrontendIcons.php` registers a
  `currentColor` SVG, a core `SvgIconProvider` SVG, a bitmap, an entry with
  only an `.svg` source, an entry with neither provider nor source, an entry
  naming a missing file, the scripted SVG of `test_current_color_icons` (a
  copy), `test-frontend-replaced`, and `test-frontend-both`, which its
  `Configuration/Icons.php` registers identically, next to the backend only
  `test-backend-only`. A listener class with TYPO3's `#[AsEventListener]`
  contributes `test-frontend-contributed` and
  `test-frontend-contributed-overridden`, the latter also in the file, wired
  by a `Configuration/Services.yaml` of the fixture. Fluid
  templates for the ViewHelper tests, declaring the prefix `ab`. Verify with
  `composerUpdate` that the package is found on both core versions.
- [ ] 2.2 Add `test_frontend_icons_override` (`tests/frontend-icons-override`),
  depending on `tests/frontend-icons` so its loading order is fixed, with a
  `Configuration/FrontendIcons.php` that replaces `test-frontend-replaced` and
  `default-not-found` with files of its own. Verify it loads after the first
  in `PackageManager::getActivePackages()` on both core versions.

## 3. Tests first

Every test below is written before the classes exist and runs red on both
core versions for that reason alone. That is not the proof. Each task names
the mutation that shows the test can fail once the implementation is green,
and the proof is run on both core versions unless the task says otherwise.

- [ ] 3.1 Unit `academic-base/Tests/Unit/Imaging/FrontendIconRegistryTest.php`,
  modelled on `SettingsFileLoaderTest` (a `PhpFrontend` double, a
  `PackageManager` double over fixture package directories, an event
  dispatcher double), one mutation per case as its red proof:
  - a later package replaces an identifier wholesale (red: reverse the merge
    order)
  - a file entry beats a contributed one in either package order (red: merge
    the event after the files)
  - a missing provider is detected for `.svg` and for a bitmap (red: drop the
    detection)
  - an entry with neither, a non-array entry, a non-array file and a package
    without the file are skipped (red: drop each guard)
  - a non-provider class throws naming the identifier (red: drop the
    validation)
  - a cached entry is returned without reading a file or dispatching the
    event (red: build on every call)
  - a missing entry is written as `return …;` under
    `AcademicFrontendIcons_<hash>`, and the key differs for two package
    manager cache identifiers (red: a fixed key)
- [ ] 3.2 Unit `academic-base/Tests/Unit/Event/CollectFrontendIconsEventTest.php`:
  a non-provider class is rejected naming the identifier, a later `addIcon()`
  of the same identifier wins, `getIcons()` returns the file format. Red:
  remove the validation, keep the first registration.
- [ ] 3.3 Functional `academic-base/Tests/Functional/Imaging/FrontendIconRegistryTest.php`
  against `tests/frontend-icons`: the contributed icon is registered, the file
  entry beats the contributed one, `test-backend-only` is unknown, and every
  identifier of the fixture's `FrontendIcons.php` except `test-frontend-both`
  is unknown to the core `IconRegistry`. Red: remove the event dispatch, merge
  the core registry's icons in.
- [ ] 3.4 Functional `academic-base/Tests/Functional/Imaging/FrontendIconFactoryTest.php`:
  - each fixture provider renders its default and `inline` markup equal to
    what the core `IconFactory` renders for the same file and provider, the
    scripted file included, so the frontend path sanitises exactly as the
    backend path. Red, v14 only: create the provider with `new`, which fails
    the inline renders on v14 and stays green on v13 because the v13 pipeline
    needs no injected service (task 1.2).
  - the missing file of the `currentColor` entry renders empty markup (red:
    let the factory skip the provider for it)
  - `spinning` and `bidi` reach the classes (red: drop them)
  - two calls return different objects (red: return the cached instance)
- [ ] 3.5 Functional `academic-base/Tests/Functional/ViewHelpers/IconViewHelperTest.php`,
  rendering fixture templates like `CategoryTypeTitleViewHelperTest`:
  - `test-frontend-both` through the new ViewHelper and `core:icon` is the
    same string for the defaults, `size="medium"`, `state="disabled"`,
    `alternativeMarkupIdentifier="inline"`, a `title` and an `overlay`, each
    case in its own template so the title of `core:icon` cannot carry over.
    Red: override `wrappedIcon()` in `FrontendIcon` with one class less, and
    change the default of `size` to `default`.
  - one literal assertion of the wrapper classes and attributes of the
    default case (red: the same `wrappedIcon()` mutation)
  - `test-backend-only` renders the placeholder,
    `data-identifier="default-not-found"`, and not `test-backend-only` (red:
    render through the core `IconFactory`)
  - an unknown identifier and an unknown overlay render the placeholder with
    non-empty markup (red: drop the substitution)
  - the same icon rendered with a title and then without carries the title
    once (red: return the runtime cached instance instead of a clone)
  - an invalid `size` raises `\ValueError` as `core:icon` does (red: fall
    back to `small` with `IconSize::tryFrom()`)
- [ ] 3.6 Functional `academic-base/Tests/Functional/ViewHelpers/IconViewHelperOverrideTest.php`
  with both fixtures: `test-frontend-replaced` and an unknown identifier render
  the files of `tests/frontend-icons-override`. Red: merge the packages in
  reverse order.
- [ ] 3.7 Functional `academic-base/Tests/Functional/EventListener/WarmUpFrontendIconRegistryTest.php`:
  dispatching `CacheWarmupEvent` with `system` through the container's
  dispatcher writes the entry under the package dependent key in `cache.core`,
  with `pages` only it does not. Red: drop the `hasGroup()` condition, drop
  the attribute.

## 4. Implementation

- [ ] 4.1 `Classes/Event/CollectFrontendIconsEvent.php` as `design.md`
  describes, `final` and `@api`. Verify with task 3.2.
- [ ] 4.2 `Classes/Imaging/FrontendIconRegistry.php`, `final readonly`,
  `@internal`, `#[Autoconfigure(public: true)]`, `#[Autowire(service:
  'cache.core')]`, a new exception code. Verify with tasks 3.1 and 3.3.
- [ ] 4.3 `Classes/EventListener/WarmUpFrontendIconRegistry.php` with TYPO3's
  `#[AsEventListener]`, never Symfony's. Verify with task 3.7.
- [ ] 4.4 `Classes/Imaging/FrontendIcon.php` and
  `Classes/Imaging/FrontendIconFactory.php` (container lookup,
  `#[Autowire(service: 'cache.runtime')]`, clone per call, a new exception
  code for a missing placeholder). Verify with task 3.4.
- [ ] 4.5 `Classes/ViewHelpers/IconViewHelper.php`, `final`, `@internal`,
  the six arguments of `core:icon`. Verify with tasks 3.5 and 3.6.
- [ ] 4.6 `Configuration/FrontendIcons.php` of `academic_base` with the
  `default-not-found` entry only. Verify with task 3.5.
- [ ] 4.7 Call `get_file_problems` on every new PHP file, then run the whole
  `academic-base` unit and functional tests on both core versions and confirm
  every test of section 3 is green and every red proof was run.

## 5. Testing helper trait

- [ ] 5.1 Add `FrontendIconsAssertionTrait` with the five methods of
  `design.md` to `packages-dev/testing-helper/Classes/FunctionalTestCase/` and
  use it in the tests of section 3. Show each method can fail by pointing it
  at a fixture icon that violates it (a backend only icon, a core provider
  icon, the scripted file), on both core versions.
- [ ] 5.2 Add its section to `docs/testing/testing-helper.md`, and raise the
  trait count from fourteen to fifteen in `AGENTS.md`,
  `docs/testing/testing-helper.md`, `docs/development/monorepo-layout.md`,
  `docs/architecture/class-design.md` and `docs/workflow/backporting.md`, the
  last with a row that is `main` only. Verify with `grep -rn -i fourteen`.

## 6. Extension points

- [ ] 6.1 On `academic-base/Documentation/Developers/ExtensionPoints/Index.rst`:
  - a row for `CollectFrontendIconsEvent` in the events table
  - a bullet in "What is public API" for the format of
    `Configuration/FrontendIcons.php`, with the rule that a later package wins
  - a bullet for the view helper `icon` of `academic_base` by namespace, tag
    name and arguments, with the placeholder identifier `default-not-found`,
    its class not public API
  - the `CurrentColorSvgIconProvider` row naming
    `Configuration/FrontendIcons.php` as well
- [ ] 6.2 Run the unit tests of `packages-dev/monorepo-shared` on both core
  versions. Show `ExtensionPointTest` catches the new class: remove its row
  (red in `thePageListsExactlyTheClassesTaggedAsApi()`), and replace the
  `new` in the registry by a factory method (red in
  `eventClassIsCreatedByProductionCode()`), then restore.

## 7. Documentation

- [ ] 7.1 Rework `docs/architecture/icons.md` and verify with
  `lintMarkdown -n`:
  - the intro and "Registration today" name both registries and count both
    files
  - a new section on the frontend registry: discovery and merge order, the
    event and its precedence, cache, key, warmup and invalidation, provider
    resolution through the container, the inherited wrapper and why
    `FrontendIcon` exists, the placeholder and the absence of a fallback, the
    namespace prefix rule, and that no extension uses it yet
  - "Keeping a template's icons resolvable" says the rule holds for both
    ViewHelpers
  - "See also" links the testing helper page
- [ ] 7.2 Add the registry split as a principle to
  `docs/architecture/Index.md` and update the icons row of `docs/Index.md`.
- [ ] 7.3 Re-measure with the commands the pages document and update:
  the `#[AsEventListener]` and `#[Autowire]`/`#[Autoconfigure]` counts and the
  listener class list of `docs/architecture/dependency-injection.md`, the
  sentence on `Core\Attribute\AsEventListener` in `AGENTS.md`, the
  `final readonly class` count of `docs/architecture/class-design.md`, and
  the fixture count and rows of `docs/testing/fixture-extensions.md`. Confirm
  `docs/architecture/core-version-aware-code.md` needs nothing, because no
  version switch is added.
- [ ] 7.4 In `academic-base/Documentation/Configuration/Index.rst`, a section
  on frontend icons: the file and its format, a later package wins, an icon
  shown in both contexts is registered in both files, the event for code, the
  ViewHelper with its namespace and arguments, the placeholder and how to
  replace it, and the `system` flush after an edit. "Opting in" of the
  provider names both files. Verify with `checkRstRenderingAll`.
- [ ] 7.5 `academic-base/Documentation/Changelog/3.0/Feature-FrontendIconRegistry.rst`
  from `Build/Documentation/Templates/Changelog-Feature.rst` in the house
  style of `docs/workflow/changelog-and-documentation.md`, saying that no
  extension uses the registry yet. Check the title adornment length with the
  `awk` command of that page.
- [ ] 7.6 Confirm that no unreleased 3.0 entry is contradicted: the
  `*IconsFollowTheColourScheme.rst` entries and the persons upgrade guide
  promise the `core:icon` wrapper, which this change keeps. Amend nothing
  unless one is.

## 8. Measure

- [ ] 8.1 Render a page with many repeated icons (the profile editing
  fixture of `academic-persons-edit`, or a fixture template with 50 icons) on
  both core versions with `core:icon` and with the new ViewHelper, and record
  the timing in the pull request. If the new ViewHelper is clearly slower,
  update `design.md` before the follow-up changes start.

## 9. File the issue

- [ ] 9.1 File the ACE issue (Story, version 3.0.0, subtask of ACE-10,
  related to the umbrella issue of the frontend icons round and to ACE-595,
  whose server side this supersedes), verify the key, and rename the change
  to `ace-NNN-frontend-icon-registry`, keeping the slug.

## 10. Definition of done

- [ ] 10.1 After `composerUpdate` for TYPO3 v13: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` (`-j auto` on SQLite, `-j 8` on
  PostgreSQL and MariaDB) green.
- [ ] 10.2 After `composerUpdate` for TYPO3 v14: the same gates green.
- [ ] 10.3 `lintMarkdown -n`, `checkRstRenderingAll` and
  `openspec validate ace-NNN-frontend-icon-registry --strict` green.
- [ ] 10.4 `docs/` is updated as in section 7, and `README.md` and
  `CONTRIBUTING.md` still only summarize.
- [ ] 10.5 The `Feature` changelog entry of `academic_base` exists, and
  nothing in this change moves an icon, switches a template or changes
  markup, as `design.md` states.
- [ ] 10.6 Commit as `[FEATURE] ACE-<NNN>: Add a frontend icon registry` in
  TYPO3 Core format with a verified key, and archive the change as the last
  commit of the pull request.
