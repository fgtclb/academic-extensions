# Upgrade checks

A project that overrides a Fluid template of an academic extension is told
nothing when the extension stops shipping that file. Fluid resolves the first
file it finds below the view root paths and raises nothing when it finds none in
the project's folder — the override simply stops taking part. The project
inventories of the 2026-09 customization analysis count **at least 35 such dead
`academic_persons_edit` overrides in three projects**, all of them written
against the pre-3.0 templates.

`academic_base` ships the console command `academic:upgrade:check` for exactly
that, and this page is about how it decides what to report. The integrator
documentation of the command is
[`packages/fgtclb/academic-base/Documentation/UpgradeCheck/Index.rst`](../../packages/fgtclb/academic-base/Documentation/UpgradeCheck/Index.rst).

## What it compares

`FGTCLB\AcademicBase\Upgrade\TemplateOverrideChecker` takes two absolute folders
and nothing else — no extension, no site, no console. It walks the `*.html`
files below the override folder and answers one question per file:

| Finding            | Meaning                                                                | Exit status |
|--------------------|------------------------------------------------------------------------|-------------|
| `missing-upstream` | No upstream file at that relative path, not even one differing in case | problem     |
| `case-mismatch`    | The upstream file exists under a name differing only in case           | problem     |
| `identical`        | A byte identical copy                                                  | notice      |

A changed copy of a file the extension still ships is **not** reported: it is a
deliberate override that works.

`identical` is a notice, not a problem. A copy that pins the markup of one file
is a legitimate decision, and failing a pipeline on it would make the check
something integrators turn off. It is still worth naming, because such a copy
freezes that file at the version it was taken from.

The case finding exists because the two machines disagree. A macOS development
machine resolves `Pages/Academicprogram.html` against the upstream
`Pages/AcademicProgram.html` and the Linux server does not, so the override
works for whoever wrote it and is dead in production. The checker therefore
builds its upstream index by **reading the directories**, not by asking the file
system for one path at a time — `is_file()` would answer yes on the very machine
the finding exists for.

### `Name.fluid.html` is deliberately not the same template

Fluid 5 (TYPO3 v14) tries `Name.fluid.html` before `Name.html`
(`TemplatePaths::resolveFileInPaths()`). Fluid 4 (TYPO3 v13) does not: it tries
`Name.html` and then `Name` without a format, and nothing else. An override
named `Name.fluid.html` against an upstream `Name.html` is therefore resolved on
TYPO3 v14 and dead on TYPO3 v13.

The checker compares names as they are, so such an override is reported as
`missing-upstream`. That is correct on TYPO3 v13 and conservative on v14, and it
keeps one behaviour on both versions. No file in any academic extension uses the
`.fluid.html` form.

Fluid 5 has a second fallback Fluid 4 does not: it retries the requested name
with `ucfirst()`. That applies to the first character of the *requested name*
only, and the names Extbase generates are already upper case, so it does not
reach the relative paths this check compares — but it is the one place where a
case difference resolves on v14 and not on v13.

## Where the override folders come from

Either from the command line, or from a site.

An override folder is named as an `EXT:` path, or as an absolute path **inside
the project root** — `GeneralUtility::getFileAbsFileName()` refuses anything
else, so a second checkout next to the project cannot be checked, and the
command says so rather than reporting that the folder does not exist.

With `--override-path` the folder is compared with
`EXT:<extension>/Resources/Private/`, which is the shape a project override
folder mirrors, or with the folder `--upstream-path` names when it mirrors a
single one such as `Partials/`. Guessing the upstream root by searching all
upstream folders for a matching file name was rejected: `List.html` and
`Item.html` exist below more than one root.

With `--site=<identifier>` the command builds a frontend environment for the
site's root page through `fgtclb/environment-state-manager` and reads
`plugin.tx<extension without underscores>.view.templateRootPaths`,
`partialRootPaths` and `layoutRootPaths` from the `frontend.typoscript` request
attribute. Each folder is compared with the upstream folder of its own kind, so
`--upstream-path` is not needed there. That is the step the command exists to
remove — reading the root paths of every plugin of every site by hand.

`academic_base` requires `fgtclb/environment-state-manager` for it. It is the
extension that ships the command, so the dependency is real there; the other
eleven academic extensions already require the package, so no installation of
the set gains one. An own frontend bootstrap in `academic_base` was rejected: it
would duplicate the `Core13`/`Core14` split the state manager already carries.

The command only reads. It builds the environment through the state manager's
`execute()`, which restores the previous request, context and singletons in a
`finally` block, so a second command in the same process does not inherit it.

## Which root paths are project overrides

**Not** "every root path except the extension's own". That rule looks right and
is wrong, because a plugin's root paths routinely name folders of *other*
extensions:

```typoscript
plugin.tx_academicpersons.view.partialRootPaths {
  -1 = EXT:academic_base/Resources/Private/Partials/
  0 = EXT:academic_persons/Resources/Private/Partials/
  1 = {$plugin.tx_academicpersons.view.partialRootPath}
}
plugin.tx_academicpersonsedit.view.partialRootPaths {
  0 = EXT:academic_persons/Resources/Private/Partials/
  10 = EXT:academic_persons_edit/Resources/Private/Partials/
  20 = EXT:fluid_styled_content/Resources/Private/Partials/
  30 = {$plugin.tx_academicpersonsedit.view.partialRootPath}
}
```

Under that rule a single `--site` run for `academic_persons_edit` reports the
19 partials of `academic_persons` and the 19 of `fluid_styled_content` as dead
overrides — 38 findings before the first real one, which is how a check gets
switched off.

A root path is therefore treated as **upstream** when it lies inside

- the checked extension itself,
- any package it requires, transitively, or
- a TYPO3 system extension (`MetaData::isFrameworkType()`).

Everything else is a project override folder. The direction matters: a project's
site package *depends on* the academic extensions, never the other way round, so
it is never excluded by this rule.

**The requirements come from the composer manifest, not from the package
metadata.** `MetaData::getConstraintsByType('depends')` is a *derived* list, and
what it holds depends on the core version **and** on how the package was
registered. Measured for `academic_base` — same source, four environments:

| Environment                    | `academic_base` → `depends`                                                                                 |
|--------------------------------|-------------------------------------------------------------------------------------------------------------|
| v13.4 composer artifact        | `php symfony/serializer core extbase` — composer `require`, extension names mapped to keys                  |
| v14.3 composer artifact        | `typo3/cms-core typo3/cms-extbase` — unmapped, `php` and `symfony/serializer` dropped                       |
| v13.4 functional test instance | `core backend extbase environment_state_manager` — from `ext_emconf.php`, the only place `backend` is named |
| v14.3 functional test instance | composer names again                                                                                        |

On top of that, a package registered at runtime on v14 loses a requirement
composer already installed: the fixture extension requiring
`fgtclb/academic-base` gets `['typo3/cms-core']` — the entry this rule needs is
gone in exactly the environment the tests run in.

The spelling differs, the filtering differs, and in the environment the tests
run in the one entry the rule needs can be missing altogether. A package's own
`require` is the same list in all four, so the closure is built from
`getValueFromComposerManifest('require')` and mapped with
`PackageManager::getPackageKeyFromComposerName()` — byte-identical on both
versions, and it returns an unknown name unchanged, so `php`, `ext-*` and
`symfony/serializer` simply fail the following `isPackageActive()` check.

Both methods are annotated `@internal` in `PackageInterface`, on v13 and on v14.
There is no public API for "what does this extension require", so the exclusion
rule uses internal API knowingly, and belongs on the list of things to re-check
for TYPO3 v15.

A root path naming a folder that does not exist is reported with the TypoScript
path that named it and skipped; the other root paths of the site are still
checked, and the run exits with an error status because it did not check
everything it was asked to. The same holds the other way round: a site that
cannot be read at all does not discard the findings of the folders
`--override-path` named, which were checked before it. A site whose TypoScript configures no view
root path at all below `plugin.tx<extension>.view` is rejected the same way:
reporting nothing and succeeding would tell a pipeline the project is clean when
the site was never asked about that extension.

A site *without* a root TypoScript record and without a site set is where the
two core versions part again. TYPO3 v14 refuses to build the frontend
environment for it at all — `No site configuration or TypoScript template record
found!` — while TYPO3 v13 builds it and renders the core's own default
TypoScript, which it delivers to every site since v13.2 (`#103485`). Both end in
an error status, through different branches and with different messages. The
test for the "configures no root path" branch therefore gives its site a root
TypoScript record with unrelated content, which is the only shape that reaches
that branch on both versions.

## What it does not check

- The `page.10` root paths of a site. They are shared with the site's theme, so
  every theme partial would be reported as `missing-upstream`. Page template
  overrides are checked by naming their folder with `--override-path`.
- `plugin.tx_<extension>_<plugin>.view.*RootPaths`. Extbase merges the
  per-plugin block over the extension-wide one, and only the extension-wide
  `plugin.tx_<extension>.view` is read. The nine extensions that ship view root
  paths configure them extension-wide, so nothing *we* ship is missed; a
  project that
  overrides per plugin is, and the "configures no view root path" guard cannot
  warn about it, because the extension-wide block is always populated.
- A site for `academic_base`, `academic_persons_sync` or `category_types`.
  Nine of the twelve extensions ship view root paths; those three ship none, so
  `--site` always ends in "configures no view root path" for them. Their
  template overrides — `academic_base` ships the shared image partial — are
  checked by naming the folder with `--override-path`.
- Static templates, TSconfig imports, site sets and XCLASSes.
- That an upstream file *changed* since the project copied it. The check has no
  record of the version a copy was taken from; `identical` is the only statement
  it can make about a copy.
- Anything that is not a `*.html` file. XLIFF files are overridden through
  `locallangXMLOverride`, not through a view root path.

## Where the code is

| File                                                                            | Contents                                                          |
|---------------------------------------------------------------------------------|-------------------------------------------------------------------|
| `packages/fgtclb/academic-base/Classes/Upgrade/TemplateOverrideChecker.php`     | The comparison, over two folders and nothing else.                |
| `packages/fgtclb/academic-base/Classes/Upgrade/TemplateOverrideFinding.php`     | One file and its finding.                                         |
| `packages/fgtclb/academic-base/Classes/Upgrade/TemplateOverrideFindingKind.php` | The three kinds, and which of them is a problem.                  |
| `packages/fgtclb/academic-base/Classes/Command/UpgradeCheckCommand.php`         | Option parsing, path and site resolution, output and exit status. |

The checker is covered by
`packages/fgtclb/academic-base/Tests/Unit/Upgrade/TemplateOverrideCheckerTest.php`
over two committed fixture folders; the command by
`Tests/Functional/Command/UpgradeCheckCommandTest.php` and
`UpgradeCheckCommandSiteModeTest.php`, whose fixture TypoScript carries the two
shapes the exclusion rule exists for.

## See also

- [Validation settings](validation-settings.md) — the other upgrade aid,
  `academic:persons:migrate-settings`, and the settings file it migrates.
- [TypoScript and site sets](typoscript-and-site-sets.md) — how the view root
  paths reach a site in the first place.
- [Shared partials](shared-partials.md) — why `academic_base` sits in the
  partial root paths of every academic plugin.
- [Dependency injection](dependency-injection.md) — the autoconfiguration that
  registers a `#[AsCommand]` class without further configuration.
