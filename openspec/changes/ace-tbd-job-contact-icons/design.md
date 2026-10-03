## Context

See `proposal.md` for the motivation. On `main` (428cf1a32):

- `packages/fgtclb/academic-jobs/Resources/Private/Partials/Job/Contact.html:27`
  renders `<core:icon identifier="phone" alternativeMarkupIdentifier="inline"/>`,
  `:39` the same with `mail`. The partial is rendered by `Templates/Job/Show.html`.
- No `Configuration/Icons.php` of this repository registers `phone` or `mail`.
  `cms-core/Resources/Public/Icons/T3Icons/icons.json` of v13.4.35 (787 icons)
  and v14.3.7 (796 icons) carry `actions-phone` and `actions-envelope`, neither
  `phone` nor `mail`, also not among its `aliases`, and no system extension
  registers them.
- `IconFactory::getIcon()` replaces an identifier that is neither registered
  nor deprecated by `IconRegistry::getDefaultIconIdentifier()`, which is
  `default-not-found` (v13 `IconFactory.php:72-74`, v14 `:58-60`). The wrapper
  then carries `data-identifier="default-not-found"` and the class
  `icon-default-not-found` (v13 `Icon.php:284,295`, v14 `Icon.php:242,253`).
  That is the observable failure the new test asserts against.
- `academic-jobs/Configuration/Icons.php:78-85` registers
  `academic_jobs-contactEmail` (`Icons/Email.svg`) and
  `academic_jobs-contactPhone` (`Icons/Phone.svg`) with core `SvgIconProvider`.
  Nothing renders them.
- The property icons of the same view (`Partials/Job/Information.html:28`) use
  `<core:icon identifier="academic_jobs-{item}"/>` without an alternative markup.
  With `SvgIconProvider` and the ViewHelper's default size `small` that is
  `<img src="…" width="16" height="16" alt="" />` (`SvgIconProvider.php`, v13
  `:37`, v14 `:35`, `IconSize` small is 16 on both).
- `Email.svg` and `Phone.svg` carry `width="24px" height="24px"` and
  `fill="#5f6368"`. The `inline` markup of `SvgIconProvider` inlines the file as
  it is (v13 `AbstractSvgIconProvider::getInlineSvg()`, v14 the same through the
  SVG sanitizer), and no stylesheet of `academic_jobs` sizes an icon.
- Core merges every package's `Configuration/Icons.php` with `array_merge()` in
  package order (`AbstractServiceProvider::configureIcons()`, both cores), so a
  site package that depends on `academic_jobs` replaces its registration.
- No test of `academic_jobs` asserts an icon. `AcademicJobsListAndDetailPluginTest`
  renders the contact block from `jobPages.csv` (job 1: phone `+49 89 1234`,
  `ada@example.org`, work location) and `jobPages_contactPhone.csv` (job 2: an
  e-mail address, no phone).

## Goals / Non-Goals

**Goals:**

- A stock installation shows a real phone and e-mail icon in the contact block,
  on v13 and v14, with no template, registry or provider difference between them.
- The partial that #115 moves to the frontend registry already renders
  registered identifiers, so #115 changes the ViewHelper and the registry file
  only.

**Non-Goals:**

- No change to `Configuration/Icons.php`, no new artwork, no `currentColor`
  provider for these two. Artwork and identifiers are the icon consolidation's
  (#617), the registry move is #115.

## Decisions

### Render the shipped identifiers

`Contact.html` renders `academic_jobs-contactPhone` and
`academic_jobs-contactEmail`. Both are registered, their files exist, and they
stay inside the extension's own identifier prefix.

Rejected: register `phone` and `mail` in `academic_jobs`. Unprefixed
identifiers collide with the site packages that already register them, and the
winner depends on package order, so a site would get either glyph without
saying which. Rejected: leave the identifiers and document that a site has to
register them. That keeps the placeholder as the default of every stock site,
which is the defect.

### Default markup, not `inline`

The two tags drop `alternativeMarkupIdentifier="inline"` and are written exactly
like the property icons of `Information.html`. With `SvgIconProvider` they then
render as a 16 px `<img>`, the size and the form of every other icon of the
detail view.

Rejected: keep `inline`. It would inline `Phone.svg` and `Email.svg` at their
own 24 px, beside 16 px property icons, and nothing in the extension sizes an
inlined icon. A site that registers its own file with a provider that inlines in
both markups, `CurrentColorSvgIconProvider` of `academic_base` for example,
still gets an inlined `<svg>`, so dropping the argument takes nothing away from
a site that wants one.

### Two test classes, one fixture extension

The shipped icons are asserted in `AcademicJobsListAndDetailPluginTest`, on the
existing fixtures, scoped to the contact block (`academic-jobs-contact`): the
wrapper of each row carries the shipped identifier and an `<img>` of
`Phone.svg` respectively `Email.svg` at 16 by 16, and the block contains no
`default-not-found`. The size scenario compares with the work location icon of
the same response.

The replacement needs a package that loads after `academic_jobs`, which only a
fixture extension provides: `test_job_contact_icon` (`tests/job-contact-icon`),
requiring `fgtclb/academic-jobs`, with a `Configuration/Icons.php` that
registers its own SVG under `academic_jobs-contactPhone`. It is loaded by a
class of its own, because a fixture extension applies to every test of the
class that names it. When #115 moves the registration, it renames that file to
`FrontendIcons.php` and the test keeps proving the documented recipe.

Rejected: registering the icon at runtime from the test. It bypasses the
package order the recipe depends on, and #112 rules out runtime registration
for the frontend registry anyway.

### An `Important` entry, not `Breaking`

Nothing that worked on a stock installation stops working. The entry
(`Important-JobContactBlockShowsItsOwnIcons.rst`) tells a site that registered
`phone` or `mail` for this block that the block shows the shipped files now,
and how to keep its own: register them under the two `academic_jobs-*`
identifiers in `Configuration/Icons.php`. #115 moves the identifiers to
`FrontendIcons.php` and amends this unreleased entry in the same change, as it
does with `Important-RecordIconsFollowTheColourScheme.rst`.

## Risks / Trade-offs

- [A site package keeps registering `phone` or `mail` and expects the block to
  show it] → The `Important` entry names both identifiers and the replacement.
  The registrations themselves stay harmless, other templates may use them.
- [A site stylesheet selects `.icon-phone`, `.icon-mail` or an inlined `<svg>`
  in the contact block] → Named in the entry. The wrapper is the same, only the
  identifier class and the inner element change.
- [The `<img>` keeps the grey of its file and does not follow the text colour]
  → Same as the property icons today. A colour that follows the text is the
  artwork question of #617.
- [A site that overrides `Partials/Job/Contact.html` keeps rendering `phone`
  and `mail`] → Intended, its own registrations keep serving it. The entry says
  how to adopt the fix.
