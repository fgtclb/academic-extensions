# Upgrade checks

A project that overrides a Fluid template of an academic extension is told
nothing when the extension stops shipping that file. Fluid resolves the first
file it finds below the view root paths and raises nothing when it finds none in
the project's folder — the override simply stops taking part. The project
inventories of the 2026-09 customization analysis count **at least 35 such dead
`academic_persons_edit` overrides in three projects**, all of them written
against the pre-3.0 templates.

The same silence covers the configuration an installation stores. TYPO3 skips a
static template folder that holds no TypoScript, an `@import` that matches no
file and a `tsconfig_includes` entry whose file is gone without a word, so an
upgrade that renamed a folder leaves an installation that looks configured and
is not. All six analysed projects had found such configuration by hand.

`academic_base` ships the console command `academic:upgrade:check` for both, and
this page is about how it decides what to report. The integrator documentation
of the command is
[`packages/fgtclb/academic-base/Documentation/UpgradeCheck/Index.rst`](../../packages/fgtclb/academic-base/Documentation/UpgradeCheck/Index.rst).

The command runs **two check groups**. The configuration group takes no argument
and reads the whole installation, so it runs on every invocation. The template
override group has to be pointed at something and runs when an extension key is
named. The exit status is the one of the whole run.

## The template override group

### What it compares

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

#### `Name.fluid.html` is deliberately not the same template

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

### Where the override folders come from

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

### Which root paths are project overrides

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

### What it does not check

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
- That an upstream file *changed* since the project copied it. The check has no
  record of the version a copy was taken from; `identical` is the only statement
  it can make about a copy.
- Anything that is not a `*.html` file. XLIFF files are overridden through
  `SYS.locallangXMLOverride` on TYPO3 v13 and `LANG.resourceOverrides` on v14,
  or through `_LOCAL_LANG` in TypoScript, not through a view root path.

## The configuration group

`FGTCLB\AcademicBase\Upgrade\ConfigurationChecker` takes no argument. It reads
the `sys_template` records, the page TSconfig of the `pages` table and of the
site configurations, the declared set dependencies of every site and the XCLASS
registry, and answers with a list of findings:

| Finding                   | Severity         | What it means                                                                        |
|---------------------------|------------------|--------------------------------------------------------------------------------------|
| `static-template`         | warning          | A TypoScript record includes an academic static template that delivers no TypoScript |
| `tsconfig-import`         | warning          | A page or a site references an academic page TSconfig file that is not there         |
| `tsconfig-syntax`         | warning          | A page or a site uses `<INCLUDE_TYPOSCRIPT:`, which TYPO3 v14 no longer reads        |
| `typoscript-import`       | warning          | The Constants or Setup field of a TypoScript record imports a file that is not there |
| `typoscript-syntax`       | warning          | The same field uses `<INCLUDE_TYPOSCRIPT:`                                           |
| `set-branch-cleared`      | warning          | A record on a set-driven site's root page clears a branch those sets deliver         |
| `alias-set`               | notice           | A site depends on a set that only forwards to another one                            |
| `unavailable-set`         | error            | A site depends on an academic set TYPO3 cannot provide                               |
| `set-and-static-template` | warning          | A site delivers one extension through a set and through a static template            |
| `xclass`                  | warning or error | An academic class is replaced through the XCLASS registry                            |

Everything the group looks at belongs to an academic extension: an `EXT:` path
below `academic_*` or `category_types`, a set of one of those packages, a class
below `FGTCLB\Academic…` or `FGTCLB\CategoryTypes\`. A project's own static
template, TSconfig import or XCLASS of a core class is none of its business.

### Each check answers the question core answers, the way core answers it

The value of a "this is dead" statement is that it is not a guess, so every
check reproduces the condition the core code applies and nothing else.

**Static templates** — core reads a stored `include_static_file` value in
`SysTemplateTreeBuilder::handleSingleIncludeStaticFile()`. It returns without a
word when the extension is not loaded, and it contributes nothing when the
folder holds neither `include_static_file.txt` nor a `constants` or `setup` file
in one of the three suffixes `.typoscript`, `.ts` and `.txt`. Those two are the
finding. Comparing the stored value with the items TCA registers for
`sys_template.include_static_file` was rejected: that list is what the *select
field offers*, and a value that is not in it still works as long as the files
are there.

The rows are read with the default restrictions — deleted, hidden, start and end
time — because that is the container `SysTemplateRepository` uses for a request
without a preview. A hidden record delivers nothing to anybody, so there is
nothing about it to fix.

**The TypoScript of a record** — the same rows, two more columns. Core
tokenizes `constants` and `config` in
`SysTemplateTreeBuilder::getTreeBySysTemplateRowsAndSite()` and hands the stream
to the same `TreeFromLineStreamBuilder` that reads page TSconfig, so an
`@import` matching no file is dropped there exactly as silently.

**The suffix is a list, not a single value.**
`TreeFromLineStreamBuilder::$atImportTypeToSuffixMap` maps `constants` and
`setup` to `['typoscript']` and **`tsconfig` to `['typoscript', 'tsconfig']`**,
and `buildTreeInternal()` calls `processAtImport()` once per allowed suffix. So
a page TSconfig `@import` of a `.typoscript` file resolves. The record's list is
a *subset* of the page TSconfig one, so the asymmetry runs one way only: a
folder holding nothing but `.tsconfig` files resolves for page TSconfig and
delivers nothing to a TypoScript record. The checker loops the same lists. Each
pass yields only files ending in its own suffix, so the two sets are disjoint —
no file is read twice, and none is missed.

This is the case the 2.4 entry
`academic-persons/Documentation/Changelog/2.4/Breaking-SiteSetsAndStaticTemplatesRestructured.rst`
describes in its own Impact section: "A site package that imported one of the
removed files by path fails to resolve it. `@import` of a missing file is
silent, so this shows up as missing configuration rather than as an error
message."

Only the fields themselves are read, not the files they import — see *What it
does not check*.

**A cleared set branch** — `SysTemplateTreeBuilder` adds the site include to the
root node *before* the `sys_template` rows, marks a row whose bit for the branch
is set as "clear" (`clear & 1` for Constants, `clear & 2` for Setup), and
`IncludeTreeAstBuilderVisitor::visitBeforeChildren()` replaces the whole AST with
a fresh `RootNode` for such a node. So on a site driven by sets the record
discards everything those sets contributed to that branch, and the failure looks
like "the extension ships no TypoScript" rather than like a template record
problem — which is why
[TypoScript and site sets](typoscript-and-site-sets.md#the-clear--3-trap) calls
it a bigger trap than the double parse. The backend's own **Create a root
TypoScript record** button writes both bits.

The site condition is `Site::getSets() !== []`, not `Site::isTypoScriptRoot()`.
The latter also answers true for a site that only carries TypoScript of its own,
which is none of this check's business, and it is `@internal` on both versions
while `getSets()` is not.

Core's convenience code
(`$atLeastOneSysTemplateRowHasClearFlag = $siteIsTypoScriptRoot`) does not
soften this: it only suppresses the flag core sets *automatically* when an
integrator set none.

**Page TSconfig** — core drops a `tsconfig_includes` entry whose file is gone in
`TsConfigTreeBuilder::getRootlinePageTsConfigTree()`, and an `@import` that
matches nothing in `TreeFromLineStreamBuilder::processAtImport()`. The check
reproduces both, including the four shapes an `@import` can take: an exact
`.tsconfig` file, a folder, a file name without its suffix, and one `*` wildcard
in the file name. Only the relative lookup of that method is left out — it needs
the path of the *file* an import stands in, and page TSconfig from the database
has none, so a relative import there never resolves in core either.

Running the core tokenizer over the stored string instead was rejected. It
answers with a line stream, and turning that into resolved files means building
the include tree — which is the very step that drops a missing file silently.

The `pages` rows are read with the deleted restriction only, not with the
default container: page TSconfig of a **hidden** page is read all the same,
because `BackendUtility::getPagesTSconfig()` walks a rootline that a hidden page
is part of. The two tables are therefore queried with different restrictions,
and that difference is deliberate.

**A selected file and an imported one are not resolved the same way.** A value
of `tsconfig_includes` goes through one exact file lookup —
`TsConfigTreeBuilder::getContentOfTsconfigFile()` on v14, the same logic inlined
in `getRootlinePageTsConfigTree()` on v13: an `EXT:` path, an active extension, a
canonical path that stays inside it, and the file exists. No folder, no
wildcard, no appended suffix, and no suffix requirement at all, so a selected
`.txt` reads fine and a selected folder reads nothing. The four `@import` shapes
apply to an `@import` and to nothing else, and using them for a selected value
would report the contents of a folder TYPO3 never opens.

A selected *folder* gets a finding of its own. Core guards the lookup with
`file_exists()`, which a directory passes, and then calls `file_get_contents()`
on it: nothing is read, and PHP warns while nothing is read — in the backend as
much as in the frontend, since `BackendUtility::getPagesTSconfig()` resolves the
same values.

**A reference that resolves is followed.** TYPO3 reads the imports inside a file
it includes, so a page whose own import is sound can still end up with
configuration that does not resolve — the realistic shape is a project file that
exists and imports an academic path a release renamed. Any resolving file is
read, not only an academic one; only *academic* references are ever reported.
The finding names the file as an `EXT:` path rather than the expression that led
to it — a folder import resolves to several files, and naming the folder would
leave the integrator to find which of them carries the line — together with the
page or site that leads TYPO3 there, because that is where they start.

Two files importing each other terminate through a `seen` set keyed by the
absolute file name. Core has no such guard and is protected by the file system
rather than by its code; a check must not be. Behind it sits a depth bound of
25, which exists for the case where the `seen` set breaks: without it a cycle
exhausts the memory limit and takes the process down, so the defect would report
itself as a fatal error in the backend status report of an installation nobody
can debug. With it the same defect produces too many findings — a defect that
can be seen, and tested.

A site's `page.tsconfig` is the third place page TSconfig is stored, and TYPO3
v13 and v14 read it alike — `TsConfigTreeBuilder::getSitePageTsConfigTree()`
takes it from `Site::getTSconfig()->pageTSconfig`, which
`SiteConfiguration` fills from `config/sites/<identifier>/page.tsconfig`. It is
scanned like a page's own TSconfig.

**`<INCLUDE_TYPOSCRIPT:` is reported whether or not the file is there.** TYPO3
v13 deprecated the syntax and TYPO3 v14 removed it
(`Breaking-105377-DeprecatedFunctionalityRemoved`): its `LossyTokenizer` turns
such a line into an invalid line and reads nothing. So on one of the two
supported core versions the line is dead regardless of its target, and the fix —
write it as `@import` — is the same either way. This is the one check that does
not ask whether a file exists.

**Alias sets** — `fgtclb/academic-persons-default` and
`fgtclb/academic-study-plan-default` are named in a constant of the checker. A
set definition has no machine readable "this is an alias" key, and introducing a
custom `config.yaml` key for two sets that go away in 4.0 buys nothing. The
notice is for an alias that still delivers. An alias TYPO3 cannot provide - its
extension is not installed, or a later release dropped it - fails the site, so
it is reported as an unavailable set instead. The change that drops the two in
4.0 adds them to the checker's list of removed sets.

**Unavailable sets** — the one site check core does not keep quiet about.
`SiteConfiguration::determineInvalidSets()` marks a declared set that
`SetRegistry` does not know, or that it registered as invalid, and
`SiteResolver` answers every frontend request of the site with HTTP 500 ("Site
… depends on unavailable sets"), on v13 and v14 alike. It is reported anyway,
because the command runs before an upgraded installation goes live, and the
site does not. `SetRegistry::hasSet()` and `getInvalidSets()` are public API
on both versions. A declared set counts when either end of its chain is
academic: an academic set that is missing, invalid for any reason or misses a
set of another vendor, and a set of another vendor - a site package set,
typically - that misses an academic one. A site package set that depends on
`fgtclb/academic-programs-content-load`, which 3.0 removed, is as unavailable as
the removed set itself. `SetRegistry::checkMissingDependencies()` records the
path below the declared set as `b[c[missing]]`, and the check names the
innermost set. Only the two ends are looked at; a foreign set that reaches a
missing foreign set through an academic one is not reported, which is
theoretical while no academic set depends on a set of another vendor. Removed sets
are listed in a constant of the checker with what replaces them, so the message
says what to do; any other academic set gets the generic message.

**A set and a static template of one extension** — the set-to-extension map is
built from the `Configuration/Sets/*/config.yaml` files of the active academic
packages, the same files core reads. Deriving the extension key from the set
name was rejected, because the names do not map mechanically:
`fgtclb/academic-contacts4pages` ships from the directory
`academic-contact4pages` with the extension key `academic_contacts4pages`.

Only the **declared** dependencies of a site are compared, and only the
TypoScript records on the site's **root page**. A set reached through another
set is a decision of whoever wrote the set that pulls it in, and a record below
the root page is not the double parse
[TypoScript and site sets](typoscript-and-site-sets.md#there-is-no-double-parse-guard-and-that-is-deliberate)
warns about. It is a warning rather than an error, because the combination is
sometimes deliberate — a site whose set contribution a `clear` flag wiped
recovers exactly by selecting the static template.

**XCLASS** — the keys of `$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']` whose
class name starts with `FGTCLB\Academic` or `FGTCLB\CategoryTypes\`. Only the
**replaced** class is ever reflected, never the replacement: loading a subclass
of a class the upgrade removed is a fatal error, and a check that reports the
problem by dying of it helps nobody. A replaced class that is final is an error,
one that no longer exists is an error, anything else is a warning.

The class name prefix is used rather than the autoload configuration of the
active packages, because the interesting case is an XCLASS of a class 3.0
*removed* — and that class is in no active package any more.

### Where the findings are shown

In the status report of EXT:reports, and on the command line. One implementation
feeds both, which is why the checks are a service of their own rather than code
inside the status provider: a command has to work on an installation without
EXT:reports.

`FGTCLB\AcademicBase\Report\UpgradeConfigurationStatus` implements an
interface that only EXT:reports ships, so it is kept out of the resource load of
`Configuration/Services.yaml` and registered by a compiler pass in the new
`Configuration/Services.php` — the same pattern, and the same comment, as
`academic-persons/Configuration/Services.php`. Symfony reflects every service
class while it compiles the container, so registering the provider where
EXT:reports is absent is a fatal error during the container build.

That guard cannot be shown to fail in a functional test. "Absent" means absent
from the autoloader — a classic installation, or a Composer project that removed
`typo3/cms-reports` — and a functional test instance always has the package in
`.Build/vendor`, so the class stays loadable whether or not the extension is
active. The registration is covered in the other direction instead: with
EXT:reports active, exactly one provider of this class is in the status
registry.

The finding texts are English in both places, built in the checker. They are
made of record uids, stored paths and class names and are read next to the
command output; one wording for both is worth more here than a translated one
for each. Only the provider label and the "nothing stale" entry are language
references.

### What it does not check

- Static templates, TSconfig imports and XCLASSes of a project's own extensions
  or of the TYPO3 core. The check is about what the academic extensions no
  longer deliver.
- The files a TypoScript record's `@import` leads to. The two fields are read,
  their imports are resolved, and the recursion stops there. Reading the whole
  TypoScript tree of an installation on every status report render is not worth
  the second level, and the first one is where a renamed path bites. Page
  TSconfig *is* followed, because those trees are small.
- **More than one TypoScript record per page.**
  `SysTemplateRepository::getSysTemplateRowsByRootline()` keeps one row per pid
  — ordered `root DESC, sorting ASC` — and only along the rootline of the page
  being rendered. This check reads every visible row of the table instead, so a
  second record on a page, or a record on a page no site reaches, is reported
  although core never tokenizes it. The pre-existing `static-template` and
  `set-and-static-template` checks share that simplification; resolving a
  rootline per record is a larger step than the finding is worth.
- **A relative `@import` inside a file that is followed.** Core sets the path of
  a node it built from a file, so `@import './Other.tsconfig'` resolves there;
  this check does not reproduce the relative lookup. A relative path is never an
  academic one, so the cost is a chain that is not walked to its end, never a
  false finding.
- **A non-academic `<INCLUDE_TYPOSCRIPT:`.** On TYPO3 v13 core still reads it,
  so a project file included that way is not followed. On v14 the syntax reads
  nothing at all, which is what the `tsconfig-syntax` finding is about.
- A file whose name TYPO3's `fileDenyPattern` refuses is skipped here as it is
  there, in all four `@import` shapes. Core contributes nothing for such a file,
  so an import naming one is **dead** and is reported as such rather than
  counted as resolved.
- User TSconfig. No academic extension ships any.
- Whether a *resolving* import still delivers what it used to. The check answers
  "does TYPO3 read this", not "does it still mean the same".
- The four 2.x static template paths of `academic_bite_jobs`,
  `academic_contacts4pages`, `academic_persons_edit` and `academic_study_plan`.
  They are kept, delivered and deprecated until 4.0, so they hold TypoScript and
  are correctly not reported.

## Where the code is

| File                                                                            | Contents                                                          |
|---------------------------------------------------------------------------------|-------------------------------------------------------------------|
| `packages/fgtclb/academic-base/Classes/Upgrade/TemplateOverrideChecker.php`     | The comparison, over two folders and nothing else.                |
| `packages/fgtclb/academic-base/Classes/Upgrade/TemplateOverrideFinding.php`     | One file and its finding.                                         |
| `packages/fgtclb/academic-base/Classes/Upgrade/TemplateOverrideFindingKind.php` | The three kinds, and which of them is a problem.                  |
| `packages/fgtclb/academic-base/Classes/Upgrade/ConfigurationChecker.php`        | The six configuration checks, over the installation.              |
| `packages/fgtclb/academic-base/Classes/Upgrade/ConfigurationFinding.php`        | One piece of stale configuration, with its severity.              |
| `packages/fgtclb/academic-base/Classes/Upgrade/ConfigurationFindingKind.php`    | The six kinds, and the title the status report groups them by.    |
| `packages/fgtclb/academic-base/Classes/Report/UpgradeConfigurationStatus.php`   | The status report entries, one per finding.                       |
| `packages/fgtclb/academic-base/Configuration/Services.php`                      | The compiler pass that registers the provider with EXT:reports.   |
| `packages/fgtclb/academic-base/Classes/Command/UpgradeCheckCommand.php`         | Option parsing, path and site resolution, output and exit status. |

The checker is covered by
`packages/fgtclb/academic-base/Tests/Unit/Upgrade/TemplateOverrideCheckerTest.php`
over two committed fixture folders; the command by
`Tests/Functional/Command/UpgradeCheckCommandTest.php` and
`UpgradeCheckCommandSiteModeTest.php`, whose fixture TypoScript carries the two
shapes the exclusion rule exists for.

The configuration checks are covered by
`Tests/Functional/Upgrade/ConfigurationCheckerTest.php` and
`ConfigurationCheckerSiteTest.php`, the status provider by
`Tests/Functional/Report/UpgradeConfigurationStatusTest.php` and
`UpgradeConfigurationStatusWithoutReportsTest.php`, and the command group by
`Tests/Functional/Command/UpgradeCheckCommandConfigurationTest.php`. The fixture
extension `academic_test_configuration` stands in for an academic extension
after an upgrade: it ships one static template folder that still holds
TypoScript and one that does not, one page TSconfig file, one set and one class
that is not final.

## See also

- [Validation settings](validation-settings.md) — the other upgrade aid,
  `academic:persons:migrate-settings`, and the settings file it migrates.
- [TypoScript and site sets](typoscript-and-site-sets.md) — how the view root
  paths reach a site in the first place.
- [Shared partials](shared-partials.md) — why `academic_base` sits in the
  partial root paths of every academic plugin.
- [Dependency injection](dependency-injection.md) — the autoconfiguration that
  registers a `#[AsCommand]` class without further configuration.
