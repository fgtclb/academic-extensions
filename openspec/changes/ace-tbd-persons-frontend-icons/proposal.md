## Why

`academic_persons` (`packages/fgtclb/academic-persons`) and
`academic_persons_edit` (`packages/fgtclb/academic-persons-edit`) register 23
icons in TYPO3's backend icon registry that only the frontend renders: the
seven icons of the public profile detail view and the sixteen action icons of
the profile editor. With the frontend icon registry of
`ace-tbd-frontend-icon-registry` (#112) in place, they move to it, so the
backend registry keeps only what the backend shows and an integrator replaces a
frontend icon in the file meant for it.

## What Changes

- **BREAKING** The seven public profile icons (`academic-persons-envelope`,
  `-phone`, `-address`, `-room`, `-clock`, `-detail-plus`, `-detail-minus`) and
  the sixteen `academic-persons-edit-*` action icons leave
  `Configuration/Icons.php` and are registered in `Configuration/FrontendIcons.php`
  only. Identifiers, files and provider stay the same.
- **BREAKING** The 42 icon tags of 15 templates and partials (2 of
  `academic_persons`, 13 of `academic_persons_edit`) render through the icon
  ViewHelper of `academic_base` instead of `core:icon`, with the same
  arguments. The markup stays byte identical, so the shipped stylesheet, the
  prototypes the editor clones in the browser and site stylesheets keep working.
- The record icons, `persons_icon` and `persons_edit_icon` stay backend icons
  in `Configuration/Icons.php`.
- The documented icon counts are corrected to sixteen and seven, and every
  manual page that tells integrators to re-register an icon in
  `Configuration/Icons.php` names `Configuration/FrontendIcons.php`.
- TYPO3 v13 and v14 behave the same.

## Non-goals

- Renaming identifiers. The icon consolidation (#617) renames them later, in
  both registries.
- `academic_contacts4pages` (`packages/fgtclb/academic-contact4pages`) and
  `academic_persons_sync` (`packages/fgtclb/academic-persons-sync`). Neither
  renders an icon in the frontend: contacts4pages has no `core:icon` and renders
  the persons item partials, which carry no icon, and persons_sync has no Fluid
  template. Both keep their backend icons as they are.
- The unused registration `tx_academiccontacts4pages_domain_model_contract`.
- A check of `academic:upgrade:check` for a site package that still replaces a
  moved icon in `Configuration/Icons.php`.

## Capabilities

### New Capabilities

- `academic-persons/public-profile-icons`: which icons the public profile
  detail view shows, how an integrator replaces them, and the markup a site
  stylesheet can rely on.
- `academic-persons-edit/profile-editing-icons`: the same for the action icons
  of the profile editor, including the controls the editor builds in the
  browser.

### Modified Capabilities

None. The office hours icon of `academic-persons/public-profile-contact` keeps
its requirement, only its registration moves.

## Impact

- Code: both `Configuration/Icons.php`, two new `Configuration/FrontendIcons.php`,
  15 Fluid files, the functional icon tests of both extensions, comments of the
  JavaScript test fixtures. No TypeScript, SCSS or compiled asset changes.
- Integrators: a site package that replaced one of the 23 icons in its
  `Configuration/Icons.php` silently gets the shipped artwork back and moves the
  entry to its `Configuration/FrontendIcons.php`. A template override that still
  renders one of them with `core:icon` shows TYPO3's not-found icon and switches
  to the `academic_base` ViewHelper. An analysed project does both today.
- Docs: a Breaking entry per extension, the amended unreleased 3.0 entries and
  manual pages, `docs/architecture/icons.md` and
  `docs/architecture/profile-editing-contract.md`.
- Depends on #112. 3.0.0, `main` only.
