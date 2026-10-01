## Context

- `free_field` appears in no file under `packages/fgtclb/` on any branch,
  while four project Solr configurations map it anyway.
- No permission set file is shipped by any extension.
- The wizard group is registered twice: as a TCA item group `academic` in
  `academic-base/Configuration/TCA/Overrides/tt_content.php`, and as page
  TSconfig in `academic-base/Configuration/TSconfig/CTypeGroup/page.tsconfig`
  (group header, and up to ACE-792 `after = special`), which
  `Configuration/page.tsconfig` loads for the whole installation.
- Since TYPO3 v13.0 (Feature #102834) the wizard is built from the TCA items
  (`value`, `label`, `description`, `group`, `icon`), and removing an entry
  goes through `removeItems` (Breaking #102834). Group position, header,
  element title and description, and `removeItems` work the same on both
  supported versions.
- The versions differ in one point. `before` and `after` on an element order
  the elements of a group from TYPO3 14.2 on (Feature #87435,
  `NewContentElementController::orderElements()`), and TYPO3 v13 ignores them.
  What does order the elements on v13 is defining them again in page TSconfig:
  `mergeContentElementWizardsWithPageTSConfigWizards()` drops the TCA entry
  whose `tt_content_defValues` are the same and appends the page TSconfig
  entries in their order. The two analysed projects that reorder the group do
  exactly that.
- One `before` or `after` on any group makes `orderWizards()` order every
  group through `DependencyOrderingService` instead of by TCA. The groups in
  the dependency come first, every other group follows in the alphabetical
  order of its identifier. The `after = special` the extension shipped
  therefore showed "Special elements" and "Academic" before "Typical page
  content" on every installation (ACE-792).
- Program pages are doktype 20, project pages doktype 30, partner pages
  doktype 40.

## Goals / Non-Goals

**Goals:**

- Correct references for the three integrations, in the rendered manual.
- The wizard recipe is proven on both core versions, not reasoned about.

**Non-Goals:**

- Keeping the Solr and permission set recipes in step automatically.

## Decisions

### One section in academic_base

The recipes span persons, programs, projects, partners and every extension with
content elements, so they sit in the manual every academic extension requires,
and the others link it. Rejected: a copy per extension, which drifts.

The chapter has one page per tool, because the three have nothing in common
but the reader.

### Documentation, not configuration

Rejected: shipping the Solr index queue or permission sets as opt-in sets.
Each would add a dependency on a third-party extension and its release cycle
to an extension that does not need it.

### The wizard recipe is backed by a functional test

`academic-base/Tests/Functional/Backend/NewContentElementWizardTest.php` calls
`NewContentElementController::handleRequest()` and reads the groups and
elements from the JSON the wizard hands to its web component, so the test
sees what an editor sees. A fixture extension registers three elements in the
`academic` group and one in `plugins`. The `plugins` element is needed: without
a group behind "Special elements", the test of the shipped position stayed
green with `after = special` removed.

Every statement of the recipe has a page of its own with the page TSconfig the
recipe shows, so the cached page TSconfig of one test never answers another.
The element ordering differs per version and has one test per version, tagged
`not-core-13` and `not-core-14`. Rejected: writing the recipe from reading core
code alone. The version difference above was found by the test, not by the
analysis.

### The shipped group position is removed (ACE-792)

The shipped `after = special` is removed, and the group header stays in the
page TSconfig. Without a position the groups keep the order of TYPO3, which
ends with the group academic_base registers. It is a commit of its own after
the documentation, so the documentation commit describes the state it was
written against and the fix commit changes test, recipe and changelog
together.

Rejected: a position that keeps "Typical page content" first, such as also
positioning the core groups. Every position of any extension or site takes
part in the same ordering, so such a setting would hold only on installations
without positions of their own. Rejected as well: keeping the setting and
documenting it, because it moves a core group for every installation to place
a group of this extension.

A site that wants the earlier placement sets `after = special` itself, and
the recipe and the changelog show it. The test keeps that placement on a page
of its own, so the alphabetical ordering it causes stays proven.

### The Solr recipe is derived from the schema and the EXT:solr source

The text columns come from the profile TCA and the detail link arguments from
the persons detail plugin and its route enhancer. The page queues copy the
`pages` queue and set `type = pages`. The page initializer reads `pages`
whatever the queue is called, but the record monitor takes the table from
`type` and falls back to the queue name, so a copy without it is never queued
again after an edit, and a moved page is removed from the index. The analysed
installation the recipe was compared with copies the queue without `type`,
so its configuration is no evidence for that part. The allowed page types are
read per queue, and a queue's `fields` apply to its pages only. A mapped Solr
field `type` is refused by EXT:solr (exception 1435441863), which is why two
projects set it in hooks or events. The recipe sets a dynamic field instead.

Partner pages (doktype 40) are covered as well as program and project pages.
One analysed project indexes them, and the queue differs from the other two
by its doktype only.

### Decided: the Solr recipe targets EXT:solr 13.x

The recipe is written for EXT:solr 13.1 on TYPO3 v13 and names the release it
was checked against, 13.1.4. A section for TYPO3 v14 is added once an EXT:solr
release for v14 has been checked with the recipe.

This repository cannot run Solr. The recipe was checked against the source of
EXT:solr 13.1.4 and the index configuration of the two analysed installations
that run it, not against a running Solr, and the page says so.

### Decided: the permission set recipe targets `b13/permission-sets`

The package is `b13/permission-sets`. The link to it is the source repository
its own `composer.json` names, `https://github.com/b13/permission-sets`, whose
README is its format documentation. The README does not mention the
`permissions` key that grants a table, so the recipe is checked against the
source of release 1.1.0 (`AttachPermissionsToGroups`), which the analysed
installations use.

The lists of tables, fields, content types and page types come from the TCA
of all academic extensions loaded together, read once on TYPO3 v14. Only
`exclude` fields are listed for tables of TYPO3, because every other field is
editable for a group that may edit the table, and the tables of an extension
are granted with `fields: '*'`, which follows the TCA of the installed version.

### Decided: the wizard recipe leaves numbering to ACE-286

The recipe covers the position of the academic group, relabelling, hiding and
the order of its elements. It shows no numbering scheme for content element
labels. A project can use the relabelling it documents for its own numbers.

Numbered labels are a catalogue convention of individual projects, and
ACE-286 owns the question. A published recipe showing one scheme would
pre-empt that issue.

### The partner fields leave the fields of TYPO3 alone (ACE-791, ACE-793)

`ExtensionManagementUtility::addTCAcolumns()` replaces a column of the same name
with `array_merge()`. `academic_partners` added `link` and `description` that
way, so it replaced two fields of TYPO3 on every page type.

- `description` is the description field of TYPO3, which EXT:seo renders as
  the meta description, and a partner page has always stored its description
  there. The extension no longer defines the column, and sets its label and
  five rows as `columnsOverrides` of the partner page type. Rejected: a column
  of its own with an upgrade wizard that copies the values. It changes nothing
  a visitor sees, needs a migration, and adds a wizard to the v15 blocker list
  (ACE-296).
- `link` is defined by TYPO3 v14 only. The extension adds its own column when
  TYPO3 has none, so v13 keeps it and v14 keeps the field of TYPO3. The column
  of the extension is shown in no form and read by no template, so there is
  nothing to override for the partner page type. Rejected: renaming the column,
  for the same migration cost and no gain.

The declarations in the `ext_tables.sql` of the extension stay. Removing
them would let the database analyzer alter columns that hold data, so the
fix is a backend definition, and the database columns keep the type the
extension gave them.

Both are tested against the TCA file of the core extension, read before any
override, so the test does not repeat the definition of TYPO3. A form test was
not added: the exclude flag, `required` and the label are what FormEngine
reads, and core tests what it does with them.

## Risks / Trade-offs

- [The recipes go stale] → each page names the release it was checked
  against, and the integrator migration guide (`cross-cutting-10`) links the
  chapter.
- [The Solr and permission set recipes are untested here] → both were checked
  against the source of the named release, and both pages say that no test
  covers them.
- [A project with its own group positions gets another wizard order] → the
  recipe says that every `before` and `after` takes part, and lists the order
  for an installation without other positions only.
- [Editor groups lose the page description] → it is an exclude field again,
  as TYPO3 defines it. That needs action from integrators, so the entry is a
  `Breaking` one and the commit carries `[!!!]`, and the permission set of
  partners lists the field.

## Open Questions

None.
