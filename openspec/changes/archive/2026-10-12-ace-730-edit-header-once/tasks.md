## 1. Premises and decisions

- [x] 1.1 Re-check the premises of `design.md`, Context, on the branch head:
  the unconditional `Header/All` line in `Layouts/ProfileEdit.html`, the 19
  templates on that layout, the missing settings in
  `ProfileEditing/setup.typoscript`, the `data` assignment of the six
  controllers, no `settings.definitions.yaml` in the extension. Record any
  difference and update the change before going on.
- [x] 1.2 Confirm the two decisions marked "Decision for the maintainer" in
  `design.md` (constant only, the mirror on `main`) and record the answer in
  `design.md`.

## 2. Tests first

- [x] 2.1 Give `setUpTestCase()` of `AbstractProfileEditingPluginTestCase`
  optional additional constant and setup files, and add the fixtures
  `Fixtures/Layouts/Default.html`,
  `Fixtures/TypoScript/Setup/LayoutWithoutHeader.typoscript` and
  `Fixtures/TypoScript/Constants/RenderContentElementHeader.typoscript`.
  Give the two overrides of `setUpTestCase()` (form validation errors,
  physical address text fields) the same optional parameters and pass them
  on, since PHP rejects an override with fewer parameters. Verify the
  existing plugin test classes stay green with their tests unchanged.
- [x] 2.2 Add the header test class with `ContentElementHeaderAssertionTrait`:
  switch off on the core layout, header layouts "Default", 2 and "Hidden",
  on the profile list and on the view of the profile. Record that the
  layout 2 case counts the heading twice and the "Default" case finds a
  `<header>` inside the plugin wrapper against the unchanged layout, on v12
  and v13.
- [x] 2.3 Add the switched on cases with the fixture layout without a header.
  Record that the "Default" case fails without the `defaultHeaderType`
  mapping (remove the line from the setup, watch it render an empty
  `<header>`, restore it from a backup).

## 3. Implementation

- [x] 3.1 The constant `plugin.tx_academicpersonsedit.renderContentElementHeader = 0`
  in `ProfileEditing/constants.typoscript`, the settings
  `renderContentElementHeader` and `defaultHeaderType` in
  `ProfileEditing/setup.typoscript`, with the comment of the job extensions.
  Plus the site setting only if 1.2 decided for it.
- [x] 3.2 Wrap the `Header/All` line of `Layouts/ProfileEdit.html` in the
  switch. Verify 2.2 and 2.3 are green on v12 and v13.

## 4. Documentation

- [x] 4.1 `Documentation/Changelog/2.4/Important-ProfileEditingLeavesTheHeaderToTheLayout.rst`
  from `Build/Documentation/Templates/`, modelled on the two ACE-729 entries:
  what was rendered, the switch, the affected installations, the copied
  layout. A unique `important-<timestamp>` anchor.
- [x] 4.2 The section `configuration-general-content-element-header` in
  `Documentation/Configuration/General/Index.rst`.
- [x] 4.3 `docs/architecture/content-element-rendering.md`: the paragraph on
  the remaining exception is replaced by `academic_persons_edit` rendering
  the header behind the switch. The plugin setting table names the constant
  generically and needs no change. Verify with `lintMarkdown -n`.

## 5. Definition of done

- [x] 5.1 `Build/Scripts/runTests.sh -t 12 -s composerUpdate`, then
  `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` with `-t 12`
  green: functional on SQLite with `-j auto`, PostgreSQL with
  `-b docker -j 8` and MariaDB with `-j 8`.
- [x] 5.2 The same for `-t 13` after its own `composerUpdate`.
- [x] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green, and no
  definition list in the rendered entry.
- [x] 5.4 Commit as `[BUGFIX] ACE-730: <subject>` in TYPO3 Core format with
  `Resolves: ACE-730`, and archive the change as the last
  commit of the same pull request.
