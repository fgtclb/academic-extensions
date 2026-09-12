## Context

- `free_field` appears in no file under `packages/fgtclb/`; four project Solr
  configurations map it anyway.
- No permission set file is shipped by any extension.
- The wizard group is registered twice: as a TCA item group `academic` in
  `academic-base/Configuration/TCA/Overrides/tt_content.php`, and as page
  TSconfig in `academic-base/Configuration/TSconfig/CTypeGroup/page.tsconfig`
  (group header, `after = special`).
- Since TYPO3 v13.0 (Feature #102834) the wizard is built from the TCA items
  (`value`, `label`, `description`, `group`, `icon`), and removing an entry
  goes through `removeItems` (Breaking #102834). Both supported versions
  therefore share one mechanism.
- Program pages are doktype 20, project pages doktype 30.

## Goals / Non-Goals

**Goals:**

- Correct references for the three integrations, in the rendered manual.
- The wizard recipe is proven, not reasoned about.

**Non-Goals:**

- Keeping the Solr and permission set recipes in step automatically.

## Decisions

### One section in academic_base

The recipes span persons, programs, projects and every extension with content
elements, so they sit in the manual every academic extension requires, and
the others link it. Rejected: a copy per extension, which drifts.

### Documentation, not configuration

Rejected: shipping the Solr index queue or permission sets as opt-in sets.
Each would add a dependency on a third-party extension and its release cycle
to an extension that does not need it.

### The wizard recipe is backed by a functional test

A functional backend test builds the new content element wizard on both core
versions and asserts the position of the academic group and the order of its
items, with and without the TSconfig the recipe shows. Rejected: writing the
recipe from reading core code; the wizard changed its semantics in v13, and
`show` no longer applies there.

### The Solr recipe is derived from the schema

The text columns come from the profile TCA and the detail link arguments from
the persons detail plugin and its route enhancer. The page names the EXT:solr
version it was written for, because the repository cannot run it.

### Decided: the Solr recipe targets EXT:solr 13.x

The recipe is written for EXT:solr 13.x on TYPO3 v13 and names the exact
release it was reviewed against. A section for TYPO3 v14 is added once an
EXT:solr release for v14 has been verified with the recipe.

The analysed installations that index profiles run EXT:solr 13.1.4, and the
recipe is reviewed against one of them, since this repository cannot run
Solr. Whether a v14-compatible EXT:solr release exists was not checked, so a
v14 section now would be written blind.

### Decided: the permission set recipe targets `b13/permission-sets`

The package is `b13/permission-sets`; two analysed installations use it for
the academic tables. The link to its format documentation is supplied by the
maintainer and is not guessed. The recipe is written only once that link is
known.

### Decided: the wizard recipe leaves numbering to ACE-286

The recipe covers the position of the academic group, relabelling and the
order of its items. It shows no numbering scheme for content element labels.
A project can use the relabelling it documents for its own numbers.

Numbered labels are a catalogue convention of individual projects, and
ACE-286 owns the question. A published recipe showing one scheme would
pre-empt that issue.

## Risks / Trade-offs

- [The recipes go stale] → the page names the versions it was verified with,
  and the integrator migration guide (`cross-cutting-10`) links it.
- [The Solr recipe is untested here] → it is reviewed against a project
  installation before merging, and says so.

## Open Questions

None.
