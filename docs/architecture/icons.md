# Icons

What an icon of the academic extensions is called, which registry it belongs
in, where its file lives and where the file comes from, how it is registered
and rendered, and how the rules are checked. Where a count is quoted with a
command next to it, that count is the output of the command, run over the
repository at the commit that last touched this page. Re-run it rather than
adjusting the number by hand. The counts without a command were read off the
files named beside them.

## The rules in short

- Identifiers are `tx-<extension key without underscores>-<group>-<name>`,
  files `Resources/Public/Icons/<group>/<name>.svg`, unless the identifier
  draws a file it shares with another one.
- **The group decides the registry.** An `action`, `state` or `info` icon is
  a frontend icon: registered in `Configuration/FrontendIcons.php` only and
  rendered with `<ab:icon>`. A `record`, `plugin` or `doktype` icon is a
  backend icon: registered in `Configuration/Icons.php` only. Category type and
  group icons reach both registries through `typo3-category-types`.
- An icon that means the same in several extensions, an action, a state or the
  glyph in front of a piece of information, exists once, in the shared set of
  `academic_base`. Look there before adding one.
- Every icon is a **Font Awesome Free solid** icon in the house format. Never
  Font Awesome Pro, never another set.
- Every icon is registered with `CurrentColorSvgIconProvider`. A category type
  asks for it with `inlineIcon: true`. The one exception is the placeholder
  `default-not-found` of `academic_base`, core's drawing with core's provider.
- A renamed identifier is renamed, not aliased: 3.0 ships no deprecated
  identifiers, and each extension documents old and new identifiers in one
  `Breaking-*.rst` about its icons.
- An icon is `1em` by `1em`, everywhere. No stylesheet enlarges an icon box to
  make up for the glyph being smaller than its box.
- A content element names one identifier in its CType item, its
  `typeicon_classes` entry and its wizard entry.

## Identifiers

### The scheme

`tx-<extension key without underscores>-<group>-<name>`, everything lowercase,
the name in kebab case: `tx-academicbase-action-move-up`,
`tx-academicpersonsedit-plugin-profile-editing`. The groups:

| Group     | For                                                | Registry, file                | Lives in                                                 |
|-----------|----------------------------------------------------|-------------------------------|----------------------------------------------------------|
| `action`  | something a control does: add, edit, move up, save | frontend, `FrontendIcons.php` | `academic_base`                                          |
| `state`   | a state a control shows: visible, hidden           | frontend, `FrontendIcons.php` | `academic_base`                                          |
| `info`    | the glyph in front of a piece of information       | frontend, `FrontendIcons.php` | `academic_base`, or the extension for a value of its own |
| `record`  | the icon of a TCA record type (`typeicon_classes`) | backend, `Icons.php`          | the extension of the table                               |
| `plugin`  | a content element: the CType and its wizard entry  | backend, `Icons.php`          | the extension of the plugin                              |
| `doktype` | a page type                                        | backend, `Icons.php`          | the extension of the page type                           |

**Why the group decides the registry.** The two registries do not read each
other, see [The frontend icon registry](#the-frontend-icon-registry). An icon
registered in both is two registrations a site has to replace twice, and one
registered in the wrong one renders the `default-not-found` placeholder where
it is shown. The group already says where an icon is shown: an action, a state
and an information glyph are shown by a frontend template, a record, content
element or page type icon by the backend. So the group is the decision, and the
checks fail an identifier of one group in the file of the other. An info icon
an extension needs for a value of its own lives in that extension:
`tx-academicprograms-info-credit-points`, and the per-property icons of
`academic-jobs`, which draw files of the shared set under identifiers of the
extension, so a site package can replace the icon of one job property.

**Why this shape.** Every part of it answers a property of both registries on
both core versions:

- **A duplicate registration wins silently.** Every package's
  `Configuration/Icons.php` is merged into one array with `array_merge()` in
  `AbstractServiceProvider::configureIcons()`, in the order of the active
  packages, and `IconRegistry::registerIcon()` assigns without looking. The
  frontend registry merges `Configuration/FrontendIcons.php` the same way.
  Nothing throws and nothing is logged, and an `Icons.php` entry overrides even
  a core identifier. The extension key is the only token that is unique across
  an installation, so it is in every identifier.
- **The key is written without underscores**, the way core derives the
  `tx_<key>` prefix of table names. Written dashed it would be ambiguous in this
  family: `academic_persons` with `edit-print` and `academic_persons_edit` with
  `print` would both read `academic-persons-edit-print`. Without underscores
  the key is exactly the second segment.
- **`tx-` keeps us out of core's namespace.** Core names its icons
  `<category>-<name>`, `actions-`, `content-`, `apps-` and so on, and adds new
  ones with every release, so an `actions-*` name that is free today may be
  taken tomorrow. `tx-` is not a core category.
- **The identifier becomes markup.** `Icon::wrappedIcon()` emits it as the CSS
  class `icon-<identifier>` and as `data-identifier`, for `<core:icon>` and
  `<ab:icon>` alike. `[a-z0-9-]` gives a class a selector can name without
  escaping, and the group keeps an `info-contract` apart from a
  `record-contract` of the same name.

### Category type identifiers

`category_types.<group>.<type>` and `category_types_group.<group>` are derived
by `typo3-category-types` from the `Configuration/CategoryTypes.yaml` of
whichever extension declares the type or group, and registered in both
registries, see [Registration today](#registration-today). They stay as they
are. What the scheme governs there is the file the `icon:` key points at,
`Icons/category-type/<name>.svg` or `Icons/category-group/<name>.svg`, or a
file of the shared set.

### Renames

The consolidation renamed every identifier of the extensions apart from the
category type and group identifiers and
`tx-academicprograms-info-credit-points`, and registers none of the old ones
under its old name. The backend registry could: an `Icons.php` entry takes a
`deprecated` key (Feature #98130, since 12.0), and rendering such an
identifier raises `E_USER_DEPRECATED`. The frontend registry
has no such key. It was left out on purpose, because it does not carry over
what matters. A template of ours emits the new identifier either way, so a
project's `.icon-<old>` selector and its replacement of an old identifier stop
working at the same moment, deprecation or not, and 3.0 is the release that
introduced the frontend registry anyway. What an integrator has to change is in
the `Breaking-*.rst` changelog entry about the icons of each extension, one per
extension.

## Files

### Layout

`Resources/Public/Icons/<group>/<name>.svg`, lowercase kebab case. The groups
of the identifiers are directories, and so are `category-type` and
`category-group` for the files a `CategoryTypes.yaml` names. Next to them:

- `Extension.svg`, the icon of the extension manager and the TER, read as a
  file and never registered. Every extension keeps its own.
- `LICENSE-font-awesome.txt`, the attribution notice, see [Licence](#licence).
- `BackendLayout.png` (partners, programs, projects), the preview of a backend
  layout, not an icon.

Several identifiers may draw one file, and that includes a file of another
extension as long as it is a file of `academic_base`: every package in
`packages/fgtclb/` requires `fgtclb/academic-base`, so an
`EXT:academic_base/Resources/Public/Icons/…` source always resolves. A record
icon that is the same glyph as a shared info icon draws the shared file rather
than copying it. A file of any other extension would be a dependency nobody
declared, and the checks fail it.

### The shared set

`academic_base` ships 38 icons in `Configuration/FrontendIcons.php`: 17
actions, 2 states and 19 information glyphs, the files in
`Resources/Public/Icons/{action,state,info}/`. The
[`academic_base` manual](../../packages/fgtclb/academic-base/Documentation/Icons/Index.rst)
lists every one with its meaning and its Font Awesome name.

```bash
grep -oE "'tx-academicbase-[a-z]+-" packages/fgtclb/academic-base/Configuration/FrontendIcons.php \
  | sort | uniq -c
```

The shared set is the default. An action, a state or an information glyph goes
there, even when only one extension uses it today, because the second one will,
and a copy in each extension is how the icons drifted apart before 3.0. What
only makes sense for one extension, its record types, its plugins, its page
types and its category types, belongs to that extension. A site package that
replaces a shared icon in its own `Configuration/FrontendIcons.php` replaces it
in every extension that renders it.

The set registers every glyph it ships, also where no template of ours renders
the identifier. Templates render five of the nineteen `info` identifiers under
their own name: `-info-email`, `-info-phone`, `-info-location`, `-info-room`
and `-info-time`. The other fourteen stay registered on purpose, as part of the
set a project can render and replace. The job views render identifiers of
`academic_jobs` that draw the same files, and several files are also the
drawings of record and category type icons, which other extensions register
under identifiers of their own. A replacement of one of the fourteen therefore
reaches neither the job views nor those record and category type icons.

`expand` and `add` are the same drawing in two files, so a project can replace
one without the other.

### Font Awesome Free solid, and nothing else

Every icon comes from `@fortawesome/fontawesome-free`, the **solid** style of
the fixed width set `svgs-full/solid/`. The shipped files are from version
7.3.1. One source, one style and one grid keep a row of icons in one visual
language: regular exists in Free for a small subset only, and mixing it with
solid, or an outline set with a filled one, is what the icons looked like
before 3.0. The fixed width grid (`viewBox="0 0 640 640"` for every icon) keeps
icons of different natural widths at one scale, where a natural width viewBox
would draw a narrow glyph larger than a wide one in the same square box.

Font Awesome **Pro** is never used: it is a commercial licence and does not
cover shipping its files in a package anyone can download. Neither is a brand
icon of the Free set, which is a trademark of its owner and only to be used
for the brand it represents.

### The house format

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" width="1em" height="1em" fill="currentColor"><!--! Font Awesome Free 7.3.1 by @fontawesome … --><path d="…"/></svg>
```

- `viewBox="0 0 640 640"`, as shipped in `svgs-full/`.
- `width="1em" height="1em"`, so the icon follows the font size on a frontend
  page, which has no stylesheet sizing `.icon svg`, see
  [What the file has to look like](#what-the-file-has-to-look-like).
- `fill="currentColor"` on the root element, so the icon takes the colour of
  the text around it. The paths carry `d` only.
- No `id`, no `class`, no `style`, no `<style>`: the markup is inlined, maybe
  many times in one document, where an `id` must be unique and a style rule is
  global.
- Font Awesome's attribution comment stays in the file. It never reaches the
  page, the sanitiser of the provider removes it, but it keeps the attribution
  attached to a file copied out of the package, and Font Awesome asks for it.

### Adding an icon

1. Look for it in the shared set first.
2. Pick the icon on fontawesome.com (Free, solid) and take its file from the
   package, which is on npm:
   <https://registry.npmjs.org/@fortawesome/fontawesome-free/-/fontawesome-free-7.3.1.tgz>,
   `package/svgs-full/solid/<name>.svg`. Use the canonical name, not one of
   the aliases of older versions that the package ships as duplicate files.
3. Normalise it into the house format. Run from the unpacked tarball, this
   produces the files of the shared set byte for byte:

   ```bash
   { sed -e 's#^<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">#<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" width="1em" height="1em" fill="currentColor">#' \
         -e 's#<path fill="currentColor" #<path #g' \
         package/svgs-full/solid/plus.svg; echo; } \
     > packages/fgtclb/academic-base/Resources/Public/Icons/action/add.svg
   ```

4. List the file with its Font Awesome name in the extension's
   `Resources/Public/Icons/LICENSE-font-awesome.txt`, creating the notice from
   the one in `academic_base` if the extension has none yet.
5. Register it in the file its group names, see [The scheme](#the-scheme), with
   `CurrentColorSvgIconProvider`, and add the identifier to the extension's icon
   test.

### Licence

Font Awesome Free icons are licensed CC BY 4.0, which requires attribution
wherever the icons are shared: creator, copyright notice, licence and its URI
with its disclaimer of warranties, a link to the material, and an indication of
what was modified. The comment in each file does not do that on a rendered
page, because the sanitiser removes it. Every extension that ships Font Awesome
files therefore carries `Resources/Public/Icons/LICENSE-font-awesome.txt`:
version, creator, copyright, licence URI, the modifications (the `fill` moved to
the root element, `width` and `height` added, the file renamed) and every file
it covers with its Font Awesome name. It is one notice per extension rather
than one for the repository, because every package is split out into a
repository of its own and released on its own, and a notice at the root of this
repository would ship with none of them. An extension that only draws files of
`academic_base` ships no Font Awesome file and needs no notice.

Every extension that ships Font Awesome files names them in a section
"Third-party icons" of its manual, in the same wording, and its `README.md`
points at the notice in its licence section. The manual is where an integrator
looks, the README is what the split repository shows.

## Registration today

There are two registries. The icon registry of TYPO3 reads
`Configuration/Icons.php` and serves the backend, and a template override that
still renders `<core:icon>` in the frontend. The frontend icon registry of
`academic_base` reads `Configuration/FrontendIcons.php` and serves `<ab:icon>`,
see [The frontend icon registry](#the-frontend-icon-registry). Neither reads the
other.

```bash
grep -c "'provider'" packages/fgtclb/*/Configuration/Icons.php
grep -rh "'provider' =>" packages/fgtclb/*/Configuration/Icons.php \
  | sed "s/.*=> *//" | sort | uniq -c
grep -c "'provider' => CurrentColorSvgIconProvider" \
  packages/fgtclb/*/Configuration/Icons.php
```

Eight of the twelve extension packages ship a `Configuration/Icons.php`, with **26
registrations in total: 7 with the core `SvgIconProvider` and 19 with
`CurrentColorSvgIconProvider`**:

| Package                  | Registrations | `CurrentColorSvgIconProvider` |
|--------------------------|---------------|-------------------------------|
| `academic-jobs`          | 2             | 1                             |
| `academic-persons`       | 10            | 9                             |
| `academic-persons-edit`  | 1             | –                             |
| `academic-study-plan`    | 4             | 3                             |
| `academic-contact4pages` | 4             | 2                             |
| `academic-partners`      | 3             | 3                             |
| `academic-bite-jobs`     | 1             | –                             |
| `academic-programs`      | 1             | 1                             |

The 19 are **record icons**: every identifier a TCA record type resolves
through `ctrl.typeicon_classes`, including the two page type icons
`academic-partners` and `academic-programs` (ACE-523).

`academic-base`, `academic-projects`, `academic-persons-sync` and the three
`packages-dev/` packages register nothing there.

```bash
ls packages/fgtclb/*/Configuration/FrontendIcons.php
grep -c "'provider'" packages/fgtclb/*/Configuration/FrontendIcons.php
```

Six packages ship a `Configuration/FrontendIcons.php`, with **83
registrations**. `academic-base` registers 39: the placeholder
`default-not-found` with the core `SvgIconProvider`, and the 38 icons of
[the shared set](#the-shared-set) with `CurrentColorSvgIconProvider`. The other
44 are frontend icons of five extensions, and their templates render them
through `<ab:icon>`. No backend view renders them, so none of them is
registered in `Icons.php`:

- The 23 **control icons** of `academic-persons` and `academic-persons-edit`
  (ACE-812), all Bootstrap Icons with `CurrentColorSvgIconProvider`: the seven
  of the public profile and the sixteen of the profile editing view.
- The 17 `academic_jobs-*` icons of the job views and the three controls of
  `academic-study-plan` (ACE-813), with the core `SvgIconProvider`, see below.
- The icon of a value that is no record,
  `tx-academicprograms-info-credit-points`, the credit points fact of a program
  (ACE-814), with `CurrentColorSvgIconProvider`. It is the first Font Awesome
  Free 7 drawing, the first with a `LICENSE-font-awesome.txt` next to it and
  the first identifier in the [scheme](#the-scheme). Eight record
  icons of `academic-jobs` and `academic-persons` carry Font Awesome Free 6.4.2
  attributions.

`typo3-category-types` contributes every category type and group icon from
code, see below.

One registration is programmatic: `typo3-category-types` registers
`category_types.<group>.<type>` per configured category type on
`BootCompletedEvent`
([`Classes/ServiceProvider.php`](../../packages/fgtclb/typo3-category-types/Classes/ServiceProvider.php),
`addIcons()`). The provider comes from
[`Imaging/CategoryTypeIconProviderResolver`](../../packages/fgtclb/typo3-category-types/Classes/Imaging/CategoryTypeIconProviderResolver.php),
which repeats the rule of `IconRegistry::detectIconProvider()`: bitmap versus
SVG by file extension and nothing else. Those icons are `sys_category`
record icons, so the same rule applies to them as to the rest — but the set of
them is whatever the *loaded* extensions declare in their
`Configuration/CategoryTypes.yaml`, site packages this repository never sees
included, and inlining a file is not a decision the registrar may take for them:
an inlined SVG is part of the document, so its `id` attributes and its `<style>`
rules are global and collide with every other inlined icon on the page. The type
therefore asks for it, with `inlineIcon: true` next to its `icon:`, and only then
does the SVG get `CurrentColorSvgIconProvider` instead of the core one. A bitmap
keeps what core detected either way. Twenty icons ship with the flag set today,
from `academic-partners` (4), `academic-programs` (12) and `academic-projects`
(4) — every category type of this repository.

The same method registers `category_types_group.<group>` for every group
declared with an icon in the `groups:` section of that file (ACE-364), with the
same choice of provider. No backend view renders them, because the option
groups of the type select carry a label only, so they are icons for templates.
The three groups of this repository declare one each, drawn in `currentColor`
and with `inlineIcon: true`: `academic-partners`, `academic-programs` and
`academic-projects`, in `Resources/Public/Icons/CategoryGroups/`. The prefix
differs from the `category_types.` of the type icons in its fifteenth
character, so no group and type name can make the two identifiers equal, dots
included (ACE-811). The earlier `category_types.group.<group>` collided with
the type icons of a group named `group` and was never released.

The frontend icon registry receives the same identifiers from
[`EventListener/AddCategoryTypeFrontendIcons`](../../packages/fgtclb/typo3-category-types/Classes/EventListener/AddCategoryTypeFrontendIcons.php),
a listener on `CollectFrontendIconsEvent` (ACE-811). It adds one entry per type
and per group with the file the frontend shows, `frontendIcon` or else `icon`,
and the provider the same resolver answers for the frontend flag: a declared
`frontendInlineIcon`, or else `inlineIcon` while the frontend shows `icon`, and
an image for a `frontendIcon` nobody opted in for. A type or group without a
file contributes nothing, so the frontend renders its placeholder where the
backend renders an empty bitmap that throws 1440754980. The values come from the
models, `CategoryType::getFrontendIcon()` and `isFrontendInlineIcon()`, and the
loader resets the frontend flag only: a `useExisting` override or a later group
declaration that names a new `frontendIcon` without `frontendInlineIcon` shows
it as an image, while a new `icon` keeps the earlier `inlineIcon`, the rule of
ACE-523. Since the event entries come before the files, a
`Configuration/FrontendIcons.php` of a site package replaces a category type
icon in the frontend whatever the loading order, which `Icons.php` cannot do in
the backend. The frontend templates of partners, programs and projects render
these icons with `<ab:icon>` (ACE-814), the page module summary of
`typo3-category-types`, a backend view, with `<core:icon>`.

### Where the identifiers are consumed

The backend consumes identifiers through `typeicon_classes` in 21 files under
`packages/fgtclb/*/Configuration/TCA/` plus
[`academic-base/Classes/TcaManipulator.php`](../../packages/fgtclb/academic-base/Classes/TcaManipulator.php)
for select items, through the `icon` key of every content element registration
in `Configuration/TCA/Overrides/tt_content.php` — that key has to be a registered
identifier: `addPlugin()` and `TcaManipulator::addRecordType()` write it verbatim
into `ctrl.typeicon_classes`, `IconRegistry::registerTCAIcons()` registers
`ctrl.iconfile` and nothing else, and an unregistered value is silently replaced
by `default-not-found` — and through
`<core:icon>` in the page module category summary,
`category-types/Resources/Private/Templates/PageCategorySummary.html`, which
draws both the category type icon and the core `overlay-hidden` overlay.

The frontend consumes them through `<ab:icon>` only. Since ACE-814 no frontend
template of the extensions renders `<core:icon>`. A template override of a site
may still do so: `core` is a global Fluid namespace on both core versions,
through `SYS.fluid.namespaces` of `cms-core/Configuration/DefaultConfiguration.php`
on v13 and through `cms-core/Configuration/Fluid/Namespaces.php` on v14, where
the setting is empty by default since 14.1. Its ViewHelper therefore needs no
`xmlns` declaration, and it is byte identical on 13.4.34 and 14.3.6. It reads
the backend registry, so an override on it shows the placeholder for an icon
of `FrontendIcons.php`, and the backend drawing for a category type icon.

Which markup a template gets depends on one argument,
`alternativeMarkupIdentifier="inline"`. An `<ab:icon>` is regularly written
across several lines, so the argument has to be counted per tag rather than per
line:

```bash
grep -rl "<ab:icon" packages/fgtclb/*/Resources/Private --include=*.html \
  | xargs perl -0777 -ne 'while (/<ab:icon\b[^>]*>/gs) {
      print /alternativeMarkupIdentifier="inline"/ ? "inline\t$ARGV\n" : "default\t$ARGV\n" }' \
  | sort | uniq -c
```

| Extension               | `alternativeMarkupIdentifier="inline"` | Without (default markup)                                                    |
|-------------------------|----------------------------------------|-----------------------------------------------------------------------------|
| `academic-persons-edit` | 35 in 13 files                         | –                                                                           |
| `academic-persons`      | 7 in 2 files                           | –                                                                           |
| `academic-study-plan`   | 3 in 2 files                           | –                                                                           |
| `academic-jobs`         | –                                      | 4 in `Job/Item.html`, `Job/Information.html` and `Job/Contact.html`         |
| `academic-partners`     | –                                      | 4 in 4 files, `category_types.partners.*` only                              |
| `academic-programs`     | –                                      | 1 in `Program/Facts/Item.html`, `category_types.*` and credit points        |
| `academic-projects`     | –                                      | 2 in `Project/Page/Categories.html` and `Project/Item.html`                 |

They switched from `<core:icon>` with every argument kept, so the markup is the
one they had, and 32 of the 35 of `academic-persons-edit` still pass
`size="small"`, the default of the argument.

## The frontend icon registry

`academic_base` keeps the icons a visitor sees apart from the icon registry of
TYPO3. That registry is a backend service: building it loads about 790 core
icons, the TCA record icons and the flags, and its wrapper markup is styled by
`backend.css` only. Five classes and one listener in
[`academic-base/Classes/`](../../packages/fgtclb/academic-base/Classes), the
same on 13.4.35 and 14.3.7 without a version switch:

| Class                                      | Role                                                                                   |
|--------------------------------------------|----------------------------------------------------------------------------------------|
| `Imaging\FrontendIconRegistry`             | Builds, caches, answers and lists the registry. `@internal`, public for tests.         |
| `Imaging\FrontendIconFactory`              | Creates a prepared icon, like `IconFactory::getIcon()`. `@internal`, public for tests. |
| `Imaging\FrontendIcon`                     | Extends core's `Icon` and overrides nothing. `@internal`.                              |
| `Event\CollectFrontendIconsEvent`          | Lets code contribute icons while the registry is built. `@api`.                        |
| `ViewHelpers\IconViewHelper`               | `ab:icon`, the arguments of `core:icon`. `@internal`, the tag is API.                  |
| `EventListener\WarmUpFrontendIconRegistry` | Builds the registry on `cache:warmup`. `@internal`.                                    |

**Discovery and merge order.** The registry dispatches
`CollectFrontendIconsEvent` first, then requires
`Configuration/FrontendIcons.php` of every package of
`PackageManager::getActivePackages()`, in loading order, inside a static
closure, and `array_merge()`s each array result onto what it has. A later
package therefore replaces an identifier wholesale, as core's
`AbstractServiceProvider::configureIcons()` does for `Icons.php`, and a file
entry replaces a contributed icon whatever the order of the two packages. A
file that returns no array is ignored. The result is normalised the way
`ServiceProvider::configureIconRegistry()` normalises `Icons.php`: `provider`
is taken out of the options, a missing one is detected from `source` with the
suffix rule of `IconRegistry::detectIconProvider()` (copied, so the backend
registry is never built for it), an entry with neither is skipped, and a
provider that is not an `IconProviderInterface` throws
`\InvalidArgumentException` 1791061901 naming the identifier.
`CollectFrontendIconsEvent::addIcon()` throws 1791061902 for the same mistake.

**Cache, key and invalidation.** The normalised array is written to
`cache.core` as `return <var_export>;`, the shape core and
`SettingsFileLoader` write, under
`PackageDependentCacheIdentifier->withPrefix('AcademicFrontendIcons')`. That
class is `@internal` but byte identical on both cores and the key core uses for
its own `Icons_` entry: the TYPO3 version, the project path and the package
manager's cache identifier, so an activated or removed package reads another
entry without a flush. An edited file is read after a flush of group `system`.
`WarmUpFrontendIconRegistry` listens to `CacheWarmupEvent` and builds the entry
for group `system`, as `IconRegistry::warmupCaches()` does for the backend.
`cache:warmup` boots the whole container first, so the listeners of the collect
event are registered by then. The registry holds no state: each lookup is one
`require` of an OPcache'd file.

**Listing the registry.** `getAllRegisteredIconIdentifiers()` answers every
identifier in the order the registry was built, contributed icons first, then
the files in loading order, a replaced identifier at the place of its first
registration. It has the name of the method of core's `IconRegistry` that
answers the same question for the backend. The checks of the shared set and
the orphan check of the files use it, and it is the list a page showing every
frontend icon, or an API serving them to a script, reads instead of a list of
its own.

**Providers come from the container.** `FrontendIconFactory` resolves the
provider as core does, `$container->has($p) ? $container->get($p) :
GeneralUtility::makeInstance($p)`. On v14 that is not a detail: core tags
every `IconProviderInterface` as `icon.provider` and publishes it, so the
container calls the `inject*()` setters of `AbstractSvgIconProvider`. A
provider created with `new` fails its first inline render there with
"Typed property `AbstractSvgIconProvider::$svgDocumentService` must not be
accessed before initialization", which is what `FrontendIconFactoryTest`
shows when the lookup is replaced by `new`. v13 renders either way.

**The markup is core's.** `FrontendIcon` overrides nothing, so `render()` and
the protected `wrappedIcon()`, byte identical on both cores, produce exactly the
`<core:icon>` wrapper, and every provider accepts it because they type hint
`Icon`. The class exists for two reasons: the factory creates it with `new`, so
an XCLASS of core's `Icon` never reaches the frontend, and a leaner frontend
markup, should one come, overrides `wrappedIcon()` there as a change of its
own. `IconViewHelperTest` renders one icon registered identically in both
files through `ab:icon` and `core:icon` and compares the strings for every
argument, and spells the wrapper out once, so an upstream change of it shows
before it reaches a site stylesheet.

**A copy per call.** The factory keeps the prepared icon in `cache.runtime`, so
an icon repeated in every row of a page reads and sanitises its file once, and
returns a clone. `core:icon` returns the shared instance and sets the title on
it, so the next rendering of the same icon without a title still carries the
earlier one. `ab:icon` does not.

**The placeholder, and no fallback.** An identifier the frontend registry does
not know renders `default-not-found`, which `academic-base` registers in its
own `Configuration/FrontendIcons.php` with the core `SvgIconProvider` and the
core file `EXT:core/Resources/Public/Icons/T3Icons/svgs/default/default-not-found.svg`,
byte identical on both cores. It is core's drawing, but not core's markup: the
backend renders its placeholder from the sprite set, whose markup differs
between v13 and v14, the frontend one is an `<img>` on both. There is no
fallback to the backend registry for any identifier, a frontend template that
needs a core icon registers its file in `FrontendIcons.php`. A site package
that replaced `default-not-found` without provider and source leaves the
factory without a placeholder, which is the one case of the
`\LogicException` 1791061903.

**Namespace prefix.** The view helper lives in
`http://typo3.org/ns/FGTCLB/AcademicBase/ViewHelpers`, declared per template,
there is no global namespace (it would take `SYS.fluid.namespaces` on v13 and
`Configuration/Fluid/Namespaces.php` on v14). The two form partials of
`academic-base` that already declare the namespace keep their prefix `p`,
every other template declares `ab`.

## The two markups, and which provider produces what

An `Icon` carries two markups, both prepared by the provider in
`prepareIconMarkup()`: the **default markup**, which `Icon::render()` and
`<core:icon>` emit unless told otherwise, and the **`inline` alternative**,
which `<core:icon … alternativeMarkupIdentifier="inline">` or
`$icon->render('inline')` selects. Either is wrapped in the same
`<span class="t3js-icon icon …" data-identifier="…"><span class="icon-markup">…</span></span>`.

| Provider                                                               | Default markup               | `inline` markup   |
|------------------------------------------------------------------------|------------------------------|-------------------|
| core `TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider`             | `<img src="…" width height>` | the file, inlined |
| `FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider` | the file, inlined            | the file, inlined |

The difference is the default markup only. An `<img>` is opaque to CSS: it
keeps the colours of its file whatever the backend colour scheme or the
frontend theme says. An inlined `<svg>` whose shapes carry
`fill="currentColor"` takes the colour of the surrounding text — in the
backend that is `--icon-color-primary: currentColor` on `.icon`, defined in
`backend.css` on both cores, and `.icon img, .icon svg { width: 100%; height: 100% }`
sizes both shapes the same.

**Which provider when.** The rule is
[`CurrentColorSvgIconProvider` for every icon](#the-rules-in-short), with
`default-not-found` as the one exception. The reasons, per kind of icon, and
the registrations of the extensions that still differ:

- A **record or page type icon** — anything a TCA `ctrl.typeicon_classes` entry
  resolves — is drawn in `currentColor` and registered with
  `CurrentColorSvgIconProvider`. The record list, the page tree and FormEngine
  all take the *default* markup, so an `<img>` there keeps the ink of its file
  on the dark cards of a dark backend colour scheme. That is all 19
  `CurrentColorSvgIconProvider` registrations in `Icons.php` today, plus the 20
  programmatic `category_types.*` ones that ask for it with `inlineIcon: true`
  (ACE-523).
- An **action or control icon** — an arrow, a pencil, a bin, a fold-out chevron —
  is registered the same way, for the same reason: it follows the text colour,
  with or without the `inline` argument. That is 23 registrations, all
  Bootstrap Icons and all in `FrontendIcons.php`, because only the frontend
  shows them (ACE-812): the seven `academic-persons-*` icons of the public
  profile (envelope, phone, address, room, clock and the plus and minus of the
  fold-out entries) and the sixteen `academic-persons-edit-*` controls of the
  profile editing view.
- An **icon of a value** in the frontend is registered the same way, so it takes
  the colour of the text it stands in: `tx-academicprograms-info-credit-points`,
  in `FrontendIcons.php` since ACE-814.
- Everything else stays with the core `SvgIconProvider`. In `Icons.php` that is
  7 registrations: six **brand icons**, the plugin and extension marks
  `academic_jobs_icon`, `persons_icon`, `persons_edit_icon`, `bitejobs_list`,
  `academic_contacts4pages` and `academic-study-plan`, drawn in fixed colours
  and meant to look the same on every background, and one orphan,
  `tx_academiccontacts4pages_domain_model_contract`, which names a table that
  does not exist. In `FrontendIcons.php` it is 21: the seventeen
  `academic_jobs-*` icons of the job views, which render as an `<img>` of 16
  pixels, the three frontend controls of `academic-study-plan` (asked for with
  `alternativeMarkupIdentifier="inline"`, so they get the same markup either
  way), and the placeholder `default-not-found`.
- A frontend template that already asks for `inline` gets the same markup from
  both providers. Switching such an icon's provider changes nothing in the
  frontend; it changes its default markup, i.e. how it looks in the backend
  and in a template that forgot the argument. The `category_types.*` icons are
  the exception in the other direction: every template renders them *without*
  the argument, so switching them changed the frontend too — from an `<img>` of
  fixed size to an inlined `<svg width="1em" height="1em">` that follows the
  font size and the text colour.
- `width="1em" height="1em"` is the rule for an icon a *frontend* template
  renders, and only for those. Both pipelines keep the two attributes, and inside
  `.icon` the backend overrides them anyway
  (`.icon img, .icon svg { width: 100%; height: 100% }`), so carrying them costs
  a backend-only icon nothing while omitting them costs a frontend icon its
  sizing. The category type icons carry them; the record icons of the tables,
  which no frontend template renders, do not.

The provider inlines in both markups on purpose. The alternative — the default
markup as `<svg><use xlink:href="…/file.svg"/></svg>`, the shape core's
`SvgSpriteIconProvider` uses for sprites — references a whole file without a
fragment, a form browsers are not verified to render; inlining sidesteps the
question rather than depending on how browsers treat it.

### What the file has to look like

Inlined markup is part of the document, possibly several times, so the file is
drawn for that: a `viewBox`, `fill="currentColor"` or `stroke="currentColor"`
on every shape, no hardcoded colour as attribute or in a `<style>`, no `id`
attributes (a duplicated `id` is invalid HTML), no `<script>` and no event
handler attributes. [The house format](#the-house-format) is the reference
shape, and every file of the shared set is in it.

**Converting an existing drawing.** Most of the record icons here were not drawn
for inlining, and three shapes recur. An Adobe Illustrator export carries
`id="Ebene_1"`, a `<style>` block of `.stN` classes, `enable-background`,
`xml:space`, `x`, `y` and `version`, and a `<defs><clipPath><use/></clipPath>`
pair whose rectangle is the artwork's own bounding box — a no-op that only
exists because the export clips to the artboard. The class declarations become
presentation attributes, every colour becomes `currentColor`, and the rest goes.
Keep a `clip-path` only where it genuinely clips something, and then it needs an
`id`, which is exactly what must not be there: the same icon can appear many
times in one document. Measure before assuming — compare the clip rectangle
against the true bounding box of every shape it applies to, stroke width
included.

A multi-colour illustration necessarily becomes monochrome. A shape that only
existed as a lighter fill on top of a coloured body has to be re-expressed —
usually the body becomes `fill="none"` with a `stroke="currentColor"` and the
shapes on top become `fill="currentColor"`. That loses whatever the palette
distinguished, so two icons that differed only in colour end up looking alike;
that is a cost of the change, not an accident.

**Sizing.** In the backend, `backend.css` sizes the inlined element through
`.icon img, .icon svg { width: 100%; height: 100% }` on both cores. A frontend
page has such a rule only if the site or the extension ships one, and an
inlined `<svg viewBox>` without `width`/`height` fills its container. Both
pipelines keep `width` and `height` — v14's `toInlineMarkup()` drops only
`xmlns` and `version` — so a file meant for the frontend carries
`width="1em" height="1em"`, which follows the font size the way the text
around it does.

**Trust boundary.** Core's own sanitisation differs per core. v14 runs the full
`enshrined/svg-sanitize` pass through `SvgDocumentFactory`. v13 strips `<script>`
elements with a regular expression and re-serialises through `simplexml`, and
that is all: an `onload` or `onclick` attribute, a `javascript:` href and a
`<foreignObject>` pass through untouched. Rendering the file as an `<img>`, which
is what the core provider does for its default markup, made that harmless;
inlining does not, and with this provider the content lands in the default markup
the backend renders everywhere. `CurrentColorSvgIconProvider` therefore runs
`SvgSanitizer::sanitizeContent()` itself on v13 — the identical library pass, from
a class that exists with the same signature on 13.4.34 and 14.3.6, both backed by
`enshrined/svg-sanitize` 0.22.0. Both cores now produce sanitised markup.

That closes a hole, it does not move the boundary. The sanitiser is a filter, not
a guarantee, and it does nothing at all about the two ways an inlined file
interferes with the page around it: a duplicated `id`, and a `<style>` block,
which is document-global CSS once inlined. Two Adobe Illustrator exports collide
by construction — the defaults are literally `id="SVGID_1_"` and `.st0`/`.st1` —
and the observable result is one icon painted in the other's colour, or clipped by
the other's `clipPath`. So the sources stay what they were: files an extension
ships and registers in its own `Configuration/Icons.php`, drawn for inlining, and
for a category type the extension has to say `inlineIcon: true` as well, or
`frontendInlineIcon: true` for a `frontendIcon` of its own.

**A file the provider cannot inline yields no markup, and never an error.** The
guarantee holds for every reason a source can be unusable — missing,
unreadable, empty, not XML, or XML whose root element is not an `<svg>` — and
the last one had to be added rather than found: `enshrined/svg-sanitize` throws
a plain `\LogicException` with code 1570870568 out of
`XPath::handleDefaultNamespace()` when the document does not carry exactly one
`<svg>` root, and nothing below the provider catches it. A `<symbol>` fragment
or an `<html>` document saved under an `.svg` name is well-formed XML, passes
every parse guard, and would take the whole response with it — a record list or
a page tree answering 500 instead of showing one icon less. The provider
catches it in both branches. **TYPO3 v14 core has the same hole on its own
inline path**: `AbstractSvgIconProvider::getInlineSvg()` catches
`InvalidSvgException` only, while `SvgDocumentFactory::fromStringAndSanitize()`
runs the sanitiser that throws, so core's `SvgIconProvider` still fails that way
for an inline render. Fixing that belongs upstream, not here. Both cases are
covered by `fileWithoutAnSvgRootRendersEmptyMarkup()` in the unit and the
functional `CurrentColorSvgIconProviderTest`.

**A comment in the file does not reach the markup.** This was measured rather
than assumed: `Sanitizer::cleanUnsafeNodes()` removes every node that is neither
an element nor text, comments included. That has always been true on v14, and it
is true on v13 as well since the provider sanitises there too — before that, v13's
`simplexml` round trip kept comments, and the two cores rendered different markup
for the same file. A licence attribution the icon set requires (Font Awesome Free
is CC BY 4.0, for example) therefore stays in the source file for whoever reads
the repository and never reaches the rendered page. Where the licence requires
attribution in the delivered output, it has to be given elsewhere — in the
extension's documentation or a visible credits line — not through the file
comment. Core's own `SvgIconProvider` inline markup keeps the comment on v13,
because it does not take this detour.

**An unregistered file under `Resources/Public/Icons/` is covered by nothing.**
Neither the per-extension `RecordIconsTest` list nor the TCA derived assertion
next to it can see a file no `Configuration/Icons.php` and no
`Configuration/CategoryTypes.yaml` names — both walk registrations, and an
orphan is not one. Three such files survive in
`academic-programs/Resources/Public/Icons/CategoryTypes/`:
`JobProfile.svg`, `PerformanceScope.svg` and `Prerequisites.svg`. They are
still the untouched Adobe Illustrator exports, with the `id`, the `<style>`
block and the fixed colours the section above says have to go. They are
deliberately left as they are, because converting a drawing nothing renders is
work with no way to check it. The trap is the day one of them is registered:
the registration compiles, the icon appears, and it appears in the wrong
colour on a dark card. Convert the file in the same change that registers it.

### How the provider is wired, per core version

`AbstractSvgIconProvider` has the same public surface on 13.4.34 and 14.3.6 and
different internals, and that decides two things about the subclass. The parent
is `@internal` on both cores and v14 already rewrote it once; core's own
`SvgIconProvider` and deepl-base's provider extend it all the same, but every
core bump has to re-read it before trusting the two points below.

**No constructor.** On v14 the parent gets `SvgDocumentFactory` and
`SvgDocumentService` through `injectSvgDocumentFactory()` /
`injectSvgDocumentService()` setters, which TYPO3's `AutowireInjectMethodsPass`
registers for an autowired service. A subclass constructor would have to
forward what it does not own.

**Not excluded from the container.** `cms-core/Configuration/Services.php`
on v14 tags every `IconProviderInterface` as `icon.provider` and a
`PublicServicePass('icon.provider')` publishes it, so `IconFactory` finds the
provider through `$container->has()` and gets the instance with the setters
called. Excluded from the `resource` load of `academic-base`'s `Services.yaml`,
the provider would be created with `new` instead and the first inline render
on v14 would fail on an uninitialised property. `IconFactory` prefers the
container on both versions — `$this->container->has($provider) ?
$this->container->get(…) : GeneralUtility::makeInstance(…)`, v13.4.34
`IconFactory:82-84` and v14 `IconFactory:67-69` — but v13's
`cms-core/Configuration/Services.php` has no `icon.provider` tag and no
`PublicServicePass` for one, so the unreferenced private service is dropped at
compile time, `has()` answers `false` and the bare instance is what renders. It
needs nothing, because on v13 the provider does not call the parent's
`getInlineSvg()` at all — see the next section.

**One version switch.** The v14 `getInlineSvg()` resolves an `EXT:` path itself
through `SystemResourceFactory` and sanitises the content through
`SvgDocumentFactory` (which also drops the `xmlns` and synthesises a missing
`viewBox`), so v14 is handed the path unchanged and needs nothing else. The v13
one expects an absolute path and sanitises next to nothing, so
`generateInlineMarkup()` takes the whole v13 branch itself: it resolves the path
with `GeneralUtility::getFileAbsFileName()` — not through the `_assets` symlink,
so it also works outside composer mode — then reads the file, runs
`SvgSanitizer::sanitizeContent()` over it and re-serialises the document element
to drop the XML declaration the sanitiser writes. The parent's v13
`getInlineSvg()` — `file_get_contents`, a regular expression that strips
`<script>` and a `simplexml` round trip — is therefore never reached from this
provider; it is what the *core* `SvgIconProvider` still runs there, and the
reason a `javascript:` href and an `onload` survive on v13 without this
branch. That last step is what the
parent's `simplexml` round trip does on v13, and re-serialising through
`DOMDocument` instead was measured to produce the identical string for all 99
SVG files under `packages/`. The switch carries a `@todo` for the v13 support
end, like the two `TcaManipulator` switches it is listed next to in
[Core version aware code](core-version-aware-code.md).

## Keeping a template's icons resolvable

The rule below holds for both view helpers. `<core:icon>` and `<ab:icon>` never
fail on an unknown identifier, both factories answer with the
`default-not-found` placeholder — the small red "broken" icon — and the
identifier that was asked for is gone from the markup. A renamed registration
or a typo in a template therefore ships silently. The model of a rendering
test that guards against it is
[`academic-study-plan/Tests/Functional/ContentElement/AcademicStudyPlanContentElementTest.php`](../../packages/fgtclb/academic-study-plan/Tests/Functional/ContentElement/AcademicStudyPlanContentElementTest.php),
`contentElementRendersOnlyResolvableIcons()`:

```php
$content = $this->renderHomePage();
$this->assertStringNotContainsString('default-not-found', $content);
$this->assertStringContainsString('data-identifier="academic-study-plan-plus"', $content);
```

Two assertions per template, for two different mistakes: the first catches an
identifier that no longer resolves, the second catches a rename in the
registration that the template did not follow, which the first alone would
also pass, since the placeholder replaces the identifier. For an icon of the
frontend registry the first also catches a tag left on `<core:icon>`, which
asks the backend registry and gets the placeholder. Every plugin or content
element rendering test that renders icons should carry both;
`academic-persons/Tests/Functional/Plugins/AcademicPersonsPublicProfilePluginTest.php`,
`profileRendersOnlyResolvableIcons()`, does so for the seven icons of the public
profile and writes the wrapper of one of them out in full, `theRenderedEditorResolvesEveryIconItAsksFor()`
of `AcademicPersonsEditProfileEditingTest` does the same for the editor, and
`academic-jobs/Tests/Functional/Plugins/AcademicJobsListAndDetailPluginTest.php`,
`detailPluginRendersTheShippedContactIcons()`, for the two icons of the job
contact block, through the helpers of `JobContactIconAssertionTrait`, and
`listPluginRendersAResolvableIconForEveryProperty()` and
`detailPluginRendersAResolvableIconForEveryProperty()` for the twelve property
icons, on a job that carries every property. The glyphs of the study plan carry
one more contract: the shipped stylesheet switches them through the
`icon-academic-study-plan-plus` and `-minus` classes of their wrappers, which
`contentElementGivesTheGlyphsTheClassesTheStylesheetSelects()` asserts in every
semester header.

These mistakes are caught for every literal identifier before anything is
rendered. `FrontendTemplateIconTest` of `packages-dev/monorepo-shared` fails a
`unit` run, with file and line, for a frontend template that renders an icon
through a core icon ViewHelper, and for an identifier a template passes
literally to `<ab:icon>` that no `Configuration/FrontendIcons.php` and no
category type or group of the repository registers. It reads the files and
boots no TYPO3, so it covers every literal identifier of every template, also
in the branches no fixture reaches. An identifier built from a variable stays
with the rendering tests. See
[Unit tests](../testing/unit-tests.md#icons-of-frontend-templates).

The registry is asserted on its own beside that:
[`academic-persons/Tests/Functional/Imaging/PublicProfileIconsTest.php`](../../packages/fgtclb/academic-persons/Tests/Functional/Imaging/PublicProfileIconsTest.php)
and
[`academic-persons-edit/Tests/Functional/Imaging/ProfileEditingIconsTest.php`](../../packages/fgtclb/academic-persons-edit/Tests/Functional/Imaging/ProfileEditingIconsTest.php)
assert, for each of the seven and sixteen identifiers, the frontend
registration with `CurrentColorSvgIconProvider`, the inlined file in both
markups, the rendered identifier, and that the backend registry does not know
it, so `IconFactory` answers `default-not-found`. The inverse holds for the
record icons, `persons_icon` and `persons_edit_icon`: in the backend registry,
not in the frontend one. The identifiers are spelled out in the tests rather
than read back out of `Configuration/FrontendIcons.php`, so a rename has to be
made twice instead of silently agreeing with itself. The `FrontendIconsTest`
of `academic-jobs` and `academic-study-plan` assert the same for the seventeen
job icons and the three study plan controls, with the core `SvgIconProvider`
and the shipped file, and the record, plugin and content element icons the
other way round. The Fluid scan of
`AcademicPersonsEditProfileEditingTest` reads the identifiers of every
`<ab:icon>` of the editor's templates, requires each in
`Configuration/FrontendIcons.php` and each registered action icon in a
template, and fails on any `<core:icon>` left there.
`AcademicPersonsEditProfileEditingPrototypesTest` asserts the icons of the
templates the editor clones in the browser. A site package replacement is
covered by `AcademicPersonsPublicProfileIconReplacementTest` and
`AcademicPersonsEditIconReplacementTest`, against the fixture extensions
`test_profile_icon_replacement` and `test_editor_icon_replacement`: an icon
replaced in `FrontendIcons.php` reaches every control, cloned ones included,
and one replaced in `Icons.php` reaches none. `AcademicJobsIconReplacementTest`
and `AcademicStudyPlanIconReplacementTest` prove the same against
`test_job_icons` and `test_study_plan_icons`, in the job list, the detail view
with its contact block, and the study plan.

The record icons are covered by one `Tests/Functional/Imaging/RecordIconsTest.php`
per extension that ships them — `academic-contact4pages`, `academic-jobs`,
`academic-partners`, `academic-persons`, `academic-programs`, `academic-projects`
and `academic-study-plan`. Each asserts, per identifier, that it is registered
with `CurrentColorSvgIconProvider`, that both markups are the inlined file, that
the markup carries `currentColor` and neither a hardcoded colour nor an `id`, and
that the rendered icon carries its own identifier rather than
`default-not-found`. The assertions live in
[`ColourSchemeAwareIconsTrait`](../../packages-dev/testing-helper/Classes/FunctionalTestCase/ColourSchemeAwareIconsTrait.php)
of the testing helper; the identifiers are spelled out per extension.

A hand written list cannot catch what is *missing* from it, so each of those
seven tests carries one more assertion that is derived from the TCA instead:
`assertEveryRecordTypeIconIsColourSchemeAware()` walks the `ctrl` of every table,
keeps what it can attribute to the extension by the source path of the registered
icon, and requires three things — no `ctrl.iconfile` pointing into the extension,
because that bypasses the registry; no file path in a `ctrl.typeicon_classes`
value, because `registerTCAIcons()` registers `ctrl.iconfile` only and an
unregistered value renders `default-not-found`; and the `currentColor` provider
for every identifier it does resolve. `tt_content` is exempt from the last of the
three: its entries are the content element brand marks, which keep the core
provider on purpose. A new record table added without a converted icon fails this
test without anyone remembering to extend a list.

The one icon of a value, the credit points fact, has no record type behind it
and therefore no TCA assertion: `academic-programs/Tests/Functional/Imaging/FactIconsTest.php`
asserts it through `FrontendIconsAssertionTrait`, with `CurrentColorSvgIconProvider`
and the shipped file in the frontend registry, inlined in both markups, drawn in
`currentColor`, rendered under its own identifier and unknown to the backend
registry, and the page type icon `academic-programs` the other way round.

The programmatic registration is covered separately, in
[`typo3-category-types/Tests/Functional/Imaging/CategoryTypeIconsTest.php`](../../packages/fgtclb/typo3-category-types/Tests/Functional/Imaging/CategoryTypeIconsTest.php),
against the `test_category_types_icons` fixture extension, which ships all four
branches of the registrar: an SVG type with `inlineIcon: true` reaches the
`currentColor` provider; an SVG type without it keeps the core provider, and the
fixture file carries `id="SVGID_1_"`, a `.st0` fill and a `clip-path` so the test
can assert that none of it enters the document; a bitmap type asks for inlining
and keeps `BitmapIconProvider` all the same; and a type naming a file that does
not exist renders empty markup rather than throwing. All four are needed — the
first alone would pass for a registrar that inlines everything, which is the
defect the opt-in exists for.

The frontend contribution is covered next to it, in
[`typo3-category-types/Tests/Functional/Imaging/CategoryTypeFrontendIconsTest.php`](../../packages/fgtclb/typo3-category-types/Tests/Functional/Imaging/CategoryTypeFrontendIconsTest.php),
against the `test_category_types_frontend_icons` fixture extension: one type
per case of the inline rule with provider and file in both registries, a
bitmap, a type without a file rendering the placeholder, a group with a
frontend file of its own, the group `group` next to a group named like one of
its types, and a `Configuration/FrontendIcons.php` replacing one type icon in
the frontend only. The `RecordIconsTest` of partners, programs and projects
asserts every shipped type icon in both registries with the `currentColor`
provider, through `FrontendIconsAssertionTrait`. The templates that render them
are covered per extension, against the fixture extensions
`test_programs_frontend_icons`, `test_partners_frontend_icons` and
`test_projects_frontend_icons`: `ProgramFactsFrontendIconsTest` on the program
page, in the details content element and on the card, and the
`CategoryTypeFrontendIconsTest` of partners, on the partner page, the partner
card, the partnerships list and teaser, and of projects, on the project page
and card. Each fixture replaces a shipped type icon in its
`FrontendIcons.php` and declares a type with a separate `frontendIcon`, and the
tests assert the frontend drawing in every place, the absence of the backend
drawing, a shipped type nobody replaces, and the declared `icon` still in the
backend registry. The programs fixture also replaces the credit points icon in
both files, so its `Icons.php` entry has to stay out of the facts.

The provider itself is covered the same way it is used:
[`academic-base/Tests/Functional/Imaging/IconProvider/CurrentColorSvgIconProviderTest.php`](../../packages/fgtclb/academic-base/Tests/Functional/Imaging/IconProvider/CurrentColorSvgIconProviderTest.php)
registers four icons in the fixture extension `tests/current-color-icons` and
renders them through the container's `IconFactory` on both cores — inlined
default markup, identical inline markup, the comment dropped on both cores,
stripped active content (a `<script>` element, an `onload`, an `onclick` and a
`javascript:` href, all in one fixture file, because pinning only the `<script>`
is what let the v13 hole through review), empty markup for a missing file, and
the core provider's `<img>` for the same file as the contrast. The unit test next
to it covers the provider's own `source` guards on both cores and the v13
pipeline on a bare instance; the v14 pipeline cannot be built without the
container and is measured functionally only.

## The icon rules, checked

The rules above are checked by assertions of the testing helper, each of which
takes an extension key and derives the set it checks, so an icon added later
cannot slip past a hand written list. They live in three traits, one per
registry and one for the files, see
[Testing helper](../testing/testing-helper.md#colourschemeawareiconstrait):

| Rule                                                          | Backend registry (`ColourSchemeAwareIconsTrait`)     | Frontend registry (`FrontendIconsAssertionTrait`)           |
|---------------------------------------------------------------|------------------------------------------------------|-------------------------------------------------------------|
| Scheme, group of the registry, provider, file                 | `assertIconIdentifiersFollowTheNamingScheme()`       | `assertFrontendIconIdentifiersFollowTheNamingScheme()`      |
| House format of every icon drawn from a file of the extension | `assertEveryIconOfTheExtensionIsInTheHouseFormat()`  | `assertEveryFrontendIconOfTheExtensionIsInTheHouseFormat()` |
| House format of one identifier                                | `assertIconIsInTheHouseFormat()`                     | `assertFrontendIconIsInTheHouseFormat()`                    |
| Every owned type names an icon of its own                     | `assertEveryTypeOfTheExtensionNamesAnIconOfItsOwn()` | –                                                           |

`IconFilesAssertionTrait` adds the two checks of the files:
`assertEveryIconFileIsRegistered()`, which fails an SVG file below
`Resources/Public/Icons/` that no identifier of either registry draws, and
`assertEveryIconFileIsAttributedInTheNotice()`, which fails a file without the
Font Awesome comment, a file the notice does not list, and a listed file that
does not exist. `Extension.svg` is exempt from both by default.

What the checks accept is what the rules allow, and nothing else:

- A naming check reads the extension's own file, `Icons.php` or
  `FrontendIcons.php`, because neither registry can tell which package
  registered what. An identifier of a group of the other registry fails, so
  the group rule is enforced where the identifier is written down.
- A backend icon may draw any file of the extension below
  `Icons/<directory>/`, so a content element and its record or page type can
  share one, or a file of the shared set. A frontend icon draws the file named
  after it, `Icons/<group>/<name>.svg` of the extension, or a file of the
  shared set.
- The house format walks the registry, not the file, so it also reaches the
  category type and group icons `typo3-category-types` registers from a
  `CategoryTypes.yaml`, and for `academic_base` every icon another extension
  draws from a file of the shared set.
- The orphan check asks both registries, because a file of the shared set may
  be drawn only by a frontend icon, only by a backend icon of another
  extension, or only by a category type. A group icon counts like a type icon,
  `typo3-category-types` registers it as `category_types_group.<group>`.
- `default-not-found` of `academic_base` is exempt from the naming check and
  from the house format walk. It is core's identifier, core's file and core's
  provider, and a site package replaces it under that name.

[`academic-base/Tests/Functional/Imaging/SharedIconsTest.php`](../../packages/fgtclb/academic-base/Tests/Functional/Imaging/SharedIconsTest.php)
covers the shared set on both cores. Per identifier, spelled out in the test:
in the frontend registry with `CurrentColorSvgIconProvider` and unknown to the
backend one, drawn in `currentColor` without a hardcoded colour, `id` or
`<style>`, in the house format, rendered under its own identifier, and rendered
by `<ab:icon>` as core's wrapper around the inlined file, with and without
`alternativeMarkupIdentifier="inline"` and without the attribution comment. For
the whole set: the registered `tx-academicbase-*` identifiers equal the list in
the test, which reads them through the list method of the frontend registry,
and the naming, house format, orphan and notice checks pass for
`academic_base`.

[`academic-base/Tests/Functional/Imaging/IconRulesTest.php`](../../packages/fgtclb/academic-base/Tests/Functional/Imaging/IconRulesTest.php)
runs every check against the fixture extension `test_icon_rules`, which uses
every shape the rules allow: a record icon of its own and one drawn from the
shared set, a content element and a page type sharing a file, a frontend icon
of its own and one drawn from the shared set, a category type, a category type
drawn from the shared set and a category group. No extension of this
repository has all of them, and a check that rejects one of them would only
show up in the extension that adds it.

## See also

- [Core version aware code](core-version-aware-code.md) — the switch
  convention the provider follows.
- [Dependency injection](dependency-injection.md) — the `resource`/`exclude`
  load the provider stays inside, and the attribute-first style for new code.
- [Fixture extensions](../testing/fixture-extensions.md) — the mechanism the
  provider test's icons are registered through.
- [Testing helper](../testing/testing-helper.md): `ColourSchemeAwareIconsTrait`
  for the backend registry, `FrontendIconsAssertionTrait` for the frontend one,
  `IconFilesAssertionTrait` for the files.
- [The `academic_base` manual, Icons](../../packages/fgtclb/academic-base/Documentation/Icons/Index.rst)
  for integrators: the shared set and how to replace one of its icons.
