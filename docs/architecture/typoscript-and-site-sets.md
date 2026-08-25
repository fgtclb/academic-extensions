# TypoScript and site sets

Every extension here ships its frontend TypoScript and its backend page
TSconfig through **two** mechanisms at once: TYPO3 site sets, and the classic
static template plus `tsconfig_includes` registration. An installation picks
one; the extension has to serve both.

The obvious way to do that — a copy of each file per mechanism — is how the
repository used to work, and it drifts. This page describes the layout that
replaces it, the two `config.yaml` keys that make it possible, and the traps
that were found while verifying it against the core source of TYPO3 v13.4.34
and v14.3.6.

`academic-bite-jobs` is the reference implementation: one component, both
mechanisms, nothing else in the way.

## The one physical copy rule

`YamlSetDefinitionProvider::createDefinition()` fills two optional keys of a
set when the `config.yaml` does not:

```php
$setData['typoscript'] ??= $basePath;                 // the set folder
$setData['pagets']     ??= $basePath . 'page.tsconfig';
```

`??=` — so an explicit key in `config.yaml` wins, and it may point anywhere an
`EXT:` path can reach. `SysTemplateTreeBuilder::handleSetInclude()` then reads
`<typoscript>/include_static_file.txt`, `<typoscript>/constants.typoscript` and
`<typoscript>/setup.typoscript` from wherever `typoscript:` points, through the
same code path a `sys_template` record uses.

So a set points `typoscript:` at exactly the folder `addStaticFile()`
registers, and `pagets:` at exactly the file `registerPageTSConfigFile()`
registers. There is one physical copy of every file, and the two mechanisms
cannot produce different results because they read the same bytes.

Neither key has to end in a slash. `SysTemplateTreeBuilder` passes
`rtrim($set->typoscript, '/') . '/'` into the include, byte-identically on both
supported core versions, so the separator is core's job. Writing the slash is a
readability choice, not a rule.

## Layout per extension

```text
Configuration/
  TypoScript/
    <Component>/
      constants.typoscript
      setup.typoscript
    Full/
      include_static_file.txt        # lists every component folder
  TSconfig/
    <Component>/
      page.tsconfig                  # re-enables this component's CType
    Full/
      page.tsconfig                  # imports every component page.tsconfig
  Sets/
    <Component>/config.yaml          # typoscript: + pagets: -> the files above
    Full/config.yaml                 # dependencies: only
  page.tsconfig                      # auto-loaded, GLOBAL: hides every own CType,
                                     # and imports a component that has to apply
                                     # everywhere, if the extension has one
  TCA/Overrides/sys_template.php     # addStaticFile() per component, plus Full
  TCA/Overrides/pages.php            # registerPageTSConfigFile() per component, plus Full
```

Set names: the aggregate is `fgtclb/<extension-key-with-dashes>`, a component
is `fgtclb/<extension-key-with-dashes>-<component>`. The aggregate keeps the
name the extension already published, so a site that depends on it keeps
working while its payload moves into the component sets underneath it.

Not every component belongs behind a site set. An extension may ship one that
has to apply to every installation regardless of the site configuration — the
content element group label of `academic-base` is one, because a group that is
rendered on every installation has to carry its label there too. Such a
component keeps its own folder and its own set like any other, and the global
`Configuration/page.tsconfig` imports it in addition to hiding what is opt-in.
That is the only reason that file ever holds an `@import`.

### When several content elements share one TypoScript block

The layout above assumes each component owns its own `constants.typoscript` and
`setup.typoscript`. Two extensions do not work that way: `academic-persons`
delivers six content elements and `academic-jobs` three, all of them configured
by a single `plugin.tx_<key>` block. Splitting such a block per component would
duplicate the same settings; delivering it from every component set would parse
it once per enabled component.

Neither is necessary. The shared block stays in one folder, and each component
folder holds nothing but a one-line `include_static_file.txt` naming it:

```text
Configuration/
  TypoScript/
    <shared>/                        # the one plugin.tx_<key> block
      constants.typoscript
      setup.typoscript
    <Component>/
      include_static_file.txt        # -> EXT:<key>/Configuration/TypoScript/<shared>
```

This needs no special case in either mechanism.
`SysTemplateTreeBuilder::handleSetInclude()` reads `include_static_file.txt` out
of the folder a set's `typoscript:` key names, exactly as the static template
path does, so the shared block reaches a component whether it arrived through a
site set or through a `sys_template` record — and it exists exactly once on
disk.

**The shared folder keeps whatever name it already had.** It is the value
stored in existing `sys_template` records and often the path functional tests
load directly, so renaming it costs a migration and buys nothing. The two
extensions therefore differ, and deliberately: `academic-persons` keeps
`TypoScript/Default/`, `academic-jobs` keeps the TypoScript root itself, with
its component folders as subfolders of it. Read what `addStaticFile()`
registers today before choosing.

The same mechanism serves a component that needs another *extension's*
TypoScript. `academic-contact4pages` reads a constant of `academic-persons`, and
names that extension's folder in its own `include_static_file.txt` rather than
depending on one of its sets — a set dependency delivers the constant on
neither path, and would make the other extension's content element selectable
as a side effect.

Three details that are easy to get wrong:

| Detail                                                        | Why                                                                                                                                                    |
|---------------------------------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------|
| `addStaticFile()` belongs in `TCA/Overrides/sys_template.php` | From `ext_localconf.php` it is guarded by `is_array($GLOBALS['TCA']['sys_template']['columns'])` and does nothing at all, silently.                    |
| The `Full` set declares `dependencies:` and nothing else      | It must not repeat the component payload — the component set already delivers it, and a second `typoscript:` would parse the same files twice.         |
| The `Full` static entry point is a file, not a set            | `include_static_file.txt` and `TSconfig/Full/page.tsconfig` are what the *static* mechanism needs, because it has no dependency resolution of its own. |

`include_static_file.txt` holds `EXT:` paths separated by **commas**, and by
nothing else: the core runs `GeneralUtility::trimExplode(',', $content, true)`
over the file, which splits on commas only. Two paths on two lines without a
comma between them become one entry, and that entry fails the way this whole
page exists to avoid — the `EXT:` prefix check passes, the extension key
resolves, the path does not exist, and the include returns having loaded
nothing, with no exception and no log entry. A comment line is not allowed
either, but at least fails loudly: it is parsed as a path and throws
`RuntimeException` 1651137904. Write the entries without a trailing slash, the
way the core and `bk2k/bootstrap-package` write them.

## Hide by default, enable per component

An extension registers its content elements in TCA for the whole installation —
that is not negotiable, the records have to be renderable wherever they exist.
What moves per site is only whether an editor can *pick* the element.

The always-loaded `Configuration/page.tsconfig` hides them:

```typoscript
TCEFORM.tt_content.CType.removeItems := addToList(academicbitejobs_list)
```

and the component's own page TSconfig brings its one element back:

```typoscript
TCEFORM.tt_content.CType.removeItems := removeFromList(academicbitejobs_list)
```

That file is reached by the site set through `pagets:`, and by an installation
without site sets through the page field *Page TSconfig*, which is what
`registerPageTSConfigFile()` feeds.

The order this relies on is `TsConfigTreeBuilder::getPagesTsConfigTree()`:
package `page.tsconfig` -> set `page.tsconfig` -> site `page.tsconfig` ->
`tsconfig_includes` -> the page record's own `TSconfig`. The global hide is
first, both ways to re-enable come after it, and the integrator's own page
TSconfig still wins over all of them.

The new content element wizard follows along on both supported core versions:
`NewContentElementController` drops every item whose value appears in
`TCEFORM.tt_content.CType.removeItems` before it renders.

## There is no double-parse guard, and that is deliberate

A site that uses *both* mechanisms parses the shared files twice. Every guard
that could suppress the second parse was written out and rejected:

| Candidate                     | Verdict                                                                                                                                                         |
|-------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `[not ('x' in site('sets'))]` | Broken. `Site::getSets()` returns the site's *declared* `dependencies` verbatim, so a set that arrives transitively never matches.                              |
| The resolved set list         | Unreachable from a condition. It exists only inside `SetRegistry::getSets()`, on v13 and on v14 alike.                                                          |
| A site-setting sentinel       | Technically reliable and therefore dangerous: it survives a `sys_template` `clear` flag, so it would suppress the static branch after the set branch was wiped. |
| A constants sentinel          | Breaks on `clear = 2`, and constants conditions get no `{$…}` substitution at all — it guards the harmless half and leaves the harmful half open.               |

So both paths ship unguarded, exactly as `bk2k/bootstrap-package` does. What
the double parse can actually cost is narrow: the set branch is read *before*
the `sys_template` rows, so the second parse can reset a constant that the
integrator set in `settings.yaml` or in `config/sites/<id>/constants.typoscript`
in between. Everything later — the `sys_template` record's own `constants` and
`config` fields, `tsconfig_includes`, the page record's `TSconfig` — still
wins.

Two things follow for the code:

- Keep a `settings.definitions.yaml` default identical to the value
  `constants.typoscript` assigns for the same path, or declare the path in only
  one of the two. Two different defaults for one constant is the bug this
  produces.
- Tell integrators, in the extension's own `Documentation/Configuration/`
  chapter, to use one mechanism per site.

## The `clear = 3` trap

`IncludeTreeAstBuilderVisitor::visitBeforeChildren()` resets the whole AST when
a `sys_template` row carries a clear flag. `SiteTemplateInclude` is a separate
class and never triggers it — but a `sys_template` row does, and it discards
everything the site sets contributed.

The backend's own **Create a root TypoScript record** button — that is its label
on both v13 and v14 — writes `'root' => 1, 'clear' => 3`. On a site that is
driven by sets, pressing it throws the entire set contribution away, and the
failure looks like "the extension ships no TypoScript" rather than like a
template record problem.

This is a bigger trap than the double parse. It is also what an installation
in that state recovers from by selecting the static template: the record that
wiped the set contribution can carry the same configuration itself.

## Both mechanisms, one site each: the development instances

The instructions above say **one mechanism per site**. The development instances
follow that by running one site per mechanism, side by side in one installation:

| Site directory                           | Root page | Base       | TypoScript arrives through                                                                           |
|------------------------------------------|-----------|------------|------------------------------------------------------------------------------------------------------|
| `core-NN/config/sites/academics/`        | 1         | `/`        | `dependencies:` — `bootstrap-package/full`, the ten academic aggregates, `typo3/felogin`             |
| `core-NN/config/sites/academics-legacy/` | 1001      | `/legacy/` | the `include_static_file` column of a root `sys_template` record, and the page's `tsconfig_includes` |

The second tree is a **full mirror** of the first — the same content pages, the
same content elements, the same images, the same slugs — written by
`packages-dev/dev-site/Configuration/DataFactory/academics-instance/ScenarioLegacy.yaml`.
That is what makes a difference between the two delivery paths observable: the
same page renders under `/persons/list` and under `/legacy/persons/list`, and
the markup either matches or it does not. A smoke tree of a handful of pages
would only find a difference somebody guessed at in advance.

The mirror is the content pages only. The nine storage folders below `/data`
are **not** mirrored: their records are shared, so a mirrored folder would be an
empty folder in the backend that looks like it should hold something. `/` is 65
pages, `/legacy/` is 56, and the `pages` table carries 242 rows once the German
variant of every one of them is counted.

The two trees share their record storage. Roughly fifteen of the seeded tables
are addressed by a storage pid, so they exist once, under `/data`, and both
trees name those pids. Four are addressed by a page or by a content element —
`tx_academiccontacts4pages_domain_model_contact`,
`tx_academicpartners_domain_model_partnership`,
`tx_academicstudyplan_domain_model_semester` and, through its semester,
`tx_academicstudyplan_domain_model_module` — and those are duplicated with the
page that owns them, because no configuration can make the reading code look
somewhere else.

The legacy site names no site set, and it carries no `settings.yaml`. What the
site settings of `academics` say, it says in the `constants` field of its
`sys_template` record, with the page uids of its own tree. Both are the same
statement; making it twice in two different ways is the point of the site.

### The legacy tree is not themed, and cannot be

`bootstrap-package/full` is a site set, and from version 16 of that extension it
is the *only* way it delivers anything: version 15 still shipped
`Configuration/TypoScript/` and registered it with `addStaticFile()`, version 16
removed both, along with its `registerPageTSConfigFile()` calls. The `core-13`
instance runs 15 and the `core-14` instance runs 16, so a `sys_template` record
naming the theme would render one instance and render nothing at all in the
other.

The `/legacy/` tree therefore gets no theme. It names
`EXT:academics_dev_site/Configuration/TypoScript` instead — the smallest page
object that puts the content of a page on the page, shipped by the seed package
in both delivery forms and described in
[its README](../../packages-dev/dev-site/README.md). The consequence to know
about is visual only: `/legacy/` renders unstyled, and its pages'
`backend_layout` values name layouts that tree does not define.

This is also the first finding of the drift gate described in
[Seed verification](../testing/seed-verification.md), and a good illustration of
why its second half exists: the theme's static template folder *resolved*
perfectly well on `core-13` while nothing offered it any more.

## Route enhancers are not loaded by anything

Five files ship route enhancers and **none of them is read by TYPO3 on its own**:

| Extension           | Files                                                   |
|---------------------|---------------------------------------------------------|
| `academic_persons`  | `Configuration/Routes/{List,ListAndDetail,Detail}.yaml` |
| `academic_jobs`     | `Configuration/Routes/Detail.yaml`                      |
| `academic_programs` | `Configuration/Yaml/Routes.yaml`                        |

A site configuration has to `imports:` them, or every detail page is reachable
only through a raw `tx_…[…]` argument. Both instance site configurations do:

```yaml
imports:
  - resource: 'EXT:academic_persons/Configuration/Routes/List.yaml'
  - resource: 'EXT:academic_persons/Configuration/Routes/ListAndDetail.yaml'
  - resource: 'EXT:academic_persons/Configuration/Routes/Detail.yaml'
  - resource: 'EXT:academic_jobs/Configuration/Routes/Detail.yaml'
  - resource: 'EXT:academic_programs/Configuration/Yaml/Routes.yaml'
```

Not through a site set: a set may carry a `route-enhancers.yaml` only from TYPO3
v14.1, and this branch supports v13 as well, so `imports:` is the form that
works on both.

### Importing is half of it — the enhancers have to be limited to their pages

Importing all five is not enough, and the distinct enhancer keys do not make it
safe. TYPO3 offers **every** enhancer of a site to **every** page unless the
enhancer carries `limitToPages`, and `PageUriMatcher::matchCollection()` takes
the first candidate route whose path matches *and* whose aspects resolve. The
insertion order is the `imports:` order.

Three of the five routes are declared twice, byte identical down to the mapper:

| Route                       | Declared in                             |
|-----------------------------|-----------------------------------------|
| `/{profile_name}`           | `Detail.yaml`, `ListAndDetail.yaml`     |
| `{localized_page}-{page}`   | `List.yaml`, `ListAndDetail.yaml`       |
| `/{letter}`                 | `List.yaml`, `ListAndDetail.yaml`       |

So the file imported first takes those URLs on every page of the site, and the
plugin on the other page never receives its argument. That is ACE-470: the
dedicated `/persons/detail` page answered 404 for every link the list plugins
generated for it, on both instances, in both languages and in both page trees,
while `/persons/list-and-detail/<slug>` kept working. Only *resolving* is
ambiguous — generation is scoped to the plugin namespace being linked, so the
links look right and the defect surfaces as a broken page instead.

The jobs and programs enhancers are not caught by this even though
`/{job_title}` compiles to the same greedy `.+` — an aspect variable without an
explicit `requirements` entry always does. Their mappers read other tables, so
a persons route that matched the path is skipped when the slug is not a profile
slug and the matcher falls through. That is a thin guarantee, not a design.

All four instance site configurations therefore pin every enhancer:

```yaml
routeEnhancers:
  ProfileListPlugin:
    limitToPages: [201, 202, 203]
  ProfileListAndDetailPlugin:
    limitToPages: [204]
  ProfileDetailPlugin:
    limitToPages: [205]
  AcademicJobsDetailPlugin:
    limitToPages: [233]
  AcademicPrograms:
    limitToPages: [251, 252]
```

The uids are the ones the seed declares, `+1000` in the `academics-legacy`
site, and they are the uids of the **default language**: matching derives the
page as `l10n_parent ?: uid`, so one list covers `/persons/detail` and
`/de/personal/detail` alike. Generation on the other hand uses the linked page
uid, which is the default-language uid too — naming a translated page's own uid
would work for neither direction. Plain uids work on v13 and v14; the
ExpressionLanguage form of `limitToPages` is v14.2 and later only.

## The integrator chapter

Each converted extension documents the two mechanisms in its own
`Documentation/Configuration/Index.rst`, and every package uses the same eight
anchors so a cross-reference written once holds everywhere:

| Anchor                            | Section                                                          |
|-----------------------------------|------------------------------------------------------------------|
| `configuration`                   | The chapter itself.                                              |
| `configuration-components`        | What the sets contain, one row per component plus the aggregate. |
| `configuration-hidden-by-default` | Which content elements are hidden and what brings them back.     |
| `site-set`                        | How to name the set in `config.yaml`.                            |
| `static-templates`                | The classic mechanism, and the `clear = 3` warning.              |
| `static-typoscript`               | The `sys_template` entries to select.                            |
| `static-pagetsconfig`             | The page TSconfig entries to select.                             |
| `one-mechanism-per-site`          | Why not to combine the two.                                      |

An extension that ships nothing for one of them keeps the anchor and says so in
one line — `academic-base` ships no TypoScript and no content element, and its
`static-typoscript` and `configuration-hidden-by-default` sections say exactly
that. Omitting the section instead is what makes a shared cross-reference
impossible.

## What is converted

All ten extensions that have configuration to convert follow this layout:
`academic-base`, `academic-bite-jobs`, `academic-persons`,
`academic-contact4pages`, `academic-persons-edit`, `academic-jobs`,
`academic-study-plan`, `academic-partners`, `academic-programs` and
`academic-projects`. The remaining two, `academic-persons-sync` and
`typo3-category-types`, ship neither a `Configuration/TypoScript/` nor a
`Configuration/Sets/` and have nothing to convert.

It was done as ACE-458, per extension, each with its own Breaking changelog
entry, because every conversion moves paths that a site package may `@import`.
A new extension follows the layout from the start; `academic-bite-jobs` is the
smallest complete example to copy.

`academic-persons` is the one extension where six content elements share a
single `plugin.tx_academicpersons` block. It keeps that block in
`Configuration/TypoScript/Default/` and each component folder names it in an
`include_static_file.txt`, which both delivery paths read — see its own
integrator chapter. The folder deliberately keeps the name `Default`: it is the
value stored in existing `sys_template` records and the path several functional
tests load directly.

The three extensions with a page type of their own — `academic-partners`,
`academic-programs` and `academic-projects` — draw one extra line the others do
not need. A page doktype and a backend layout identifier are values persisted on
`pages` records, so they stay registered installation-wide (TCA and the
auto-loaded `Configuration/page.tsconfig`) and never move behind a component
set; only the content element visibility does. Each of the three carries a
`Tests/Functional/SiteSet/InstallationWideRegistrationTest.php` that pins it.

## See also

- [Core version aware code](core-version-aware-code.md) — the other place where
  a difference between v13 and v14 has to be handled explicitly.
- [Monorepo layout](../development/monorepo-layout.md) — which extension lives
  where, and which extension key it ships.
- [Changelog and documentation](../workflow/changelog-and-documentation.md) —
  the integrator-facing counterpart of this page, and where a Breaking entry
  goes.
- [`packages/fgtclb/academic-bite-jobs/Configuration`](../../packages/fgtclb/academic-bite-jobs/Configuration)
  — the reference implementation.
- `.Build/vendor/typo3/cms-core/Classes/Site/Set/YamlSetDefinitionProvider.php`
  and `.../TypoScript/IncludeTree/SysTemplateTreeBuilder.php` — the two classes
  every claim above was read out of.
