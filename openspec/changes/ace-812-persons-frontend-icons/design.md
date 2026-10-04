## Context

See `proposal.md` for the motivation. On `main` (`428cf1a32`), re-checked
against the inventory of the frontend icon analysis:

- `academic-persons/Configuration/Icons.php` registers 17 identifiers: nine
  record icons and `persons_icon` (backend: `ctrl.typeicon_classes`, the
  `tt_content` type icons, the wizard entries of page TSconfig) and the seven
  `academic-persons-*` controls. `academic-persons-edit/Configuration/Icons.php`
  registers `persons_edit_icon` (backend) and the sixteen
  `academic-persons-edit-*` actions. All 23 controls use
  `CurrentColorSvgIconProvider`. No backend template, TCA, TSconfig or PHP class
  of either package uses one of the 23, and no other package registers or
  renders them.
- 42 `<core:icon>` tags in 15 files render them, all with
  `alternativeMarkupIdentifier="inline"`: 7 in
  `Partials/Profile/PublicProfile/{Contact,ProfileEntries}.html` of persons
  (no `size`), 35 in 13 files of persons-edit (32 with `size="small"`). There is
  no inline `{core:icon()}` form. None of the 15 files declares the
  `academic_base` namespace today. Ten declare `xmlns:core`, three rely on the
  global namespace, and `Documents/Editor.html` and
  `Documents/ContractContactEditor.html` declare `xmlns:core` without using
  it. No file uses another `core:` ViewHelper. The comment of
  `Contact.html:8-12` describes `Configuration/Icons.php` and the global `core`
  namespace.
- No TypeScript module builds or looks up an icon. `fields.ts`
  (`createActivateButton()`), `prototypes.ts` and `documents.ts`
  (`insertDocumentRow()`) clone server rendered markup that contains the
  icons: `ButtonTemplates.html`, the `helptext-button`, `contact-section` and
  `contact-row` prototypes, and the document row template. `common.ts` toggles
  `hidden` on the template owned `data-pe-view-icon` and
  `data-pe-visibility-icon` spans around the icons.
- `profile-detail.scss:36-49` (and the compiled `profile-detail.css:14-24`)
  selects `.icon`, `.icon-markup` and `.icon svg`. The persons-edit stylesheet
  selects no icon class.
- Tests: `ProfileEditingIconsTest` asks the core `IconRegistry` and
  `IconFactory` for the sixteen identifiers.
  `AcademicPersonsEditProfileEditingTest` scans the Fluid sources for
  `<core:icon … identifier>` against `Configuration/Icons.php` and asserts
  `data-identifier` on the rendered page, also through XPath (`:327`,
  `:1126-1130`). `AcademicPersonsPublicProfilePluginTest` asserts
  `data-identifier` for the seven icons and no `default-not-found`. The
  JavaScript fixtures replace the icons with marker elements and do not depend
  on the markup.
- `ace-tbd-frontend-icon-registry` (#112) provides `Configuration/FrontendIcons.php`,
  a ViewHelper of `academic_base` whose markup equals `core:icon`'s for the
  same provider and source, the `default-not-found` answer for an unknown
  identifier, and a testing-helper trait for the frontend registry.

## Goals / Non-Goals

**Goals:**

- The backend registry of both packages holds backend icons only.
- A visitor and a site stylesheet see no difference: same identifiers, same
  files, same markup.

**Non-Goals:**

- Changing the markup, the arguments or the identifiers. A leaner markup or
  the #617 renaming are changes of their own.

## Decisions

### Move the 23 icons, do not register them twice

They are frontend only, verified per identifier, so they go to
`FrontendIcons.php` and leave `Icons.php`, the rule the drafting decisions set
for frontend-only icons. Each file keeps the comment of today's block, rewritten
for the frontend registry. `persons_edit_icon` stays the only entry of
persons-edit's `Icons.php`, whose "fourteen action icons" comment goes.

Rejected: registering them in both files. It would keep the documented
`Icons.php` recipe working for one release, but leave a site that replaces an
icon with two registrations to maintain and the backend registry carrying 23
icons it never shows, which is what this round removes.

### Swap the tag, keep every argument

Each `<core:icon` becomes `<ab:icon`, with `identifier`,
`alternativeMarkupIdentifier="inline"` and `size` unchanged. The files declare
`xmlns:ab="http://typo3.org/ns/FGTCLB/AcademicBase/ViewHelpers"` (none has a
prefix for that namespace today) and drop `xmlns:core`, including the two
unused declarations. The comment of `Contact.html` is rewritten.

Rejected: dropping `alternativeMarkupIdentifier` because the shipped provider
renders the same markup with and without it. A site package that replaces an
icon with core's `SvgIconProvider` gets an `<svg>` with the argument and an
`<img>` without it, so dropping it changes what such a site renders. Dropping
`size="small"` is rejected for the same byte identity: it is the default of the
argument, and the class `icon-size-small` stays either way, but the diff should
be the tag name and nothing else.

### No TypeScript and no stylesheet change

The modules clone markup and never read the icon inside it, and the markup is
byte identical, so nothing in `Resources/Private/TypeScript/`,
`Resources/Private/Scss/` or the committed build output changes.
`checkJsBuildClean` and `testJs` staying green is the evidence. Only comments
change: the fixture comments of `Tests/JavaScript/Fixtures/profile-editing.ts`
(`:13`, `:241`) name the new tag, and the two that describe a
`<template data-pe-icon>` mechanism that never existed
(`contract-contacts-element.test.ts:305-310`,
`document-editor-element.test.ts:400-404`) are corrected.

### Tests against the frontend registry, each with its inverse

`ProfileEditingIconsTest` and a new `PublicProfileIconsTest` of persons assert,
per spelled-out identifier, the frontend registration with
`CurrentColorSvgIconProvider`, the inlined markup and the rendered identifier
through the #112 trait, and that the identifier is not in the core
`IconRegistry`. The inverse also covers `persons_icon` and `persons_edit_icon`:
in the core registry, not in the frontend one. The Fluid scan of
`AcademicPersonsEditProfileEditingTest` reads `FrontendIcons.php` and matches
`<ab:icon`, and fails on any `<core:icon` left in the editor's sources. The
prototypes test gains the icons each cloned prototype carries. The rendered
tests keep their two assertions and gain one exact wrapper string per
extension, which pins the markup the stylesheets select.

Rejected: reading the identifier list out of `FrontendIcons.php`. A rename
then agrees with itself, which `docs/architecture/icons.md` rules out.

### Amend the unreleased entries, add one Breaking entry per extension

The 3.0 entries that say "re-register it in `Configuration/Icons.php`"
(`Feature-ProfileEditingIconSet.rst`, `Feature-ConfigurablePublicProfile.rst`,
`Breaking-ReplacedProfileEditingPlugin.rst`) describe a state that was never
released, so they are amended in place, with the counts fixed. The two new
Breaking entries address sites that replaced an icon or override a template,
whatever release they come from.

Rejected: leaving the Feature entries as written and letting the Breaking
entries correct them. A reader of the Feature entry would follow a recipe that
silently does nothing.

## Risks / Trade-offs

- [A site package's `Icons.php` replacement stops reaching the frontend without
  an error] → Both Breaking entries lead with it and give the file to move it
  to. A check in `academic:upgrade:check` would find it, named as a non-goal.
- [An override on `core:icon` renders the not-found icon, inside a cloned
  prototype once per row] → Breaking entry of persons-edit names the three
  prototype partials. The icon is visible, not empty.
- [#112 renders different markup than assumed] → Task 1.1 compares the
  wrapper first and stops the change if it differs.
