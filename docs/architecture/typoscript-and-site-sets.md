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

## The integrator chapter

Each converted extension documents the two mechanisms in its own
`Documentation/Configuration/Index.rst`, and every package uses the same eight
anchors so a cross-reference written once holds everywhere:

| Anchor                             | Section                                                          |
|------------------------------------|------------------------------------------------------------------|
| `configuration`                    | The chapter itself.                                              |
| `configuration-components`         | What the sets contain, one row per component plus the aggregate. |
| `configuration-hidden-by-default`  | Which content elements are hidden and what brings them back.     |
| `site-set`                         | How to name the set in `config.yaml`.                            |
| `static-templates`                 | The classic mechanism, and the `clear = 3` warning.              |
| `static-typoscript`                | The `sys_template` entries to select.                            |
| `static-pagetsconfig`              | The page TSconfig entries to select.                             |
| `one-mechanism-per-site`           | Why not to combine the two.                                      |

An extension that ships nothing for one of them keeps the anchor and says so in
one line — `academic-base` ships no TypoScript and no content element, and its
`static-typoscript` and `configuration-hidden-by-default` sections say exactly
that. Omitting the section instead is what makes a shared cross-reference
impossible.

## What is not converted yet

`academic-base`, `academic-bite-jobs`, `academic-persons`,
`academic-contact4pages`, `academic-persons-edit`, `academic-jobs` and
`academic-study-plan` follow this layout. Three extensions still ship a single
`Configuration/TypoScript/` folder, a set whose `setup.typoscript` is a one-line
`@import` of it, no page TSconfig registration and no hide-by-default:
`academic-partners`, `academic-programs` and `academic-projects`. The
remaining two, `academic-persons-sync` and `typo3-category-types`, ship neither
a `Configuration/TypoScript/` nor a `Configuration/Sets/` and have nothing to
convert.

Converting the rest is tracked as ACE-458 and happens per extension, each with
its own Breaking changelog entry, because every conversion moves paths that a
site package may `@import`.

`academic-persons` is the one extension where six content elements share a
single `plugin.tx_academicpersons` block. It keeps that block in
`Configuration/TypoScript/Default/` and each component folder names it in an
`include_static_file.txt`, which both delivery paths read — see its own
integrator chapter. The folder deliberately keeps the name `Default`: it is the
value stored in existing `sys_template` records and the path several functional
tests load directly.

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
