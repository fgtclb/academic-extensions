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
  `2.4.x-dev`. No branch answers `2.0.x-dev`, `2.1.x-dev` or `2.3.x-dev`.

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

- **TYPO3 v12**: 2.3 and 2.4 run on 12.4 from 12.4.22 on and on 13.4. The
  path from 2.3 to 2.4 covers v12, and the paths to 3.0 already start with
  the update of TYPO3 to 13.4.35 while 2.3 or 2.4 still runs, so the item
  only adds the 12.4.22 floor. An earlier draft routed v12 through 2.4 first,
  which contradicted those pages. The core update stays a non-goal.
- **A release older than 2.3**: read the `Breaking-` and `Important-` entries
  of each minor version after the installed one, then follow the 2.3 path.
- **An unreleased revision** (`dev-2`, `2.x-dev`, `2.4.x-dev`): it carries
  the version of the release it leads to, `2.4.0` in `ext_emconf.php` and
  `2.4.0-dev` in `VERSION`, and part of its changes. `composer.lock` names the
  installed commit. Follow the path from the last release before it, 2.3,
  and skip the steps that do not apply, as every page says.
- **A development constraint of an earlier version**: `main` was aliased
  `2.0.x-dev`, `2.1.x-dev`, `2.3.x-dev` and `2.4.x-dev` in turn, none of the
  first three resolves any more, and `2.2.x-dev` resolves to the unmaintained
  branch `2.2`. Replace it, or `2.x-dev`, with `^2.4`, then `^3.0`.

The other eleven upgrade chapters link it from their "Before you start" by an
interlink to a label of the new section. The label is published before the
links use it, see Risks.

On branch `2` the section has no 3.0 step, and the v12 item reads "2.4
supports v12 and v13".

Rejected: the section in every manual. Twelve copies drift, and the content
is the same for every extension.

### Include order: in the changelog entry, on both branches

The "Affected Installations" section of the three entries gains the order rule
proven by task 1.2: TypoScript that assigns `page.10.templateName.cObject`
after the extension's TypoScript wins over the name. On TYPO3 v13 and v14 the
TypoScript of the site sets is parsed before that of the `sys_template`
records, so the static template of Bootstrap Package 15 (v12 and v13, version
16 has none) or a record of the site package that assigns the name overrides
the name the set of the extension sets. Including the Bootstrap Package
through its site set (ACE-601), as both development instances do with
versions 15 and 16, or the extension's static template after the one of the
Bootstrap Package in the same record, fixes it. On TYPO3 v12 the later static
template of the record wins. The text names
"Included TypoScript" of the TypoScript module as the place to check
the order on a page of the type.

The text makes no promise about the order of two sets. TYPO3 sorts all sets
of an installation by their dependencies and their names, not by the order
of the site's dependency list, and neither set depends on the other. Making
the order irrelevant, with an optional dependency of the page sets on the
sets of the Bootstrap Package, stays a non-goal of this change.

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
