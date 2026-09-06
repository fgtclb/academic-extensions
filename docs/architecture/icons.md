# Icons

How icons are registered and consumed across the extensions, which provider to
register an icon with, and how a template's icons are kept resolvable. Where a
count is quoted with a command next to it, that count is the output of the
command, run over the repository at the commit that last touched this page —
re-run it rather than adjusting the number by hand. The counts without a command
were read off the files named beside them.

## Registration today

```bash
grep -c "'provider'" packages/fgtclb/*/Configuration/Icons.php
grep -rh "'provider' =>" packages/fgtclb/*/Configuration/Icons.php \
  | sed "s/.*=> *//" | sort | uniq -c
grep -c "'provider' => CurrentColorSvgIconProvider" \
  packages/fgtclb/*/Configuration/Icons.php
```

Eight of the twelve extension packages ship a `Configuration/Icons.php`, with **66
registrations in total: 27 with the core `SvgIconProvider` and 39 with
`CurrentColorSvgIconProvider`**:

| Package                  | Registrations | `CurrentColorSvgIconProvider` |
|--------------------------|---------------|-------------------------------|
| `academic-jobs`          | 19            | 1                             |
| `academic-persons`       | 16            | 15                            |
| `academic-persons-edit`  | 15            | 14                            |
| `academic-study-plan`    | 7             | 3                             |
| `academic-contact4pages` | 4             | 2                             |
| `academic-partners`      | 3             | 3                             |
| `academic-bite-jobs`     | 1             | –                             |
| `academic-programs`      | 1             | 1                             |

The 39 are of two kinds. Twenty are control icons — the six of the public
profile of `academic-persons` and the fourteen of the profile editing view of
`academic-persons-edit`, all Bootstrap Icons. The other nineteen are **record
icons**: every identifier a TCA record type resolves through
`ctrl.typeicon_classes`, including the two page type icons `academic-partners`
and `academic-programs` (ACE-523).

`academic-base`, `academic-projects`, `academic-persons-sync` and the three
`packages-dev/` packages register nothing.

One registration is programmatic: `typo3-category-types` registers
`category_types.<group>.<type>` per configured category type on
`BootCompletedEvent`
([`Classes/ServiceProvider.php`](../../packages/fgtclb/typo3-category-types/Classes/ServiceProvider.php),
`addIcons()`). It asks `IconRegistry::detectIconProvider()`, which knows bitmap
versus SVG by file extension and nothing else. Those icons are `sys_category`
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
`<core:icon>` in the three page layout partials
`Resources/Private/Backend/Partials/PageLayout/Doktype*.html` of
`academic-programs`, `academic-projects` and `academic-partners`.

The frontend consumes them through `<core:icon>` as well. `core` is a global
Fluid namespace on both core versions — through `SYS.fluid.namespaces` of
`cms-core/Configuration/DefaultConfiguration.php` on v13, and through
`cms-core/Configuration/Fluid/Namespaces.php` on v14, where the setting is
empty by default since 14.1 — so the ViewHelper needs no `xmlns` declaration
in a frontend template. The
ViewHelper is byte identical on 13.4.34 and 14.3.6. Which markup a template
gets depends on one argument, and the templates on `main` are split on it:

A `<core:icon>` is regularly written across several lines, so the argument has
to be counted per tag rather than per line:

```bash
grep -rl "<core:icon" packages/fgtclb/*/Resources/Private --include=*.html \
  | grep -v /Backend/ \
  | xargs perl -0777 -ne 'while (/<core:icon\b[^>]*>/gs) {
      print /alternativeMarkupIdentifier="inline"/ ? "inline\t$ARGV\n" : "default\t$ARGV\n" }' \
  | sort | uniq -c
```

| Extension               | `alternativeMarkupIdentifier="inline"`   | Without (default markup)                                           |
|-------------------------|------------------------------------------|--------------------------------------------------------------------|
| `academic-persons-edit` | 31 sites in 13 files                     | —                                                                  |
| `academic-persons`      | 6 sites in 2 files, `academic-persons-*` | —                                                                  |
| `academic-study-plan`   | 3 sites, its `plus`/`minus`/`close`      | —                                                                  |
| `academic-jobs`         | 2 sites, core `phone`/`mail`             | `Job/Item.html`, `Job/Information.html`                            |
| `academic-partners`     | —                                        | 4 files, `category_types.partners.*` only                          |
| `academic-programs`     | —                                        | `Program/Categories.html`, `Program/Item.html`, `category_types.*` |
| `academic-projects`     | —                                        | `AcademicProject.html`, `Project/Item.html`                        |

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

**Which provider when:**

- A **record or page type icon** — anything a TCA `ctrl.typeicon_classes` entry
  resolves — is drawn in `currentColor` and registered with
  `CurrentColorSvgIconProvider`. The record list, the page tree and FormEngine
  all take the *default* markup, so an `<img>` there keeps the ink of its file
  on the dark cards of a dark backend colour scheme. That is 19 of the 39
  registrations today, plus the 20 programmatic `category_types.*` ones that ask
  for it with `inlineIcon: true` (ACE-523).
- An **action or control icon** — an arrow, a pencil, a bin, a fold-out chevron —
  is registered the same way, for the same reason: it follows the text colour in
  the backend *and* in the frontend, with or without the `inline` argument. That
  is the other 20 registrations, all Bootstrap Icons: the six
  `academic-persons-*` icons of the public profile — envelope, phone, address,
  room and the plus and minus of the fold-out entries — and the fourteen
  `academic-persons-edit-*` controls of the profile editing view.
- Everything else stays with the core `SvgIconProvider` — 27 registrations: the
  seventeen `academic_jobs-*` icons of the job detail fields, the three frontend
  controls of `academic-study-plan` (asked for with
  `alternativeMarkupIdentifier="inline"`, so they get the same markup either
  way), six **brand icons** — the plugin and extension marks
  `academic_jobs_icon`, `persons_icon`, `persons_edit_icon`, `bitejobs_list`,
  `academic_contacts4pages` and `academic-study-plan`, drawn in fixed colours
  and meant to look the same on every background — and one orphan,
  `tx_academiccontacts4pages_domain_model_contract`, which names a table that
  does not exist.
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
handler attributes. The shipped `academic-study-plan`
`plus.svg`/`minus.svg`/`close.svg` are the reference shape.

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
for a category type the extension has to say `inlineIcon: true` as well.

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

`<core:icon>` never fails on an unknown identifier: `IconFactory` answers with
the `default-not-found` placeholder — the small red "broken" icon — and the
identifier that was asked for is gone from the markup. A renamed registration
or a typo in a template therefore ships silently. The one test that guards
against it is
[`academic-study-plan/Tests/Functional/ContentElement/AcademicStudyPlanContentElementTest.php`](../../packages/fgtclb/academic-study-plan/Tests/Functional/ContentElement/AcademicStudyPlanContentElementTest.php),
`contentElementRendersOnlyResolvableIcons()`:

```php
$content = $this->renderHomePage();
$this->assertStringNotContainsString('default-not-found', $content);
$this->assertStringContainsString('data-identifier="academic-study-plan-plus"', $content);
```

Two assertions per template, for two different mistakes: the first catches an
identifier that no longer resolves, the second catches a rename in
`Configuration/Icons.php` that the template did not follow — which the first
alone would also pass, since the placeholder replaces the identifier. Every
plugin or content element rendering test that renders icons should carry both;
`academic-persons/Tests/Functional/Plugins/AcademicPersonsPublicProfilePluginTest.php`,
`profileRendersOnlyResolvableIcons()`, does so for the six icons of the public
profile.

The registry is asserted on its own beside that:
[`academic-persons-edit/Tests/Functional/Imaging/ProfileEditingIconsTest.php`](../../packages/fgtclb/academic-persons-edit/Tests/Functional/Imaging/ProfileEditingIconsTest.php)
asks the `IconFactory` for each of the fourteen action identifiers and asserts
that the answer is that identifier and not `default-not-found`, and that its
default markup is the inlined file. The identifiers are spelled out in the test
rather than read back out of `Configuration/Icons.php`, so a rename has to be
made twice instead of silently agreeing with itself.

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

## See also

- [Core version aware code](core-version-aware-code.md) — the switch
  convention the provider follows.
- [Dependency injection](dependency-injection.md) — the `resource`/`exclude`
  load the provider stays inside, and the attribute-first style for new code.
- [Fixture extensions](../testing/fixture-extensions.md) — the mechanism the
  provider test's icons are registered through.
