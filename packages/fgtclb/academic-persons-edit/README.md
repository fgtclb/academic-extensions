# TYPO3 Extension `Academic person database - frontend editing` (READ-ONLY)

|                  | URL                                                                        |
| ---------------- | -------------------------------------------------------------------------- |
| **Repository:**  | https://github.com/fgtclb/academic-persons-edit                            |
| **Read online:** | https://docs.typo3.org/p/fgtclb/academic/academic-persons-edit/main/en-us/ |
| **TER:**         | https://extensions.typo3.org/extension/academic_persons_edit/              |

## Description

This extension extends the `academic_persons` extension by the option to edit profiles in the frontend.
Profiles get connected with a frontend user and the frontend user is allow to edit its assigned profiles.

The `Inline profile editing` content element provides a responsive, Bootstrap
5 based overview of all profiles assigned to the authenticated frontend user.
Each row shows the profile image, complete name and site-language label. The
`View` action opens the public `academic_persons` `Detail` plugin on the same
page used by Academic Persons list views. The target comes from
`plugin.tx_academicpersons.detailPid`; the `Edit` action opens the selected
profile in the inline editor.
The editor saves only changed profile fields through a JSON endpoint without
reloading the page. On large viewports the profile image
column remains sticky while the personal data scrolls. Its runtime top offset
tracks the rendered border-box height of `#page-header`, including responsive
and scroll-dependent height or padding changes. On smaller viewports the
layout collapses to one column. Read values
are plain text with a borderless pencil action instead of button-like value
controls. Related name and link properties are presented as groups and edited
together.

The profile name is displayed as the full-width main heading above both editor
columns. The compact `Private` switch and the small `Edit all` toggle sit in the
same responsive heading row. The image column starts with `Profile image`, and
the data column starts with `Personal data`. The switch keeps using its dedicated
`skipSync` AJAX endpoint. The toggle changes to `Close all` while the editors are open. Closing
all editors keeps unsaved browser-side drafts; persistence remains available
only through each field's own save action. A small pencil button in the
upper-right corner of the profile image
opens a Bootstrap 5 modal: selecting a file updates the local modal preview,
saving uploads and replaces the page preview, and deleting removes the current
image. Upload errors retain their non-success HTTP status, are shown inside the
open modal and do not change the page preview. Bootstrap provides the layout;
when `special.image.renderType` is `cropper`, the locally shipped CropperJS
module constrains the selection to `special.image.settings.ratio` before the
cropped file is uploaded. The selection remains movable, and the cropper stays
inactive for the fallback image until a real file is selected. The upload
writes the composed profile name to both
the FAL alternative text and title metadata.

The existing small compatibility stylesheet only adjusts surrounding overflow,
frame spacing and sticky-card stacking. The templates require no inline styles.
Every Bootstrap button and modal surface in the shipped Fluid views uses
`rounded-0` for consistent square corners.
Authentication, profile ownership, configured validators, file validation and
the TCA allow lists of all configured select fields are checked server-side.

The profile fields configured with `renderType: ckeditor` use TYPO3's bundled
CKEditor 5; in the shipped settings four appear in the `information` section
and `miscellaneous` appears in `aboutme`. They are saved through the same
partial-update endpoint. Submitted rich text is
sanitized server-side with an explicit tag, attribute and URI-scheme allowlist
before it is validated and persisted. Rich-text fields show their formatted
content directly with a compact pencil control. While editing, separate delete,
cancel and save actions clear the draft, restore the last persisted value or
persist the field through AJAX. For CKEditor fields only, this action group is
placed beside the field heading so the editor keeps the complete row width;
ordinary field controls retain their existing action placement. See the
[inline-editing documentation](./Documentation/InlineEditing/Index.rst) for the
editor, AJAX and security contracts.

The inline controller consumes the ordered `profile`, `special`,
`contractContact` and `documentSections` configuration from
`Configuration/AcademicsPersonsEdit/Settings.yaml`. This edit graph is loaded
and cached independently from the public `academic-persons` profile layout.
Fluid fields are selected by `renderType`, select options remain in TCA, and
the JavaScript entry delegates to focused feature modules.
Direct public-profile email/telephone values and their opt-in flags stay
separate from Contract contact data. Validation and structured-section metadata
remain attached to their respective section. These editor rules drive only the
frontend controls, JSON metadata and server-side validation; they never
override backend TCA.

Editable structured document sections have an add button beside their heading.
Their compact row values and ordered controls follow `rowFields` and `actions`
from the edit settings. Sections marked `readonly`, including the shipped
contracts section, expose viewing only. Editable lists can be reordered with
the configured up/down controls or by dragging the additional sort handle. The
full row is used as the drag image, the source row and active list are outlined,
and a strong insertion line marks whether the row will be placed before or after
the current target. The shared Bootstrap modal uses full-width CKEditor 5
controls for HTML descriptions. Its heading uses a record's non-empty `title`
and falls back to the section heading. Delete mode renders the modal submit as
`btn-danger` and explicitly removes primary or success styling. Ownership- and
capability-checked JSON actions complete the workflow without calling the
retained legacy controllers or reloading the page.

The `date` flag on configured `from`, `to` and `year` validators renders a
native date picker. Profile-information records persist the complete selected
calendar date in native `DATE` fields; no time value is stored. The three date
fields and the compact year-only checkbox each use `col-12 col-md-3` and share
one responsive modal row. `required` is taken from the same settings, so the
shipped `year` field is mandatory while `from` and `to` are optional. Required
markers are generated from that metadata, not from field names. A per-record
`Show year only` switch controls presentation without discarding the stored
month or day.

Select and checkbox controls save on change and expose a compact undo action
that restores the last persisted value and closes the inline editor. Frontend
unit and DOM interaction tests are isolated in
`Resources/Public/Development/`; run `npm i` and then `npm test` in that
directory.

> [!NOTE]
> This extension is currently in beta state - please notice that there might be changes to the structure

## Compatibility

| Branch | Version     | TYPO3                | PHP                                          |
| ------ | ----------- | -------------------- | -------------------------------------------- |
| main   | ^3, 3.x-dev | v13.4.31+ + v14.3.6+ | 8.2, 8.3, 8.4, 8.5                           |
| 2, 2.x | ^2, 2.x-dev | v12 + v13            | 8.1, 8.2, 8.3, 8.4, 8.5 (depending on TYPO3) |
| 1      | ^1, 1.x-dev | v11 + v12            | 8.1, 8.2, 8.3, 8.4 (depending on TYPO3)      |

## Installation

Install with your flavour:

- [TER](https://extensions.typo3.org/extension/academic_persons_edit/)
- Extension Manager
- composer

We prefer composer installation:

```bash
composer req \
  'fgtclb/academic-persons':'^2' \
  'fgtclb/academic-persons-edit':'^2'
```

> [!IMPORTANT]
> `3.x.x` is still in development and not all academics extension are fully tested in v13,
> but can be installed in composer instances to use, test them. Testing and reporting are welcome.

**Testing 3.x.x extension version in projects (composer mode)**

It is already possible to use and test the `2.x` version in composer based instances,
which is encouraged and feedback of issues not detected by us (or pull-requests).

Your project should configure `minimum-stabilty: dev` and `prefer-stable` to allow
requiring each extension but still use stable versions over development versions:

```shell
composer config minimum-stability "dev" \
&& composer config "prefer-stable" true
```

and installed with:

```shell
composer require \
  'fgtclb/academic-persons':'3.*.*@dev' \
  'fgtclb/academic-persons-edit':'3.*.*@dev'
```

## Upgrade

Upgrading between major versions can include breaking changes, which have to be
addressed manually where no automatic upgrade path is available. They are
documented per version in [Documentation/Changelog](./Documentation/Changelog).

## Credits

This extension was created by [FGTCLB GmbH](https://www.fgtclb.com/).

[Find more TYPO3 extensions we have developed](https://github.com/fgtclb/).

## Supported Versions

| Version | Supported          | End of Support |
|---------|--------------------|----------------|
| 3.x     | :white_check_mark: | 2029-06-30     |
| 2.x     | :white_check_mark: | 2027-12-31     |
| < 2.0   | :x:                | support ended  |

The newest line listed above is under development on the default branch and has not been released yet.

## Security

Found a vulnerability? Please report it privately via our
[security report form](https://security.fgtclb.com) — **do not** open a public issue.
See [SECURITY.md](SECURITY.md) for the full vulnerability disclosure policy,
including what to expect and our safe harbor statement.

## Simplified EU Declaration of Conformity (Annex VI)

> Hereby, web-vision GmbH declares that the product with digital elements
> type FGTCLB: Academic Persons Edit is in compliance with Regulation (EU) 2024/2847.
>
> The full text of the EU declaration of conformity is available at the
> following internet address:
> https://security.fgtclb.com/conformity/fgtclb/academic-persons-edit/3.0.0/en/

The full declarations are also included in this repository:
[English](EU-Declaration-of-Conformity.md) ·
[Deutsch](EU-Konformitaetserklaerung.md).

## License

This extension is released under the [GPL-2.0-or-later](LICENSE) license.
