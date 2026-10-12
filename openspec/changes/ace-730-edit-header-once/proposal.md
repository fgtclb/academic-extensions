## Why

The profile editing plugin of `academic_persons_edit`
(`packages/fgtclb/academic-persons-edit`) renders the content element header
in its `ProfileEdit` layout, which all 19 templates of the plugin use, while
the content element layout renders it as well. The layouts of
EXT:fluid_styled_content and of `bk2k/bootstrap-package` both do. The line is
released since 2.1.0:

- With an explicit header layout (1 to 5), the header and the subheader
  appear twice on every view of the plugin: the profile list, the profile,
  the edit forms and the image form.
- With the header layout "Default", the plugin emits an empty
  `<header></header>`: the partial falls back to a setting plugin settings do
  not carry.

It is the defect ACE-729 fixed for the job plugins, and
`docs/architecture/content-element-rendering.md` names it as the one
remaining exception on this branch. 2.4.0 is not tagged yet, so the fix ships
with it.

## What Changes

- A setting of `academic_persons_edit`, "render the content element header in
  the plugin", off by default. Off: the plugin renders no header, the layout
  renders it once. On: the plugin renders it, with the heading level for
  "Default" mapped from the constant of EXT:fluid_styled_content.
- An `Important` changelog entry in `Documentation/Changelog/2.4/` and a
  section in the configuration manual.

Behaviour is the same on TYPO3 v12 and v13. The switch is a TypoScript
constant on both. Whether TYPO3 v13 gets a site setting as well is a decision
for the maintainer, see `design.md`.

A site whose content element layout renders **no** header loses the plugin
header on update and switches the constant on. That is what the `Important`
entry leads with.

## Capabilities

### New Capabilities

- `academic-persons-edit/content-element-header`: who renders the header of
  the profile editing plugin, and how often.

### Modified Capabilities

None.

## Impact

- The layout `Resources/Private/Layouts/ProfileEdit.html`, the constants and
  the setup of `Configuration/TypoScript/ProfileEditing/`.
- The functional tests of the plugin, with new fixtures.
- `Documentation/Changelog/2.4/`, `Documentation/Configuration/General/`,
  `docs/architecture/content-element-rendering.md`.
- No PHP, TCA or database change. No controller change: the controllers
  assign `data` already, which is what the header partial reads on v12 and
  v13.

## Non-goals

- `main`. ACE-262 replaced the editor there and its templates render no
  content element header, so there is nothing to forward-port. Whether the
  new 2.4 changelog entry is mirrored into the `Changelog/2.4/` folder of
  `main` is a separate decision, see `design.md`.
- Changing the content element layout or `lib.contentElement`.
- The `record` view variable: a TYPO3 v14 requirement of the header partial,
  and this branch has no v14 support.
- The other plugins of this branch: they render no header of their own.
