## 1. Verify the premises

- [x] 1.1 Confirm `ace-tbd-frontend-icon-registry` (#112) is merged on `main`.
  In a functional test on both core versions, render one identifier registered
  with `CurrentColorSvgIconProvider` in both registries through `core:icon` and
  through the `academic_base` ViewHelper, with `alternativeMarkupIdentifier="inline"`
  and with and without `size="small"`, and compare the output byte for byte.
  If it differs, stop and update this change.
  Done differently: the comparison ran as two throwaway cases of
  `IconViewHelperTest`, not committed, and was extended to whole pages: every
  page the functional suites of both extensions render, captured before and
  after the switch (1190 pages with 62256 icons on v13, 1223 with 64557 on
  v14). They differ
  only by blank lines and by values that change per run. The blank lines come
  from the namespace line added to or removed from the `<html>` tag of a
  partial, which Fluid drops but whose line break stays. The icon markup is
  byte identical.
- [x] 1.2 Re-run the inventory on `main`: 17 entries in each `Icons.php`,
  `grep -rn "<core:icon" packages/fgtclb/academic-persons*/Resources/Private`
  gives 42 tags in 15 files, no backend template, TCA, TSconfig or class of
  either package names one of the 23 identifiers, and
  `grep -rl "academic-persons-\(envelope\|phone\|address\|room\|clock\|detail-plus\|detail-minus\|edit-[a-z-]*\)" packages packages-dev`
  over PHP, HTML, YAML, TypoScript and TSconfig finds them in no other package
  (empty on `428cf1a32`). Stop on any difference.
  The task's grep pattern `edit-[a-z-]*` also matches the element and class
  names of the editor, so it ran with the 23 identifiers spelled out: no file
  outside the two `Icons.php`, the templates and the tests names one, apart
  from a comment of `elements/names.ts`.

## 2. Registrations

- [x] 2.1 Create `academic-persons/Configuration/FrontendIcons.php` with the
  seven `academic-persons-*` entries, same provider and source, and remove them
  from `Configuration/Icons.php` (ten entries stay). Move the comment of the
  block with them.
- [x] 2.2 Create `academic-persons-edit/Configuration/FrontendIcons.php` with
  the sixteen `academic-persons-edit-*` entries and their comment, and reduce
  `Configuration/Icons.php` to `persons_edit_icon` with a comment that says so
  (the "fourteen action icons" comment goes).

## 3. Templates

- [x] 3.1 In `Partials/Profile/PublicProfile/Contact.html` and
  `ProfileEntries.html` of persons, replace `<core:icon` with `<ab:icon`,
  keep every argument, declare
  `xmlns:ab="http://typo3.org/ns/FGTCLB/AcademicBase/ViewHelpers"`, and rewrite
  the comment of `Contact.html:8-12`.
- [x] 3.2 The same for the 13 files of persons-edit (`Templates/Profile/Index.html`,
  `List.html`, `Partials/Profile/ButtonTemplates.html`, `Prototypes.html`,
  `Field/{Helptext,Preview,Group,AutosaveUndo,Actions}.html`,
  `Documents/{Sections,ContractContacts,Actions}.html`, `Image/Card.html`), and
  remove `xmlns:core` from the ten files that declare it and from
  `Documents/Editor.html` and `Documents/ContractContactEditor.html`. Verify
  with `grep -rn "core:" packages/fgtclb/academic-persons*/Resources/Private`
  that nothing is left.
- [x] 3.3 Verify that nothing below `Resources/Private/TypeScript/`,
  `Resources/Private/Scss/` and `Resources/Public/` of both packages changed:
  `testJs` and `checkJsBuildClean` green.

## 4. Tests

- [x] 4.1 New `academic-persons/Tests/Functional/Imaging/PublicProfileIconsTest.php`
  with the seven identifiers spelled out: registered in the frontend registry
  with `CurrentColorSvgIconProvider`, inlined in both markups, rendered with
  their own identifier (the frontend trait of #112), not registered in the core
  `IconRegistry`, so the core answers `default-not-found`. Plus `persons_icon`
  in the core registry and not in the frontend one. Show it red twice: one
  identifier left in `Icons.php`, one removed from `FrontendIcons.php`.
  Done differently: the test also covers the nine record icons with the
  plugin icon, in the core registry and not in the frontend one, and adds a
  third red proof, `persons_icon` registered in `FrontendIcons.php`.
- [x] 4.2 Rewrite `academic-persons-edit/Tests/Functional/Imaging/ProfileEditingIconsTest.php`
  the same way for the sixteen identifiers and `persons_edit_icon`, with the
  same two red proofs.
  The third red proof here is `persons_edit_icon` in `FrontendIcons.php`.
- [x] 4.3 `AcademicPersonsPublicProfilePluginTest`: update the docblocks of
  `ICON_IDENTIFIERS` and `profileRendersOnlyResolvableIcons()`, keep both
  assertions, and add the exact wrapper of `academic-persons-envelope` (classes,
  `data-identifier`, `aria-hidden`, inner `icon-markup`). Show it red by
  switching one tag of `Contact.html` back to `core:icon` (not-found), and the
  wrapper assertion by removing the `icon-markup` span in the ViewHelper of
  `academic_base` for one run.
  Done differently: the wrapper is asserted up to the opening `<svg `, because
  the inlined file is serialised differently on v13 and v14.
- [x] 4.4 `AcademicPersonsEditProfileEditingTest::everyIconIdentifierOfTheShippedTemplatesIsRegistered()`
  reads `Configuration/FrontendIcons.php`, matches `<ab:icon`, and asserts that
  no `<core:icon` is left in the editor's sources.
  `theRenderedEditorResolvesEveryIconItAsksFor()` gains the exact wrapper of
  `academic-persons-edit-save` with `icon-size-small`. Show both red by putting
  one `core:icon` back, and the scan by removing one identifier from
  `FrontendIcons.php`. The XPath tests at `:327` and `:1126-1130` run unchanged.
  The red proof switched both tags of `academic-persons-edit-save` back
  (`Field/Actions.html`, `Field/Group.html`). The wrapper assertion was shown
  red on its own with the ViewHelper change of 4.3.
- [x] 4.5 `AcademicPersonsEditProfileEditingPrototypesTest`: a new test that
  the rendered `helptext-button`, `contact-section` and `contact-row`
  prototypes and the `data-pe-new-button-template` carry their icons
  (`-help`, `-add`, the eight row controls, `-edit`) with their identifiers and
  no `default-not-found`. Show it red by switching `ContractContacts.html` back
  to `core:icon`.
  Also shown red for the other two files, one run each: the help icon of
  `Prototypes.html` and the icon of `ButtonTemplates.html` back on
  `core:icon`.
- [x] 4.6 One fixture extension per package (`test_frontend_icon_replacement`,
  depending on the extension) registers `academic-persons-envelope` resp.
  `academic-persons-edit-edit` with its own file in `FrontendIcons.php`, and
  `academic-persons-phone` resp. `academic-persons-edit-delete` in `Icons.php`
  only. Assert on both cores: the detail view and the editor (including the
  `contact-row` prototype) show the fixture drawing for the first and the
  shipped drawing for the second. Show the first red by dropping the fixture's
  `FrontendIcons.php` entry, the second by switching its template back to
  `core:icon` for one run.
  Done differently: two fixture keys instead of one shared
  `test_frontend_icon_replacement`, because two packages with the same
  composer name cannot both be found by the fixture plugin:
  `test_profile_icon_replacement` (`tests/profile-icon-replacement`) and
  `test_editor_icon_replacement` (`tests/editor-icon-replacement`). Both sort
  after the extension they replace an icon of, and each test asserts that
  order first. The tests are `AcademicPersonsPublicProfileIconReplacementTest`
  and `AcademicPersonsEditIconReplacementTest`.
- [x] 4.7 Correct the JavaScript test comments: `Tests/JavaScript/Fixtures/profile-editing.ts:13`
  and `:241` name the new tag, `contract-contacts-element.test.ts:305-310` and
  `document-editor-element.test.ts:400-404` describe the cloned prototypes
  instead of a `<template data-pe-icon>` that never existed. `testJs` green.
  `docs/testing/javascript-tests.md` names the tag and the marker elements
  of the prototype blocks as well.

## 5. Documentation

- [x] 5.1 `academic_persons`: `Breaking-PublicProfileIconsMovedToTheFrontendIconRegistry.rst`
  in `Documentation/Changelog/3.0/` with both silent effects and what to do: a
  replacement in a site package's `Icons.php` shows the shipped artwork again
  (move it to `FrontendIcons.php` of a package that depends on
  `academic_persons`, flush caches), an override of `Contact.html` or
  `ProfileEntries.html` on `core:icon` shows the not-found icon (swap the tag,
  declare `xmlns:ab`, keep the arguments, a project icon in the same file either
  stays on `core:icon` or is registered in `FrontendIcons.php`), PHP or backend
  code asking `IconFactory` for one of them gets not-found, and the markup is
  unchanged, so stylesheets need nothing.
- [x] 5.2 `academic_persons_edit`: `Breaking-ProfileEditingIconsMovedToTheFrontendIconRegistry.rst`
  with the same content for the sixteen icons, plus the three prototype
  partials whose not-found icon is cloned into every row, and the
  `data-pe-view-icon` and `data-pe-visibility-icon` wrappers an override keeps.
- [x] 5.3 Amend the unreleased persons pages: `Configuration/Sections/Index.rst:274-282`,
  `Changelog/3.0/Feature-ConfigurablePublicProfile.rst:44-51` (six becomes
  seven with the clock), `Changelog/3.0/Important-RecordIconsFollowTheColourScheme.rst`
  (six control icons becomes seven, now frontend icons), `Upgrade/Index.rst:370-383`
  (thirteen becomes sixteen, `FrontendIcons.php`).
- [x] 5.4 Amend the unreleased persons-edit pages: `Templates/Override/Index.rst:61-100`
  (all sixteen identifiers, `FrontendIcons.php` in text and code caption, the
  site package depends on the extension), `ProfileEditing/Index.rst:577`,
  `:722-728` and `:1843-1850` (the `academic_base` ViewHelper and the frontend
  registry, the table gains `-visible` and `-hidden`, sixteen rows),
  `Changelog/3.0/Feature-ProfileEditingIconSet.rst` (fourteen becomes sixteen,
  Impact and Migration name `FrontendIcons.php`),
  `Changelog/3.0/Breaking-ReplacedProfileEditingPlugin.rst:146-151` (sixteen
  action icons, `persons_edit_icon` the only entry of `Icons.php`),
  `Changelog/3.0/Feature-ProfileEditing.rst:138` (thirteen becomes sixteen).
  Two unreleased entries of `academic_base` named the old state as well and
  are corrected: `Feature-FrontendIconRegistry.rst` said no template renders
  through the new view helper yet, `Feature-CurrentColorSvgIconProvider.rst`
  counted six and thirteen control icons.
- [x] 5.5 `docs/architecture/icons.md`, in the shape #112 left it: the persons
  rows of the registration tables (`Icons.php` 10 and 1, `FrontendIcons.php` 7
  and 16), the template table (7 sites, not 6), the control icon paragraph and
  the test section, recounted with the page's own commands.
  `docs/architecture/profile-editing-contract.md:928-940` names the frontend
  registry and the ViewHelper, `docs/testing/javascript-tests.md:89` the new
  tag, `docs/testing/fixture-extensions.md` the two fixtures and the count.
- [x] 5.6 The migration guide change `ace-tbd-integrator-migration-guide`, while
  it is still active: its `design.md` maps the profile editor override intent
  "icons" to `Icons.php`. Change it to `FrontendIcons.php` and add this change
  to its list of changes the guide names.

## 6. File the issue

- [x] 6.1 File the ACE issue in YouTrack (Task, version 3.0.0, subtask of
  ACE-10, relates to the frontend icon umbrella issue and to the issue of
  #112), and verify the key with a GET request.
  Done differently: filed as ACE-812 before the documentation, so the
  documentation could name it. It is in the list of ACE-808.
- [x] 6.2 Rename the change to `ace-<NNN>-persons-frontend-icons`.

## 7. Definition of done

- [x] 7.1 After `composerUpdate` for TYPO3 v13: `lintPhp`, `cgl -n`, `phpstan`,
  `unit` and `functional -j auto` green.
- [x] 7.2 After `composerUpdate` for TYPO3 v14: `lintPhp`, `cgl -n`, `phpstan`,
  `unit` and `functional -j auto` green.
- [x] 7.3 `testJs`, `checkJsBuildClean`, `lintMarkdown -n` and
  `checkRstRenderingAll` green, and the reST adornments match their titles.
- [x] 7.4 `docs/` is updated, and `README.md` and `CONTRIBUTING.md` still only
  summarize.
- [x] 7.5 Commit as `[!!!][TASK] ACE-<NNN>: Move persons icons to frontend` in
  TYPO3 Core format, and archive the change as the last commit of the pull
  request.
