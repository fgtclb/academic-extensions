## Context

Verified on `main` in `packages/fgtclb/academic-programs`:

- `Resources/Private/Pages/AcademicProgram.html` ends with
  `<f:cObject typoscriptObjectPath="styles.content.getContent"/>`, which
  throws when the path is undefined.
- `styles.content.getContent` is defined only in
  `Configuration/TypoScript/ContentLoad/setup.typoscript`, delivered by the
  set `fgtclb/academic-programs-content-load`
  (`Configuration/Sets/ContentLoad/config.yaml`, whose comment documents the
  exception) and by the static template
  `Academic Programs: Content load override`
  (`Configuration/TCA/Overrides/sys_template.php`). The split came with
  `05bc244ae` (ACE-458). The aggregate set `fgtclb/academic-programs` depends
  on it; the component sets do not.
- On tag `2.3.4` the override sits in
  `Configuration/TypoScript/Content/ContentLoad.typoscript` and is always
  included.
- `composer.json` requires `typo3/cms-frontend` but not
  `typo3/cms-fluid-styled-content`. That does not leave `styles.content.get`
  undefined, contrary to the first version of this design: `cms-frontend`
  adds it to the default TypoScript itself (`ext_localconf.php`,
  `addTypoScriptSetup()`, a `CONTENT` of colPos 0 ordered by `sorting`) on
  13.4.35 and 14.3.7. It is a global object all the same, so rendering it
  would not let an integrator adjust program pages alone.
- The page object is the site package's.
  `Configuration/TypoScript/Page/AcademicPrograms.typoscript` refines
  `page.10` inside
  `[page && traverse(page, "doktype") == 20]` for both `FLUIDTEMPLATE`
  (`templateRootPaths`) and `PAGEVIEW` (`paths`) integrations;
  bootstrap_package 15 uses `FLUIDTEMPLATE`. Both content objects assign
  `variables` (`FluidTemplateContentObject`, `PageViewContentObject`, cms-frontend
  13.4).
- `Tests/Functional/Pages/AcademicProgramPageTemplateTest.php` includes the
  content-load setup explicitly, so the exception is not covered.
  `Tests/Functional/SiteSet/SiteSetDeliveryTest.php` and
  `Tests/Functional/Tca/StaticRegistrationTest.php` cover the set and the
  static template.
- `academic_partners` and `academic_projects` ship an identical
  `Configuration/Sets/ContentLoad` set, and their page templates carry the
  same call.
- `SetRegistry::getSets()` returns only registered sets, but a removed set is
  **not** dropped silently, contrary to the first version of this design:
  `SiteConfiguration::determineInvalidSets()` marks a declared set that
  `SetRegistry::hasSet()` does not know as `notFound`, and `SiteResolver`
  answers every frontend request of that site with HTTP 500 ("Site <id>
  depends on unavailable sets: ..."). A site package set that depends on the
  removed set is registered as invalid (`missingDependency`) and fails the
  site the same way. Verified on 13.4.35 and 14.3.7, and by a functional test
  that asked for such a site.
- Branch `2` (2.4.x-dev, unreleased) has the same split (`c7730da11`,
  ACE-458) and the same page template call, so a site there that leaves out
  the content-load set gets the same exception; its 2.4 Breaking entry warns
  about it. Only the released 2.3.4 always includes the override.
- `academic:upgrade:check` of `academic_base` already reports a stored static
  template whose folder holds no TypoScript (`static-template`) and an
  `@import` of a missing file in a TypoScript record (`typoscript-import`),
  but no site dependency on a set TYPO3 cannot provide.

## Goals / Non-Goals

**Goals:**

- The program page type works with any combination of the programs sets.
- One mechanism for `FLUIDTEMPLATE` and `PAGEVIEW` page objects.
- No site wide redefinition of `styles.content.getContent` shipped by
  `academic_programs` any more.

**Non-Goals:**

- The layout and the section partials of the page; that is
  `ace-tbd-program-page-layout-and-sections`, which renders this variable in
  its content partial.
- The content-load sets of `academic_partners` and `academic_projects`; see
  the removal decision below.

## Decisions

### A scoped `CONTENT` variable on the program page object

Inside the doktype 20 condition of `AcademicPrograms.typoscript`:

```typoscript
variables {
  programContent = CONTENT
  programContent {
    table = tt_content
    select {
      orderBy = sorting, uid
      where = {#colPos}=0
    }
  }
}
```

The template renders `{programContent -> f:format.raw()}`. `CONTENT` applies
the language overlay and the workspace overlay itself, and `sorting, uid`
follows the ordering rule for manually sortable tables. The records are
rendered through the site's `tt_content` object, which is the same dependency
`styles.content.get` has today.

Rejected: the core `page-content` data processor with
`{content.main.records}`. It is backend layout aware and the better long-term
shape, but every record then has to be rendered per type in the template, and
that differs between `FLUIDTEMPLATE` and `PAGEVIEW` integrations. It is to be
revisited with the page layout change.

Rejected: guarding the `f:cObject` with a condition. The page then renders
nothing instead of throwing, which hides the missing content.

Rejected: making the component sets depend on the content-load set. That
brings back the site wide override the split removed on purpose.

### Decided: `academic:upgrade:check` reports sets TYPO3 cannot provide

Because a site that still names the removed set fails completely, the
configuration group of `academic:upgrade:check` gains the finding
`unavailable-set`, an error: a declared dependency of a site that
`SetRegistry::hasSet()` does not know, when either end of its chain is
academic - an academic set that is missing, is invalid for any reason or misses
a set of another vendor, or a set of another vendor that misses an academic one
(`SetRegistry::getInvalidSets()`, whose `missingDependency` context records the
path below the set as `b[c[missing]]`). An alias set that is unavailable gets
the error instead of the alias notice. Both methods are public API on v13 and
v14. A
constant of the checker maps a removed set to what replaced it, so the message
says what to do; the partner and project content-load sets join it when their
changes remove them. Core already fails the site loudly; the finding exists
because the command runs before the upgraded site goes live. Decided by the
maintainer after the premise above was found false; the alternatives were
documenting the HTTP 500 only, and keeping an empty deprecated set until 4.0.

### Decided: branch `2` gets the non-breaking part

The scoped variable and the template switch are backported to branch `2` as a
change of its own; the content-load set and its static template stay there, so
nothing a 2.x site configuration names disappears in a minor release. That
changes what a 2.3.x site that customised `styles.content.getContent` for
program pages renders after the update, which its changelog states. Decided by
the maintainer after the backport premise was found false.

### Decided: the content-load sets are removed in 3.0, not deprecated

The content-load sets of `academic_partners`, `academic_programs` and
`academic_projects` and `Partials/Program/Categories.html` are removed in 3.0
as a breaking change. There is no deprecation phase and no removal in 4.0.
This supersedes the earlier recommendation to deprecate the three sets
together and remove them in 4.0.

This change removes the programs part, because it is the change that makes
the program page independent of the set:

- the set `fgtclb/academic-programs-content-load`
  (`Configuration/Sets/ContentLoad/`);
- the dependency on it in the aggregate set `fgtclb/academic-programs`;
- the static template `Academic Programs: Content load override` and its
  folder `Configuration/TypoScript/ContentLoad/`.

The partner and project sets are removed by the changes that take their page
templates off `styles.content.getContent`
(`ace-673-page-template-category-block` and
`ace-tbd-page-templates-sections-subtitle`), so no page template loses the
object it still renders. `Partials/Program/Categories.html` is removed by
`ace-tbd-program-facts-field-list`, which replaces its last callers.

The three sets are identical, redefine a global object for every page of a
site, and exist only for the page templates of the three extensions. Once
those templates no longer need them, a deprecation phase would keep a site
wide override alive for a whole major version for nothing but site templates
that call `styles.content.getContent` themselves, and such a site defines it
in its own package in three lines. Rejected: deprecating the three sets in
3.x and removing them in 4.0. Also rejected: keeping them as an opt-in.

## Risks / Trade-offs

- [A site customised `styles.content.getContent` for program pages, for
  example with `slide`] → It overrides `page.10.variables.programContent`
  instead; the changelog says so.
- [A site template override of `AcademicProgram.html` still renders
  `styles.content.getContent`] → It throws once the set is gone unless the
  site package defines the object itself. The Breaking changelog names the
  new variable and the three-line definition for a site that keeps the call.
- [A site configuration lists `fgtclb/academic-programs-content-load`,
  directly or through a set of the site package] → Every page of the site
  answers HTTP 500 until the entry is removed. The Breaking changelog names the
  message and the fix, and `academic:upgrade:check` reports the dependency as
  an error before the upgraded site goes live.
- [A `sys_template` record includes the removed static template] → The
  include resolves to nothing; the same changelog entry covers it.

## Migration Plan

No data migration. A site configuration or `sys_template` record that names
the programs content-load set or static template removes the entry;
`academic:upgrade:check` lists both. A site
template that renders `styles.content.getContent` switches to the
`programContent` variable, or defines the object in its own site package.
Rollback is the code only.

## Open Questions

None.
