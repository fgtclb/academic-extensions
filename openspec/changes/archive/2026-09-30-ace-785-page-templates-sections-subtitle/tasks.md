## 1. Tests first

- [x] 1.1 Add `Tests/Functional/Pages/AcademicPartnerPageLayoutTest.php` and
  `AcademicProjectPageLayoutTest.php` on the model of
  `AcademicProgramPageLayoutTest`: the layout `Default` of a FLUIDTEMPLATE
  site package, of a PAGEVIEW site package at `paths.10` and at `paths.100`,
  another layout through the constant and through the site setting, an empty
  setting, a site package without a layout, a header (partners) or facts
  (projects) override at `75`, a whole template override, and the subtitle
  filled and empty on both page object types. Record which fail on the
  unchanged code.
- [x] 1.2 For projects, assert the page title as heading fallback on a
  PAGEVIEW page object. Record that it fails today.
- [x] 1.3 In both `*PageTemplateTest.php`, drop the content-load include and
  add the main column cases of `AcademicProgramPageTemplateTest`: manual
  order, ties by uid, no other column, no hidden element, PAGEVIEW, the
  translated page, an integrator adjusting the variable, no variable on other
  page types, and a site on site sets. Record that they fail on the unchanged
  templates with the `f:cObject` exception.
- [x] 1.4 Keep the existing FLUIDTEMPLATE assertions, as the proof that the
  parts did not move.
- [x] 1.5 In both extensions, invert `SiteSetDeliveryTest`,
  `StaticRegistrationTest` and the probe comment as `ace-721` did for programs,
  and drop the content-load include from `*LabelOverrideTest.php`. Record that
  the assertions fail on the unchanged code.
- [x] 1.6 Add both removed sets to the `ConfigurationCheckerSiteTest` case of
  removed sets, and record that it fails before `REMOVED_SETS` names them.
- [x] 1.7 Add a PAGEVIEW site package fixture that assigns a variable `data`
  of its own, and assert the heading and the subtitle of both page types with
  it. Record that the unchanged processors fail with a type error, and that
  reading `data` first in the template or in the processor turns it red.

## 2. Implementation

- [x] 2.1 In both page objects: the variables `partnerPageLayout` /
  `projectPageLayout` and `partnerContent` / `projectContent`, the paths at
  `50`, the fallback layout at `-1758484901` / `-1758484903`, and no
  `layoutRootPaths.100`.
- [x] 2.2 The constant and the site setting `page.layout` in both extensions,
  with the same default.
- [x] 2.3 Split `AcademicPartner.html` into the five `Partner/Page/`
  partials inside the section `Main`, with the resolved page record and the
  subtitle, and add the fallback layout.
- [x] 2.4 The same for `AcademicProject.html` and the `Project/Page/`
  partials.
- [x] 2.5 Remove the content-load sets, their TypoScript, the aggregate
  dependency, the static include line and the static template registration in
  both extensions, and adjust the comments that mention them. Grep that
  neither extension references the set or the folder any more, apart from the
  changelog entries of version 2.4.
- [x] 2.6 Add both sets to `ConfigurationChecker::REMOVED_SETS`.
- [x] 2.7 Remove the comment on the content-load sets from
  `core-13/config/sites/academics/config.yaml` and
  `core-14/config/sites/academics/config.yaml`, which no longer applies.
- [x] 2.8 Let `PartnerProcessor` and `ProjectProcessor` read the page record
  from `page` first and from `data` otherwise, as the templates do.

## 3. Documentation

- [x] 3.1 `Documentation/Configuration/Index.rst` of both extensions: replace
  the content load parts with the page content, the page layout and the parts
  of the page, as in the programs manual. For projects, link it from
  `Documentation/Templates/`.
- [x] 3.2 `Breaking-ContentLoadSetRemoved.rst` and
  `Breaking-PartnerPageRendersInsideTheSiteLayout.rst` /
  `Breaking-ProjectPageRendersInsideTheSiteLayout.rst` in both
  `Documentation/Changelog/3.0/`, from the templates in
  `Build/Documentation/Templates/`. Check the reST over and underline lengths.
- [x] 3.3 `docs/architecture/page-type-rendering.md`: the pattern holds for all
  three page types, `data` and `page` on the two page object types, and the
  page content variables. `docs/architecture/shared-partials.md`: the partial
  that renders the image. `docs/architecture/upgrade-checks.md`: the removed
  sets. `docs/testing/functional-tests.md`: the measured counts.

## 4. Definition of done

- [x] 4.1 `Build/Scripts/runTests.sh -t 13 -s composerUpdate`, then
  `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` (SQLite and
  PostgreSQL) with `-t 13` green.
- [x] 4.2 The same for `-t 14` after its own `composerUpdate`.
- [x] 4.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 4.4 `docs/` and the `Documentation/` changelog entries are part of the
  change, and `README.md` and `CONTRIBUTING.md` still only summarize and link.
- [x] 4.5 Commit as `[!!!][FEATURE] ACE-785: <subject>`, subject at most 52
  characters, body wrapped at 72, naming the removed
  `styles.content.getContent` call.
- [x] 4.6 Archive the change as the last commit of the pull request and verify
  the delta specs landed in `openspec/specs/`.
