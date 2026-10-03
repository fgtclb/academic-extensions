## Why

The contact block of the job detail view of `academic_jobs`
(`packages/fgtclb/academic-jobs`) asks the icon registry for the identifiers
`phone` and `mail`. Neither TYPO3 v13.4.35 nor v14.3.7 registers them (not in
`T3Icons/icons.json`, not as an alias, not in any system extension), and no
extension of this repository does. A stock installation therefore shows TYPO3's
"icon not found" placeholder in front of the contact phone number and the
contact e-mail address. Sites only saw a real icon when their own site package
happened to register `phone` and `mail`, which is how it went unnoticed.

The extension ships the intended icons, `academic_jobs-contactPhone` and
`academic_jobs-contactEmail`, registered and never rendered. The frontend icon
round (#112 to #117) moves the jobs icons to a frontend registry in #115, so
this defect is fixed first, on today's registry, and #115 moves a working
partial.

## What Changes

- The phone row and the e-mail row of the job contact block show the phone and
  e-mail icons the extension ships, on TYPO3 v13 and v14 alike.
- They are rendered like the property icons of the same view, at the same size.
- `docs/architecture/icons.md` stops calling `phone` and `mail` core icons.
- An `Important-` changelog entry in `academic_jobs` tells sites that registered
  `phone` or `mail` for this block what changes for them.

## Non-goals

- Moving any jobs icon to a frontend registry, or rendering it through another
  ViewHelper. That is #115, after #112.
- New artwork, a colour that follows the text, or renaming identifiers. The
  icon consolidation (#617) is rebased after this round.
- Removing the three registered icons that stay unused
  (`academic_jobs-starttime`, `-contactName`, `-contactAdditionalInformation`).
  #115 decides that when it moves the registrations.
- Template overrides in site packages, which keep their own identifiers.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-jobs/job-contact`: a new requirement that the contact rows show the
  icons the extension ships. The existing requirement on the dialable phone link
  is unchanged.

## Impact

- `academic_jobs`: `Resources/Private/Partials/Job/Contact.html`, a functional
  test, the `Important-` changelog entry. `Configuration/Icons.php` is not
  changed, both identifiers are registered already.
- `docs/architecture/icons.md`.
- Behaviour is the same on TYPO3 v13 and v14.
- Integrators: a stock site needs nothing. A site package that registered
  `phone` or `mail` to give this block an icon now sees the shipped artwork in
  the block. To keep its own, it registers its file under
  `academic_jobs-contactPhone` or `academic_jobs-contactEmail` instead, and a
  stylesheet that selects the old identifiers in this block follows them. A site
  that overrides `Partials/Job/Contact.html` keeps its own output unchanged.
- Origin: the frontend icon analysis of 2026-10-03, side finding. Three analysed
  projects register `phone` in their site package, two of them `mail` as
  well.
