# Icons

How icons are registered and consumed across the extensions, which provider to
register an icon with, and how a template's icons are kept resolvable. The
counts on this page were measured on `main` at the time of writing and can be
reproduced with the commands quoted next to them.

## Registration today

```bash
grep -c "'provider'" packages/fgtclb/*/Configuration/Icons.php
grep -rh "'provider' =>" packages/fgtclb/*/Configuration/Icons.php | sort | uniq -c
```

Eight of the twelve extension packages ship a `Configuration/Icons.php`, with **62
registrations in total: 56 with the core `SvgIconProvider`, and the six control
icons of the public profile of `academic-persons` with
`CurrentColorSvgIconProvider`**:

| Package                  | Registrations |
|--------------------------|---------------|
| `academic-jobs`          | 18            |
| `academic-persons-edit`  | 12            |
| `academic-persons`       | 16            |
| `academic-study-plan`    | 7             |
| `academic-contact4pages` | 4             |
| `academic-partners`      | 3             |
| `academic-bite-jobs`     | 1             |
| `academic-programs`      | 1             |

`academic-base`, `academic-projects`, `academic-persons-sync` and the three
`packages-dev/` packages register nothing.

One registration is programmatic: `typo3-category-types` registers
`category_types.<type>.<x>` per configured category type on `BootCompletedEvent`
([`Classes/ServiceProvider.php`](../../packages/fgtclb/typo3-category-types/Classes/ServiceProvider.php),
`addIcons()`), and asks `IconRegistry::detectIconProvider()` for the provider.
That method knows bitmap versus SVG by file extension and nothing else, so a
category type icon always gets the core provider — a different provider for
those would be a change to the registrar, not to a configuration file.

### Where the identifiers are consumed

The backend consumes identifiers through `typeicon_classes` in 17 files under
`packages/fgtclb/*/Configuration/TCA/` plus
[`academic-base/Classes/TcaManipulator.php`](../../packages/fgtclb/academic-base/Classes/TcaManipulator.php)
for select items, through the `icon` key of every content element registration
in `Configuration/TCA/Overrides/tt_content.php` — `academic-programs` passes an
`EXT:` path there instead of an identifier, which TYPO3 accepts — and through
`<core:icon>` in the three page layout partials
`Resources/Private/Backend/Partials/PageLayout/Doktype*.html` of
`academic-programs`, `academic-projects` and `academic-partners`.

The frontend consumes them through `<core:icon>` as well. `core` is a global
Fluid namespace on both core versions
(`cms-core/Configuration/DefaultConfiguration.php`, `SYS.fluid.namespaces`), so
the ViewHelper needs no `xmlns` declaration in a frontend template. The
ViewHelper is byte identical on 13.4.34 and 14.3.6. Which markup a template
gets depends on one argument, and the templates on `main` are split on it:

```bash
grep -rn "core:icon" packages/fgtclb/*/Resources/Private --include=*.html | grep -v Backend
```

| Extension               | `alternativeMarkupIdentifier="inline"`   | Without (default markup)                                           |
|-------------------------|------------------------------------------|--------------------------------------------------------------------|
| `academic-persons-edit` | 65 sites in 17 files                     | —                                                                  |
| `academic-persons`      | 6 sites in 2 files, `academic-persons-*` | —                                                                  |
| `academic-study-plan`   | 3 sites, its `plus`/`minus`/`close`      | —                                                                  |
| `academic-jobs`         | 2 sites, core `phone`/`mail`             | `Job/Item.html`, `Job/Information.html`                            |
| `academic-partners`     | —                                        | 4 files, `category_types.*` and `academic-partners`                |
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

- A **record, page type, content element or brand icon** — anything drawn in
  fixed colours, meant to look the same on every background — stays with the
  core `SvgIconProvider`. That is 56 of the 62 registrations today.
- An **action or control icon** — an arrow, a pencil, a bin, a fold-out chevron —
  is drawn in `currentColor` and registered with `CurrentColorSvgIconProvider`.
  Then it follows the text colour in the backend *and* in the frontend, with
  or without the `inline` argument. The six `academic-persons-*` icons of the
  public profile — envelope, phone, address, room and the plus and minus of
  the fold-out entries, all Bootstrap Icons — are the first registrations of
  that kind.
- A frontend template that already asks for `inline` gets the same markup from
  both providers. Switching such an icon's provider changes nothing in the
  frontend; it changes its default markup, i.e. how it looks in the backend
  and in a template that forgot the argument.

The provider inlines in both markups on purpose. The alternative — the default
markup as `<svg><use xlink:href="…/file.svg"/></svg>`, the shape core's
`SvgSpriteIconProvider` uses for sprites — references a whole file without a
fragment, a form browsers are not verified to render (05 §3 of the analysis
recorded it as unverified); inlining sidesteps the question rather than
depending on how browsers treat it.

### What the file has to look like

Inlined markup is part of the document, possibly several times, so the file is
drawn for that: a `viewBox`, `fill="currentColor"` or `stroke="currentColor"`
on every shape, no hardcoded colour as attribute or in a `<style>`, no `id`
attributes (a duplicated `id` is invalid HTML), no `<script>` and no event
handler attributes. The shipped `academic-study-plan`
`plus.svg`/`minus.svg`/`close.svg` are the reference shape.

**Sizing.** In the backend, `backend.css` sizes the inlined element through
`.icon img, .icon svg { width: 100%; height: 100% }` on both cores. A frontend
page has such a rule only if the site or the extension ships one, and an
inlined `<svg viewBox>` without `width`/`height` fills its container. Both
pipelines keep `width` and `height` — v14's `toInlineMarkup()` drops only
`xmlns` and `version` — so a file meant for the frontend carries
`width="1em" height="1em"`, which follows the font size the way the text
around it does.

**Trust boundary.** The sanitisation differs per core, and neither is a reason
to inline a file from anywhere else. v14 runs the full `enshrined/svg-sanitize`
pass. v13 strips `<script>` elements with a regular expression and
re-serialises through `simplexml` — event handler attributes, `javascript:`
hrefs and `<foreignObject>` pass through, and with this provider they land in
the default markup the backend renders everywhere. The sources are files an
extension ships and registers in its own `Configuration/Icons.php`, never
editor uploads; that is the same trust boundary the core provider's `inline`
markup has always had, and it is the boundary to keep.

**A comment in the file does not reach the markup on v14.** The two pipelines
differ (below), and this was measured rather than assumed: v13 re-serialises
the file with `simplexml` and keeps comments; v14 sanitises it through
`enshrined/svg-sanitize` 0.22.0, whose `Sanitizer::cleanUnsafeNodes()` removes
every node that is neither an element nor text — comments included. A licence
attribution the icon set requires (Font Awesome Free is CC BY 4.0, for
example) therefore stays in the source file for whoever reads the repository,
but the rendered page carries it on v13 only. Where the licence requires
attribution in the delivered output, it has to be given elsewhere — in the
extension's documentation or a visible credits line — not through the file
comment. The same applies to the core `SvgIconProvider`'s inline markup, which
runs through the same pipeline.

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
on v14 would fail on an uninitialised property. On v13 there is no such tag,
the unreferenced private service is dropped at compile time, `IconFactory`
falls back to `GeneralUtility::makeInstance()`, and the bare instance needs
nothing — `getInlineSvg()` there is `file_get_contents`, a `<script>` strip and
a `simplexml` re-serialisation.

**One version switch.** The v13 `getInlineSvg()` expects an absolute path; the
v14 one resolves an `EXT:` path itself through `SystemResourceFactory` and
sanitises the content through `SvgDocumentFactory` (which also drops the
`xmlns` and synthesises a missing `viewBox`). `generateInlineMarkup()` therefore
resolves the path with `GeneralUtility::getFileAbsFileName()` on v13 only —
not through the `_assets` symlink, so it also works outside composer mode —
and hands v14 the path unchanged. The switch carries a `@todo` for the v13
support end, like the two `TcaManipulator` switches it is listed next to in
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

The provider itself is covered the same way it is used:
[`academic-base/Tests/Functional/Imaging/IconProvider/CurrentColorSvgIconProviderTest.php`](../../packages/fgtclb/academic-base/Tests/Functional/Imaging/IconProvider/CurrentColorSvgIconProviderTest.php)
registers four icons in the fixture extension `tests/current-color-icons` and
renders them through the container's `IconFactory` on both cores — inlined
default markup, identical inline markup, the comment kept on v13 and dropped on
v14 (two tests, one per `not-core-*` group), a stripped `<script>`, empty
markup for a missing file, and the core provider's `<img>` for the same file
as the contrast. The unit test next to it covers the
provider's own `source` guards on both cores and the v13 pipeline on a bare
instance; the v14 pipeline cannot be built without the container and is
measured functionally only.

## See also

- [Core version aware code](core-version-aware-code.md) — the switch
  convention the provider follows.
- [Dependency injection](dependency-injection.md) — the `resource`/`exclude`
  load the provider stays inside, and the attribute-first style for new code.
- [Fixture extensions](../testing/fixture-extensions.md) — the mechanism the
  provider test's icons are registered through.
