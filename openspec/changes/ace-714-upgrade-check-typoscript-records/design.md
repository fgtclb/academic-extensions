## Context

- `FGTCLB\AcademicBase\Upgrade\ConfigurationChecker` (ACE-713) reports six
  findings over `sys_template.include_static_file`, `pages.TSconfig`,
  `pages.tsconfig_includes`, a site's `page.tsconfig`, the declared set
  dependencies of every site and the XCLASS registry. It is `final readonly`,
  takes `PackageManager`, `ConnectionPool` and `SiteFinder`, and writes
  nothing.
- It already carries `tsConfigReferences()` (an `@import` and
  `<INCLUDE_TYPOSCRIPT:` line scanner that mirrors `LossyTokenizer`),
  `includeTypoScriptBody()` and `atImportResolves()` (the four shapes of
  `TreeFromLineStreamBuilder::processAtImport()`), both written for
  `fileSuffix` = `tsconfig`.
- `UpgradeCheckCommand` reads `--upstream-path` once and falls back to
  `EXT:<extension>/Resources/Private/`. The option is used only where
  `--override-path` folders are compared.
- `docs/architecture/typoscript-and-site-sets.md` explains why there is no
  double-parse guard, and calls the `clear` flag on a set-driven site "a bigger
  trap than the double parse".

## Goals / Non-Goals

**Goals:**

- Report the silent failures one reference further out than ACE-713 reaches,
  with the same rule: reproduce what core does, and nothing else.
- One more column pair, one more column and one recursion — no second service.

**Non-Goals:**

- Reporting a resolving reference whose *content* changed meaning.
- Following a TypoScript `@import` chain of a `sys_template` record. Only the
  field's own lines are read; see *Risks*.

## Decisions

### Verified: the four premises hold, and a fifth item comes with them

Measured against `core-13/vendor` (v13.4.34) and `core-14/vendor` (v14.3.6):

- **TypoScript fields are tokenized like any other source.**
  `SysTemplateTreeBuilder` line 168-173 tokenizes `constants` or `config` of
  every row and hands the stream to `TreeFromLineStreamBuilder`, whose
  `processAtImport()` drops a target that matches nothing without a word —
  the same method the TSconfig check already mirrors, with `fileSuffix` =
  `typoscript` instead of `tsconfig`.
- **`<INCLUDE_TYPOSCRIPT:` is equally dead there.** The tokenizer is the same
  one, so the syntax v13 deprecated and v14 removed hits a `sys_template`
  field exactly as it hits page TSconfig — and a record field is where that
  syntax was *most* used before `@import` existed. It is therefore reported
  too, as a finding of its own rather than as part of the import finding. This
  is the one item beyond the four the follow-up was opened for; it is the same
  code path, and skipping it would mean tokenizing the field, seeing the dead
  include and saying nothing.
- **A `clear` flag really discards the site set contribution.**
  `SysTemplateTreeBuilder` adds the site include to the root node *before* the
  `sys_template` rows (line 127 vs 184), sets `setClear(true)` on a row whose
  bit for the branch is set (`clear & 1` for constants, `clear & 2` for setup,
  line 177-180), and `IncludeTreeAstBuilderVisitor::visitBeforeChildren()`
  replaces the whole AST with a fresh `RootNode` for such a node. Both blocks
  are byte-identical on v13 and v14.
- **Core's convenience code does not soften it.**
  `$atLeastOneSysTemplateRowHasClearFlag = $siteIsTypoScriptRoot` only
  suppresses the *automatic* clear flag core sets when an integrator set none;
  an explicit flag still wipes.
- **`clear` is a two-bit checkbox**, `Constants` and `Setup`, so the finding
  names the branch rather than the number.

### The site condition is `getSets() !== []`, not `isTypoScriptRoot()`

`Site::isTypoScriptRoot()` answers "sets **or** site TypoScript **or** site
TSconfig", and it is annotated `@internal` on both versions.
`Site::getSets()` is public, and "the site delivers TypoScript through site
sets" is the narrower and correct condition here: the academic extensions
deliver through sets, and a site whose own `typoscript` a record clears is not
this check's business.

### One scanner, two file suffixes

`tsConfigReferences()` becomes suffix-agnostic and is used for both fields, and
`atImportResolves()` becomes `atImportFiles()`, returning the files a reference
resolves to instead of a boolean. "Resolves" is then "the list is not empty",
which the existing callers ask, and the new recursion gets the list it needs
from the same method. The four shapes and their order stay exactly as
`processAtImport()` has them.

Rejected: a second scanner for TypoScript. The syntax is the same; only the
suffix differs, and core passes it as a parameter for the same reason.

### Following a reference that resolves

A page TSconfig reference that resolves is read, and its own references are
scanned, recursively. Two rules keep it honest:

- **Any resolving file is followed, not only an academic one.** The realistic
  case is a project file that exists and imports a renamed academic path —
  `EXT:my_site/Configuration/TSconfig/all.tsconfig` importing
  `EXT:academic_persons/Configuration/TsConfig/page.tsconfig`. Only *academic*
  references are ever reported; which files are read is a different question,
  and TYPO3 reads them all.
- **A `seen` set of absolute paths**, so two files importing each other
  terminate and every unresolved reference is reported once. Core has no such
  guard and recurses; it is protected by the file system rather than by the
  code, and this check must not be.

The finding names the page or the site that leads to the file, plus the file,
because that is where an integrator starts looking.

Rejected: following the TypoScript `@import` chain of a `sys_template` record
as well. The same recursion would work, but a site package's TypoScript tree is
large, the first level is where the 2.4 rename bites, and the cost of reading
every TypoScript file of an installation on every status report render is not
worth the second level. Named as a gap.

### `--upstream-path` without `--override-path` is invalid input

It applies to nothing else. Today it is accepted and ignored, which is the
failure mode this whole command exists to remove. The guard sits next to the
two ACE-713 added, and the message says what to give instead.

### Severity

WARNING for all three new findings. A cleared set branch is sometimes
deliberate — a site whose set contribution was wiped recovers by carrying the
configuration in the record itself, which is exactly the shape
`docs/architecture/typoscript-and-site-sets.md` describes — and so is a
TypoScript import a project keeps for a file it ships itself.

### Finding kinds

`typoscript-import`, `typoscript-syntax` and `set-branch-cleared`. They sit
next to `tsconfig-import`, `tsconfig-syntax` and `set-and-static-template`, and
none of them is longer than the 23 characters `set-and-static-template` already
takes, so the output column does not move.

## Risks / Trade-offs

- [Reading arbitrary project TSconfig files] → read-only, and only files a page
  or a site already leads TYPO3 to.
- [A deep import tree costs file reads on every status report render] → page
  TSconfig trees are small, the `seen` set bounds the work, and the status
  report is not a request path a visitor reaches.
- [A cleared branch is sometimes deliberate] → a warning, and the message names
  the documentation chapter.
- [Only the first level of a record's TypoScript is read] → named in both
  "does not check" lists.

## Migration Plan

None: nothing is stored or migrated.

## Open Questions

None.
