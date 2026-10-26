## Context

See `proposal.md` for the motivation. On `main`:

- Every manual has `Documentation/Upgrade/` with `Index.rst`, `From23To24`,
  `From24To30` and `From23To30` (ACE-910). Each `Index.rst` has a "Before you
  start" list below its table of paths. Branch `2` has `Index.rst` and
  `From23To24` only, and all twelve `From23To24.rst` are byte-identical
  between the branches.
- The upgrade pages of `academic_persons_edit` list the removed Fluid files
  under "The replaced view" and end with "re-apply what the project still
  wants on top of the new files".
- The editor reads its fields from `Configuration/AcademicPersons/Settings.yaml`
  of `academic_persons`. Verified there:
  - a document section offers only the buttons listed under its `actions`
    (`hide`, `view`, `down`, `up`, `delete`, `edit`), and `readonly: true`
    disables creation and every mutating action while keeping `view`;
  - a field takes `readonly`, `frontendreadonly` (ACE-756) or `disabled`, and
    a field or a section that is removed from the file is neither rendered nor
    written;
  - `special.skipSync` is the synchronisation toggle. `~` removes it, and
    `readonly` or `disabled` locks it. The endpoint answers 422
    `invalid_profile_data` when the special field is missing, read-only or
    disabled (`ProfileController::isSpecialFieldWritable()`). This is a code
    reading, not a request.
- `managedFields` (ACE-758, ACE-760), `BeforeProfileEditingWriteEvent`
  (ACE-762) and custom profile fields (ACE-764) have landed, so the gaps the
  first draft of this change named have a replacement now.
- The three `Important-PageTemplateNameIsSetExplicitly.rst` are in
  `Changelog/2.4/`, which is byte-identical between `main` and `2`. 2.4.0 is
  not tagged, so the folder may still change, on both branches alike.
- Links into the manual of `academic_base` are `:ref:` interlinks with the
  shortcode `fgtclb/academic-base` (ACE-937). A label resolves only once the
  rendering of `main` on docs.typo3.org carries it.
- The branch aliases are `dev-main` → `3.0.x-dev` and `2.x-dev` →
  `2.4.x-dev`. No branch answers `2.0.x-dev`.

## Goals / Non-Goals

**Goals:**

- Every row of the persons-edit table is checked against `Settings.yaml` and
  the manual of `academic_persons`, never taken from this design alone.
- The include order explanation is proven in an instance before it is written.

**Non-Goals:**

- New pages per starting version, such as 2.2 to 3.0. The added part points to
  the changelogs and to the existing pages.

## Decisions

### Persons-edit: one table of intents, on both 3.0 pages

A section "From an override to a setting" below "The replaced view" of
`From24To30.rst` and `From23To30.rst`. 2.4 still ships the 2.x form flow, so
`From23To24.rst` needs nothing. The rows, each to be verified:

- no add, delete or sort buttons: the `actions` of the section;
- a section that shows but does not change: `readonly: true` of the section;
- a field that shows but does not change: `readonly`, or `frontendreadonly`
  when the backend keeps it editable;
- a hidden field or section: removed from the settings file of the site
  package;
- the synchronisation toggle hidden or locked: `special.skipSync: ~`, or
  `readonly` or `disabled`. Whether it shows by default stays with ACE-367;
- fields owned by an import: `managedFields`;
- relabelled fields: the `profileEditing.*` label keys;
- replaced icons: `Configuration/FrontendIcons.php` of the site package;
- the "View" link to the public profile: `plugin.tx_academicpersons.detailPid`;
- extra fields: custom profile fields;
- a write that needs a check or a change of the data:
  `BeforeProfileEditingWriteEvent`.

What has no setting (layout, markup, the order of whole sections where the
settings cannot express it) is named as staying a template override, with a
link to the label `templates-override` of the manual.

Rejected: a checklist per 2.x override path. The paths are all removed, the
existing list already says so, and what matters is the intent behind each
override.

### Before you start: one place, linked from the others

`academic-base/Documentation/Upgrade/Index.rst` gains a section
"Installations outside these paths":

- **TYPO3 v12**: update the academic extensions to 2.4 on v12 first, then
  TYPO3 to v13 with 2.4, then 3.0. The core update stays a non-goal.
- **A release older than 2.3**: read the `Breaking-` and `Important-` entries
  of each minor version after the installed one, then follow the 2.3 path.
- **An unreleased revision**: the `ext_emconf.php` version of the installed
  package names the release it was cut after.
- **A `2.0.x-dev` or `2.x-dev` constraint**: replace it with `^2.4`, then
  `^3.0`.

The other eleven upgrade chapters link it from their "Before you start" by an
interlink to a label of the new section. The label is published before the
links use it, see Risks.

On branch `2` the section has no 3.0 step, and the v12 item reads "2.4
supports v12 and v13".

Rejected: the section in every manual. Twelve copies drift, and the content
is the same for every extension.

### Include order: in the changelog entry, on both branches

The "Affected Installations" section of the three entries gains the order rule:
TypoScript that assigns `page.10.templateName.cObject` after the extension's
TypoScript wins over the name. On TYPO3 v13 and v14 a site set is parsed
before the `sys_template` records, so a Bootstrap Package included as a static
template overrides the name the set of the extension sets. Including the
Bootstrap Package through its site set (ACE-601), or the extension through a
static template after it, fixes it. On TYPO3 v12 the order of the static
templates in the record decides.

Rejected: a new changelog entry. The entry exists and is unreleased, so
correcting it keeps one place for the fact.

## Risks / Trade-offs

- [A link to a new label fails `checkRstRenderingAll` until `main` is
  rendered with it] → The section and its label land in their own commit and
  pull request first, the links of the other manuals in a second one after the
  rendering, as `docs/workflow/changelog-and-documentation.md` describes.
- [The include order claim is wrong for one core version] → Task 1.2 proves it
  on v13 and v14 on `main`, and on v12 on `2`, before it is written.
- [The `Changelog/2.4/` copies drift between the branches] → Both pull
  requests carry the same text, compared with `cmp` before pushing.

## Migration Plan

None. Documentation only.
