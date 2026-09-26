## Context

Verified against tag 2.3.4 and branch `2`:

- The 2.3.4 registrations were `Configuration/TypoScript` (bite_jobs,
  persons_edit), `Configuration/TypoScript/` (contacts4pages) and
  `Configuration/TypoScript/Default` (study_plan). On branch `2`,
  `Configuration/TCA/Overrides/sys_template.php` of the four extensions
  registers only the component folder and `Full`, and none of the 2.3 folders
  holds a TypoScript file.
- Each `Full/` folder holds only an `include_static_file.txt` naming the one
  component folder of the extension: `List` (bite_jobs, contacts4pages),
  `ProfileEditing` (persons_edit), `ContentElement` (study_plan).
  `academic-contact4pages/Configuration/TypoScript/List/include_static_file.txt`
  pulls in `EXT:academic_persons/Configuration/TypoScript/Default`.
- Core resolves a stored `EXT:` include by its path. It reads
  `include_static_file.txt`, `constants.typoscript` and `setup.typoscript` of
  that folder (`SysTemplateTreeBuilder::handleSingleIncludeStaticFile()`); the
  registration only feeds the backend list.
- `AbstractItemProvider::processSelectFieldValue()` drops a stored value that
  is not among the items, so saving a template record in the backend removes
  an unregistered include.
- academic_partners registers its shared folder with a label and a comment
  of its own (`academic-partners/Configuration/TCA/Overrides/sys_template.php`).
  That is the pattern this change follows. Its own 2.3 entry is a different
  story: it named `academic_programs`, see its
  `Important-StaticTemplateAndBackendLayoutRegistration.rst`.
- Version 2.3 had no `constants.typoscript` in the folders of contacts4pages
  and study_plan, and their setup read no constant of their own. The component
  setup of both reads constants now - the view paths of contacts4pages, the
  template paths of study_plan - so a site package that imports only the old
  `setup.typoscript` gets `{$…}` left unresolved. It is documented, not worked
  around (see below).
- A contacts4pages record of version 2.3 that renders profile links stores
  `EXT:academic_persons/Configuration/TypoScript/Default` next to the path,
  because the 2.3 setup reads `{$plugin.tx_academicpersons.detailPid}`. Core
  does not remove a static template read twice.
- Branch `2` has no upgrade check (ACE-713 is on `main` only), so the test
  `main` adds for it has no counterpart here.
- TYPO3 v12 has no `FrontendTypoScriptFactory`, and no site sets: there a
  static template is the only way to deliver an extension at all.

## Goals / Non-Goals

**Goals:**

- A stored 2.3 path delivers "All components" - for contacts4pages together
  with the persons entry a record of version 2.3 stores next to it - and an
  `@import` of a 2.3 file delivers what an import of the component file does.
  Nothing is read twice.

**Non-Goals:**

- Anything beyond what "All components" delivers. On this branch the
  component setup of study_plan adds the stylesheet and the script of the
  content element to the page object, so the 2.3 path delivers them as
  version 2.3 did.

## Decisions

### Thin files that import the component folder

Each 2.3 folder gets `setup.typoscript` and `constants.typoscript`, each a
single `@import` of the matching file of the component folder. study_plan
gets a new `Configuration/TypoScript/Default/` folder.

contacts4pages gets no `include_static_file.txt`, although its `List` folder
has one naming `EXT:academic_persons/Configuration/TypoScript/Default`. A
record of version 2.3 stores that entry itself, and a second one at the
position of the contacts4pages entry would read the persons TypoScript again:
it would reset persons constants set by an entry in between and apply its
`:= addToList()` twice. The old path therefore delivers what it did in 2.3,
and a record that stores both entries gets what "All components" gets.

Rejected: an `include_static_file.txt` that names `Full`. It serves stored
records but not `@import` users, because an import reads only the file it
names. Combined with a `setup.typoscript` in the same folder, it would parse
the component twice for stored records. Rejected as well: importing `Full`,
which holds no TypoScript file to import.

### An import of only the old setup file needs the constants too

Constants cannot be assigned from setup, and repeating the constant defaults
as literal values in the old `setup.typoscript` would make a stored record
differ from "All components" and ignore a changed constant. The Deprecation
entries of contacts4pages and study_plan say to import the new
`constants.typoscript` of the same folder, and the architecture page names the
gap. Rejected: a fallback key below the constant-driven one per root path,
which makes the equality test impossible.

### Register the 2.3 path again, labelled as deprecated

One `ExtensionManagementUtility::addStaticFile()` call per extension, labelled
"<Extension>: Path up to 2.3 (deprecated, use All components)". "2.x" would
read oddly on branch `2`, whose own 2.4 is a 2.x version too. Rejected:
leaving it unregistered, because the next save of the template record drops it
silently.

### Tests compare whole TypoScript trees

`StaticTemplateTypoScriptTrait` (testing helper) builds the TypoScript of one
in-memory root record with `SysTemplateTreeBuilder` and the AST builder
visitor, as the Extbase `BackendConfigurationManager` of v12 does;
`FrontendTypoScriptFactory`, which `main` uses and which runs the same classes
on v13, does not exist on v12. Both classes are `@internal`. Conditions are
not evaluated; none of the compared trees has one. Each extension's
`LegacyStaticTemplatePathTest` compares the tree of the old path with that of
`Full`, and an import of the old files with an import of the component files.
Two reads of the same file build the same tree, so the trait also lists the
files the setup reads, and the test asserts the component setup is read once.
The backend form is compiled for a stored record to show it keeps the old
path; an unregistered second value proves a drop is noticed. Rejected: probing
single values in a rendered page, which shows that one value arrived and
nothing about the rest.

### Changelog: Deprecation entries in 2.4 on both branches

The entries go in `Changelog/2.4/`, as on `main`. The 2.4 Breaking entry of
each extension said the old path delivers nothing; its Impact section is
corrected in place, because 2.4.0 is not released.

### Decided: keep the 2.3 paths, deprecated, until 4.0

The four 2.3 paths keep delivering "All components" as thin deprecated
`@import` files, re-registered with a deprecated label, and are removed in
4.0. A `Deprecation-*.rst` entry per extension names the replacement. A stored
2.3.4 value delivers nothing on branch `2` without any error, and the next
save of the template record drops the unregistered value; academic_partners
already keeps its shared folder registered for that reason. Rejected:
detection only (`ace-713-upgrade-check-configuration` on `main`), which leaves
production rendering unconfigured until somebody reads the report.

## Risks / Trade-offs

- [A site with the set that also stores the 2.3 path or imports its files, as
  one analysed project does, goes from a dead include to a double parse] → The
  Deprecation entries say so and point at the one-mechanism-per-site section.
- [A component added to `Full` later is missing from the 2.3 folder] → The
  functional test compares the TypoScript of the 2.3 path with that of `Full`
  and goes red on any drift.

## Open Questions

None.
