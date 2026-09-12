## Context

Verified against tag 2.3.4 and `main`:

- The 2.3.4 registrations were `Configuration/TypoScript` (bite_jobs,
  persons_edit), `Configuration/TypoScript/` (contacts4pages) and
  `Configuration/TypoScript/Default` (study_plan). On `main`,
  `Configuration/TCA/Overrides/sys_template.php` of the four extensions
  registers only the component folder and `Full`, and none of the 2.x folders
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
- academic_partners keeps its 2.x value registered with a label of its own
  (`academic-partners/Configuration/TCA/Overrides/sys_template.php:53-65`).
  That is the pattern this change follows.

## Goals / Non-Goals

**Goals:**

- A stored 2.x include and an `@import` of a 2.x file each deliver "All
  components", and nothing is parsed twice.

**Non-Goals:**

- The page CSS and JS of the 2.3.4 study plan `Default` folder: on `main` the
  content element template loads its own assets.

## Decisions

### Thin files that import the component folder

Each 2.x folder gets `setup.typoscript` and `constants.typoscript`, each a
single `@import` of the matching file of the component folder. Only
contacts4pages also gets an `include_static_file.txt`, naming
`EXT:academic_persons/Configuration/TypoScript/Default` as its `List` folder
does. study_plan gets a new `Configuration/TypoScript/Default/` folder.

Rejected: an `include_static_file.txt` that names `Full`. It serves stored
records but not `@import` users, because an import reads only the file it
names. Combined with a `setup.typoscript` in the same folder, it would parse
the component twice for stored records. Rejected as well: importing `Full`,
which holds no TypoScript file to import.

### Register the 2.x path again, labelled as deprecated

One `ExtensionManagementUtility::addStaticFile()` call per extension, with a
label such as "Academic Bite Jobs: 2.x path (deprecated, use All
components)". Rejected: leaving it unregistered, because the next save of the
template record drops it silently.

### Decided: keep the 2.x paths, deprecated, until 4.0

The four 2.x paths keep delivering "All components" as thin deprecated
`@import` files, re-registered with a deprecated label, and are removed in
4.0. A `Deprecation-*.rst` entry per extension names the replacement. A
stored 2.3.4 value delivers nothing on `main` without any error, and the next
save of the template record drops the unregistered value; academic_partners
already keeps its 2.x value for that reason. Rejected: detection only
(`ace-tbd-upgrade-check-configuration`), which leaves production rendering
unconfigured until somebody reads the report. That check still reports the
other dead includes.

## Risks / Trade-offs

- [A site with the set that also imports the 2.x file, as one analysed
  project does, goes from a dead import to a double parse] → The
  Deprecation entries say to remove such imports, and point at the
  one-mechanism-per-site section.
- [A component added to `Full` later is missing from the 2.x folder] → The
  functional test compares the TypoScript of the 2.x path with that of `Full`
  and goes red on any drift.

## Open Questions

None.
