## Context

- `academic-persons/Classes/Report/LegacySettingsStatus.php` is the one status
  provider in the repository. It implements an EXT:reports interface, so it is
  kept out of the resource load of `Services.yaml` and registered by a compiler
  pass in `academic-persons/Configuration/Services.php` only when the
  EXT:reports `StatusRegistry` definition exists.
- `academic_base` configures its services in `Configuration/Services.yaml`
  only; it has no `Services.php` yet.
- The `academic:upgrade:check` command **exists on main** since ACE-712
  (`cbfbb9135`, candidate `cross-cutting-08`), with a required `extension`
  argument, `--override-path`, `--upstream-path` and `--site`. This change adds
  a second check group to it and makes the argument optional.
- On main every extension keeps page TSconfig under `Configuration/TSconfig/`,
  and the persons and study plan alias sets live in `Sets/Default/`. Every 2.x
  release up to 2.3.4 ships `Configuration/TsConfig/` and set folders such as
  `Sets/AcademicPersonsDefault/`, so an import written against 2.x paths does
  not resolve on 3.0.
- `fgtclb/academic-persons-default` and `fgtclb/academic-study-plan-default`
  are alias sets without payload, kept so that existing site configurations
  keep working (`Sets/Default/config.yaml` of both extensions).
- `docs/architecture/typoscript-and-site-sets.md` explains why there is no
  double-parse guard and asks for one mechanism per site; the integrator
  chapter it describes carries the `one-mechanism-per-site` label.

## Goals / Non-Goals

**Goals:**

- One implementation of the five checks, used by the status report and by the
  command.
- Read-only, and the same code path on v13 and v14.

**Non-Goals:**

- Building a frontend context to render the TypoScript of a site.
- Checking static templates or imports of project extensions.

## Decisions

### One stateless checker, two thin consumers

`FGTCLB\AcademicBase\Upgrade\ConfigurationChecker` is a `final readonly`,
autowired service returning a list of `ConfigurationFinding` value objects
(severity, subject, message, optional documentation label).
`FGTCLB\AcademicBase\Report\UpgradeConfigurationStatus` maps them to status
entries, and the command check group prints them and derives the exit status.

Rejected: the logic inside the status provider, as `LegacySettingsStatus`
does it. The command could not use it on an installation without EXT:reports.

### The status provider exists only with EXT:reports

A new `academic-base/Configuration/Services.php` carries the same compiler
pass as the persons one, and `Services.yaml` excludes `Classes/Report/` from
its resource load.

Rejected: `#[Autoconfigure]` on a class inside the resource load. The class
implements an interface of EXT:reports and fails to load when that extension
is missing.

### Static templates: ask what core asks

The checker reads `include_static_file` of every `sys_template` row (query
builder with the default restrictions, ordered by `uid`), keeps the values
that start with `EXT:academic_` or `EXT:category_types`, and reports a value
whose extension is not active, or whose folder holds none of the files
`SysTemplateTreeBuilder::handleSingleIncludeStaticFile()` reads:
`include_static_file.txt`, or `constants`/`setup` in one of the three suffixes
`.typoscript`, `.ts` and `.txt`. **Verified against the installed cores**, and
the method is byte-identical on v13.4.34 and v14.3.6.

Rejected: building the site TypoScript and looking for missing keys. It needs
a frontend context per site in a backend or CLI request and still would not
say which stored value is dead.

Rejected after verification: comparing the stored value with the items TCA
registers for `sys_template.include_static_file`, which is what this design
said before. That list is what the *select field offers*; a value that is not
in it still works as long as the files are there, and every value our
extensions removed also lost its folder. The file criterion is what core
actually applies.

### Decided: the 2.x paths stay, the check covers the rest

The 2.x static template paths of bite_jobs, contacts4pages, persons_edit and
study_plan stay until 4.0, delivered and registered as deprecated by
`ace-tbd-legacy-typoscript-paths`, so the static template check reports none
of them. A silently dropped include is worse than a deprecated working one.
The check keeps its value for other dead static template values, dead
`TsConfig/` imports, renamed set folders, alias sets, a set combined with a
static template, and XCLASSes of final classes.

### Verified: the restrictions differ per table, on purpose

`sys_template` is read with the default restriction container - deleted,
hidden, start and end time - because that is what `SysTemplateRepository` uses
for a request without a preview. `pages` is read with the deleted restriction
only, because page TSconfig of a *hidden* page is read all the same:
`BackendUtility::getPagesTSconfig()` walks a rootline a hidden page is part of.

### Verified: `<INCLUDE_TYPOSCRIPT:` is reported unconditionally

TYPO3 v13 deprecated the syntax and **TYPO3 v14 removed it**
(`Breaking-105377-DeprecatedFunctionalityRemoved`): `LossyTokenizer` turns such
a line into an invalid line and reads nothing at all. An academic include in
that syntax is therefore dead on one of the two supported versions whether or
not its target exists, and the fix - write it as `@import` - is the same in
both cases. It gets a finding kind of its own, `tsconfig-syntax`, and is the
one check that does not ask whether a file exists.

This was not in the proposal. It was found while verifying the premise that
nothing reports an unresolved academic `@import`, and it is the same class of
silent failure, in the same lines of the same field.

### Decided: path and database based, no frontend context

The five checks read `sys_template`, `pages.TSconfig` and
`pages.tsconfig_includes`, the set `config.yaml` files and the XCLASS
registry, and none of them needs rendered TypoScript. The configuration group
therefore builds no frontend context. The `--site` mode that
`ace-712-upgrade-check-template-overrides` adds to the command, through
`fgtclb/environment-state-manager`, stays available to this group: a future
check that has to inspect the TypoScript of a site uses it rather than
building a context of its own.

### TSconfig: resolve the references, do not parse TSconfig

Lines of `pages.TSconfig` holding `@import` or `<INCLUDE_TYPOSCRIPT:` with an
academic `EXT:` path, and every academic value of `pages.tsconfig_includes`,
are resolved against the installed packages. A reference that resolves to no
file, or a wildcard that matches none, is a finding. Only rows with a
non-empty `TSconfig` or `tsconfig_includes` are read, ordered by `uid`.

Rejected: running the core TSconfig parser per page. It skips a missing file
silently, which is exactly the defect to report.

**Verified: a site-level `page.tsconfig` is read on both versions**, so it is
included. `TsConfigTreeBuilder::getSitePageTsConfigTree()` takes its content
from `Site::getTSconfig()->pageTSconfig` on v13.4.34 (line 253) and v14.3.6
(line 261), and `SiteConfiguration` fills that from
`config/sites/<identifier>/page.tsconfig` on both. The check scans it like a
page's own TSconfig and names the site as the subject.

### Alias sets: a constant list

The two alias names are a constant of the checker. The set definitions carry
no machine-readable alias marker, and a custom `config.yaml` key for two sets
that go away in 4.0 is not worth introducing.

### Set plus static template: map sets to extensions from their files

The set-to-extension map is built from the `Configuration/Sets/*/config.yaml`
files of the active academic packages, the same files the core reads. The
site's direct set dependencies are compared with the extension keys of the
static templates stored in `sys_template` rows on the site's root page.

Rejected: deriving the extension key from the set name. The names do not map
mechanically (`fgtclb/academic-contacts4pages` ships from
`academic-contact4pages`, extension key `academic_contacts4pages`).

Rejected: walking the rootline of every page for TypoScript records. Records
below the root page are rare, and the double parse the documentation warns
about is the root page case.

The map holds every set of an academic package, including a payload-free
forwarder and a `pagets`-only set such as `fgtclb/academic-base-ctype-group`.
For such a pair the message "the set is parsed first, so the record can reset a
constant" would not be true - there is no constant. It is unreachable today,
because the only extensions that register a static template are the ones whose
sets carry TypoScript (`academic_base` registers none), so the case is recorded
rather than guarded.

### XCLASS: reflect the original class only

Keys of `$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']` in the
`FGTCLB\Academic` and `FGTCLB\CategoryTypes` namespaces are reflected; a
`final` original is an error, anything else a warning. The XCLASS itself is
never loaded, so a broken one cannot take the check down with it.

### Severity

`ContextualFeedbackSeverity` exists on v13 and v14: NOTICE for alias sets,
ERROR for an XCLASS of a final class **and for an XCLASS of a class the
installed version no longer ships** - that one is a fatal error as soon as
anything loads the replacement - WARNING for everything else. Warnings rather
than errors, because deliberate combinations exist.

### Decided: the finding texts are English in both places

The messages are built in the checker and printed unchanged by the command and
by the status provider. They are made of record uids, stored paths and class
names and are read next to the command output, so one wording for both is worth
more than a translated one for each. Only the provider label and the "nothing
stale" entry are language references, which is what the status report needs.

### Report layout

Guessed layout — a sketch, not a design:

```text
Reports > Status report
Academic extensions: upgrade configuration
 [!] sys_template 1 includes EXT:academic_jobs/Configuration/TypoScript/Old
     - the installed extension delivers no TypoScript there
 [!] Page 12 imports EXT:academic_base/Configuration/Sets/
     AcademicBaseCTypeGroup/page.tsconfig - file not found
 [i] Site "main" depends on fgtclb/academic-persons-default
     - depend on fgtclb/academic-persons instead
 [!] Site "main" uses the set and the static template of academic_jobs
     - see "One mechanism per site"
```

### Decided: the extension argument becomes optional, the group always runs

`academic:upgrade:check` is the installation's upgrade check, and the
configuration group needs no argument, so it runs on every invocation and the
`extension` argument becomes `InputArgument::OPTIONAL`. An extension key still
has to be given together with `--override-path` or `--site`, and those options
still have to be given together with an extension key; every invocation that was
valid before stays valid, with the configuration section added to its output.
The exit status is the one of the whole run.

Rejected: a `--group` option, or a second command. Both let a pipeline that was
written for ACE-712 keep reporting "clean" for an installation whose stored
configuration is not.

## Risks / Trade-offs

- [Deliberate combinations are flagged] → warnings, not errors, and each
  message links the documentation.
- [A set reached only through a project's own set is missed] → only direct
  dependencies are compared; the documentation says so.
- [Large `pages` tables] → only rows with a non-empty TSconfig column are
  read.
- [The command group depends on another change] → the status provider is
  useful on its own; the command task is ordered after
  `ace-712-upgrade-check-template-overrides`.

## Migration Plan

None: nothing is stored or migrated. After deployment the status report shows
the findings, and the integrator fixes the project configuration.

### Not observable: the compiler pass guard itself

`UpgradeConfigurationStatus` implements an interface only EXT:reports ships, and
Symfony reflects every service class while it compiles the container - so
registering the provider where EXT:reports is *absent from the autoloader* is a
fatal error during the container build. That is a classic installation or a
Composer project that removed `typo3/cms-reports`, and a functional test
instance always has the package in `.Build/vendor`. Removing the guard therefore
changes nothing observable in the suite, which was measured rather than assumed:
the container answers `has()` with `false` in both variants, because the private
service is removed as unused either way.

The registration is covered in the other direction instead - with EXT:reports
active, exactly one provider of this class is in the status registry - and the
without-reports test asserts what is true and observable there: the check and
the command work.

## Open Questions

None.
