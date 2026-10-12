## Context

Measured on branch `2` at `7ebded7b8` by reading, nothing rendered yet:

- `Resources/Private/Layouts/ProfileEdit.html:11` renders
  `<f:render partial="Header/All" arguments="{_all}" />` with no condition.
  All 19 templates below `Resources/Private/Templates/` use that layout, so
  one line decides the header of every view.
- The plugin is a content element (`addPlugin()` with
  `PLUGIN_TYPE_CONTENT_ELEMENT`), so the core renders it as
  `tt_content.academicpersonsedit_profileediting =< lib.contentElement` with
  the `Generic` template, and the `Default` layout of the site renders the
  header around it.
- `Header/All` resolves from
  `partialRootPaths.20 = EXT:fluid_styled_content/Resources/Private/Partials/`
  in `Configuration/TypoScript/ProfileEditing/setup.typoscript`. The plugin
  settings carry neither `renderContentElementHeader` nor `defaultHeaderType`.
- All six controllers assign the record as `data`, which is what the header
  partial reads on v12 and v13. No controller change is needed.
- The extension declares no site settings: there is no
  `Configuration/Sets/*/settings.definitions.yaml`. Its constants live in
  `Configuration/TypoScript/ProfileEditing/constants.typoscript`, which both
  the static template and the site set `fgtclb/academic-persons-edit-profile-editing`
  deliver. `Configuration/TypoScript/constants.typoscript` is the deprecated
  legacy path (ACE-745) and only imports it.
- `AbstractProfileEditingPluginTestCase` already loads
  EXT:fluid_styled_content, includes its constants and setup, and renders
  `styles.content.get`, so the core layout renders the header in every plugin
  test. Its fixture element has an empty header, which is why no test sees
  the defect.
- `ContentElementHeaderAssertionTrait` is in `packages-dev/testing-helper`
  on this branch since ACE-729.
- The fix of ACE-729 on this branch (`6633a3e3f`) is the model:
  `academic_jobs` with a site setting, `academic_bite_jobs` with the constant
  only, because that extension declares no site settings either.

## Goals / Non-Goals

**Goals:** the behaviour of the job plugins after ACE-729, for the profile
editing plugin, on TYPO3 v12 and v13.

**Non-Goals:** any change to the plugin views apart from the header, and the
wider test coverage of the plugin.

## Decisions

### The same switch, name and default as ACE-729

The layout wraps the `Header/All` line in
`<f:if condition="{settings.renderContentElementHeader}">`. The setup maps
`settings.renderContentElementHeader` from the new constant
`plugin.tx_academicpersonsedit.renderContentElementHeader`, default `0`, and
`settings.defaultHeaderType = {$styles.content.defaultHeaderType}`, with the
comment of the job extensions. The constant goes into
`ProfileEditing/constants.typoscript`, so the static template, the legacy
path and the site set deliver it alike.

The wrap belongs in the layout, not in the 19 templates: the layout holds
the only render of the partial, and one condition covers every view.

Rejected, as for ACE-729: removing the line without a switch (a site whose
layout renders no header loses the header with no way back), a switch shared
through `academic_base` (every extension is used on its own), and a layout
without a `Header` section for this CType (the site decides that, not the
extension).

### Constant only, no site setting (Decision for the maintainer)

Options:

1. **Constant only**, as `academic_bite_jobs` in ACE-729. Works on v12 and
   v13. A v13 site that uses the site set sets it in the constants of its
   site TypoScript or of a `sys_template` record.
2. **Constant and a v13 site setting**, as `academic_jobs` in ACE-729. Needs
   a first `Configuration/Sets/Full/settings.definitions.yaml` with the
   `academic` category and a `persons-edit` subcategory, a delivery test for
   the setting, and a sentence in the configuration manual on the aggregate
   set.

Recommended: option 1. The extension declares no site setting today, and on
`main` the editor of 3.0 renders no header of its own and has no switch
(ACE-262), so a site setting declared for 2.4.0 would be declared for one
minor version and then disappear, while a constant that nothing reads any
more is harmless.

Decided on 2026-10-12: option 1, the constant only.

### An undefined `styles.content.defaultHeaderType`

Documented, not guarded, for the reason recorded with ACE-729: TypoScript has
no fallback for an undefined constant, and the elements of
EXT:fluid_styled_content miss the same constant in that case. A site of
`bk2k/bootstrap-package` that switches the header on sets
`plugin.tx_academicpersonsedit.settings.defaultHeaderType` itself.

### Tests

A new test class in `Tests/Functional/Plugins/`, extending
`AbstractProfileEditingPluginTestCase` and using
`ContentElementHeaderAssertionTrait`. `setUpTestCase()` of the abstract case
gets two optional lists of additional constant and setup files, the way the
bite jobs test has them. Two test classes override it without parameters,
`AcademicPersonsEditFormValidationErrorsTest` and
`AcademicPersonsEditPhysicalAddressTextFieldsTest`, and PHP rejects an
override with fewer parameters than its parent. Both take the same two
optional parameters and pass them on. Their tests do not change.

- Switch off, core layout: header layouts "Default", 2 and "Hidden" with a
  header and a subheader, rendered on the profile list and on the view of the
  profile. The heading and the subheader are counted once (none for
  "Hidden"), and no `<header>` below the `academic-persons-edit` wrapper.
- Switch on, fixture layout without a header: the same layouts, the heading
  counted once in the whole page and once inside the wrapper.

Fixtures as for the bite jobs test: `Fixtures/Layouts/Default.html` without
a header, `Fixtures/TypoScript/Setup/LayoutWithoutHeader.typoscript` giving it
to `tt_content.academicpersonsedit_profileediting.layoutRootPaths.50`, and
`Fixtures/TypoScript/Constants/RenderContentElementHeader.typoscript`. The
fixture name `Default.html` does not collide with the plugin's own layout,
which is `ProfileEdit` and resolved through the plugin view's root paths.

### Changelog placement

`Documentation/Changelog/2.4/Important-ProfileEditingLeavesTheHeaderToTheLayout.rst`,
following the two ACE-729 entries, plus a section
`configuration-general-content-element-header` in
`Documentation/Configuration/General/Index.rst`.

### The 2.4 changelog folder on `main` (Decision for the maintainer)

Since ACE-896 the `Changelog/2.4/` folders on `main` are byte identical to
branch `2`. A new 2.4 entry here makes them differ. Options: mirror the entry
to `main` in a small follow-up commit after the merge (2.4.0 is unreleased,
so the folder is still editable), or leave `main` as it is. Recommended:
mirror it, so the folders stay identical as ACE-896 left them. Confirm with
the maintainer. It is not part of this change either way.

A mirror is not a plain copy. The two ACE-729 entries link the manual with
`:ref:` to `configuration-general-content-element-header`, which exists in
the manuals of the job extensions on both branches. The manual of
`academic_persons_edit` on `main` has no such section, and the 3.0 editor no
switch to describe, so an entry that links it fails `checkRstRenderingAll`
on `main`. Either the entry here names the section without a `:ref:`, or the
mirror on `main` replaces the link by text and the folders differ in that
line.

Decided on 2026-10-12: mirror it after the merge, and write the entry here
without a `:ref:`, so the mirror is a plain copy and the folders stay byte
identical. The entry names the manual section in words. The follow-up on
`main` adds a `Breaking` entry to `Changelog/3.0/` as well: a 2.4 site that
switches the header on loses it on the update to 3.0, whose editor renders no
header and reads no such constant.

## Verified

Against the unchanged layout, on TYPO3 v12 and v13 alike, the new test class
failed in six of its twelve cases: header layout 2 counted the heading twice
with the switch off, header layout "Default" left a `<header>` inside the
plugin wrapper, and with the switch on header layout "Default" rendered no
heading, on the profile list and on the view of the profile each. With the
change, all twelve pass. Without the `defaultHeaderType` line in the setup,
the two switched on "Default" cases fail on both versions, and nothing else.
The "Hidden" cases pass before and after, they guard the layout that shows no
header.

## Risks / Trade-offs

- [A site whose content element layout renders no header loses the profile
  editing header on update] -> The `Important` entry leads with the switch,
  and the manual section names it.
- [A project that copied `Layouts/ProfileEdit.html` keeps the double header]
  -> The `Important` entry names the copied layout and shows the condition to
  wrap the line in.
- [A site that switches the constant on keeps no header of its own on 3.0,
  where the editor renders none] -> Not part of this change. The follow-up
  ACE-902 on `main` documents it in a `Breaking` entry for 3.0, with the
  two ways to render the header there.
