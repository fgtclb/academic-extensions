# TypoScript and site sets

Every extension here ships its frontend TypoScript and its backend page
TSconfig through **two** mechanisms at once: TYPO3 site sets, and the classic
static template plus `tsconfig_includes` registration. An installation picks
one; the extension has to serve both.

The obvious way to do that — a copy of each file per mechanism — is how the
repository used to work, and it drifts. This page describes the layout that
replaces it, the two `config.yaml` keys that make it possible, and the traps
that were found while verifying it against the core source of TYPO3 v12.4.45
and v13.4.34.

**On this branch only one of the two mechanisms exists on both core versions.**
Site sets arrived in TYPO3 v13.1 (Feature: #103437): v12.4.45 has no
`Classes/Site/Set/` at all, nothing in it ever opens `Configuration/Sets/`, and
`Site::__construct()` stores an unknown site configuration key such as
`dependencies` verbatim without looking at it. A shipped set is therefore inert
on v12 — never harmful, and never a delivery path either. Everything a v12
installation gets, it gets through the static half, which is why that half is
not a legacy fallback here but the load-bearing one.

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
`rtrim($set->typoscript, '/') . '/'` into the include, so the separator is
core's job. Writing the slash is a readability choice, not a rule. All of this
is v13 only — the classes quoted here do not exist on v12.

The static half, by contrast, is identical on both versions.
`ExtensionManagementUtility::addStaticFile()` and `registerPageTSConfigFile()`
have the same signature and the same body on v12.4.45 and v13.4.34, and
`SysTemplateTreeBuilder` reads `include_static_file.txt` with
`GeneralUtility::trimExplode(',', …)` and recurses into a nested one on both.
So a component folder and an aggregate `include_static_file.txt` behave the
same way on v12 as they do on v13.

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

`Configuration/page.tsconfig` is auto-loaded on **both** versions — TYPO3 reads
it for every active package since v12.0 (Feature: #96614), and the branch's core
constraint starts at `^12.4.22`. So the global half of hide-by-default works on
v12 unchanged; what is missing there is only the set layer of the merge order.

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
TSconfig still wins over all of them. On v12 the same method has the same order
minus the set layer, so `tsconfig_includes` is the only re-enable there.

The new content element wizard follows along on both supported core versions:
`NewContentElementController` drops every item whose value appears in
`TCEFORM.tt_content.CType.removeItems` before it renders.

### `mod.wizards…show` is not dead on v12

`main` deletes `mod.wizards.newContentElement.wizardItems.<group>.show` as dead
configuration. **Do not do that here.** On v12 the wizard is built from page
TSconfig, not from TCA — the TCA based wizard arrived in v13.0
(Feature: #102834) — and `NewContentElementController` gates every element on
that list:

```php
$showItems = GeneralUtility::trimExplode(',', $wizardGroup['show'] ?? '', true);
$showAll = in_array('*', $showItems, true);
…
if ($itemConf !== [] && ($showAll || in_array($itemKey, $showItems))) {
    $groupItems[$groupKey . '_' . $itemKey] = $this->getWizardItem($itemConf);
```

An element that is defined but not listed is never added. On v13 the same
controller has no reader for the key at all.

The line therefore stays, and it belongs in the **component** file, next to the
element definition it enables — both then arrive through one and the same
include. Keeping the definition global while the `show` entry moves would make
the element unreachable on v12; the other way round it would be offered on
pages that did not ask for it.

## There is no double-parse guard, and that is deliberate

A site that uses *both* mechanisms parses the shared files twice — a v13 only
question, since a v12 site has one mechanism to begin with. Every guard that
could suppress the second parse was written out and rejected:

| Candidate                     | Verdict                                                                                                                                                         |
|-------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `[not ('x' in site('sets'))]` | Broken. `Site::getSets()` returns the site's *declared* `dependencies` verbatim, so a set that arrives transitively never matches.                              |
| The resolved set list         | Unreachable from a condition. It exists only inside `SetRegistry::getSets()`.                                                                                   |
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

The backend's own **Create a root TypoScript record** button writes
`'root' => 1, 'clear' => 3`. On a v13 site that is driven by sets, pressing it
throws the entire set contribution away, and the failure looks like "the
extension ships no TypoScript" rather than like a template record problem. A
v12 site has nothing to lose here.

`FunctionalTestCase::setUpFrontendRootPage()` writes `clear = 3` as well, which
is why the delivery tests of `academic-bite-jobs` insert their `sys_template`
row by hand with `clear = 0` instead of using it.

This is a bigger trap than the double parse. It is also what an installation
in that state recovers from by selecting the static template: the record that
wiped the set contribution can carry the same configuration itself.

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

## Testing it per core version

A test that names `SetRegistry` or `SetDefinition` cannot be analysed against
the v12 core, and `#[Group('not-core-12')]` does not help: the group keeps
PHPUnit from running the test, and does nothing for PHPStan, which analyses
`packages/` wholesale at level 8. `Build/phpstan/Core12/phpstan.neon` excludes
`packages/fgtclb/*/Tests/*/Core12/*`'s counterpart
`packages/fgtclb/*/Tests/*/Core13/*` instead, and that glob accepts exactly
**one** directory level — `Tests/Functional/Core13/SiteSet/…` matches,
`Tests/Functional/SiteSet/Core13/…` does not.

So a site-set test class lives in `Tests/Functional/Core13/` *and* carries
`#[Group('not-core-12')]`; the folder is what satisfies PHPStan and the group is
what satisfies PHPUnit, which still collects that folder. Everything the static
half covers stays in a shared class that runs on both versions — otherwise v12
gets no coverage of the only mechanism it has. `academic-bite-jobs` splits it
that way: `Tests/Functional/SiteSet/StaticTemplateDeliveryTest.php` on both versions,
`Tests/Functional/Core13/SiteSet/SiteSetDeliveryTest.php` on v13 alone, with the
scaffolding they share in an abstract case next to the shared one.

## What is converted

The conversion is ACE-458 and lands per extension, each slice with its own
Breaking changelog entry, because every conversion moves paths that a site
package may `@import`. On this branch it is a backport of the work done on
`main`, and it runs in five slices.

All ten extensions that have configuration to convert now follow this layout:
`academic-base`, `academic-bite-jobs`, `academic-persons`,
`academic-contact4pages`, `academic-persons-edit`, `academic-jobs`,
`academic-study-plan`, `academic-partners`, `academic-programs` and
`academic-projects`. The remaining two, `academic-persons-sync` and
`typo3-category-types`, ship neither a `Configuration/TypoScript/` nor a
`Configuration/Sets/` and have nothing to
convert.

Each slice also renames the page TSconfig directory of the extensions it
converts to `Configuration/TSconfig/`. This branch still carries three spellings
— `TsConfig/`, `TSconfig/` and `TSConfig/` — because that rename was `main` only
until now; the same pull request breaks those paths anyway, so it is done
together with the conversion rather than on its own.

A new extension follows the layout from the start; `academic-bite-jobs` is the
smallest complete example to copy.

Two things the later slices have to keep, which `main` no longer has:

- The three `addUserTSConfig()` guards in the `ext_localconf.php` of
  `academic-partners`, `academic-programs` and `academic-projects`.
  `Configuration/user.tsconfig` is auto-loaded from v13 only, so on v12 that
  call is the only mechanism there is.
- `academic-study-plan` registers its page TSconfig file with
  `registerPageTSConfigFile()`, so the path sits in `pages.tsconfig_includes` of
  real installations. Renaming its directory changes a stored record value and
  needs its own `Important-*.rst`, not just a row in a migration table.

## See also

- [Core version aware code](core-version-aware-code.md) — the other place where
  a difference between v12 and v13 has to be handled explicitly.
- [Monorepo layout](../development/monorepo-layout.md) — which extension lives
  where, and which extension key it ships.
- [Changelog and documentation](../workflow/changelog-and-documentation.md) —
  the integrator-facing counterpart of this page, and where a Breaking entry
  goes.
- [`packages/fgtclb/academic-bite-jobs/Configuration`](../../packages/fgtclb/academic-bite-jobs/Configuration)
  — the reference implementation.
- `.Build/vendor/typo3/cms-core/Classes/Site/Set/YamlSetDefinitionProvider.php`
  and `.../TypoScript/IncludeTree/SysTemplateTreeBuilder.php` — the two classes
  every claim above was read out of. The first one exists in the v13 vendor tree
  only; install the version you are checking a claim for before checking it.
