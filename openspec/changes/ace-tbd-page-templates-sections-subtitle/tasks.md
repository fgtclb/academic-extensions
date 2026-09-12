## 1. Tests first

- [ ] 1.1 Extend `Tests/Functional/Pages/AcademicPartnerPageTemplateTest.php`
  and `AcademicProjectPageTemplateTest.php` with a filled and an empty
  subtitle. Record that the filled case fails against the unchanged
  templates.
- [ ] 1.2 Add a fixture partial path above key `100` with its own
  `Partner/Page/Header.html` and `Project/Page/Facts.html`, and assert that
  they replace only their section. Record that both fail today.
- [ ] 1.3 Add a PAGEVIEW fixture site package with the page content data
  processor and two content elements in colPos 0. Assert the content, the
  subtitle and, for projects, the page title as heading fallback. Record
  which cases fail against the unchanged templates; the fallback is expected
  to fail, and a content case that passes today pins the behaviour instead.
- [ ] 1.4 Keep the existing FLUIDTEMPLATE assertions unchanged, as the proof
  that the markup order did not move.
- [ ] 1.5 Add a case to both page template tests whose setup omits
  `Configuration/TypoScript/ContentLoad/setup.typoscript`, with one visible
  colPos 0 record, a second one in manual order, a colPos 1 record and a
  translated page: assert HTTP 200, both records in order, no colPos 1
  record and the translated content. Record that it fails on the unchanged
  templates with the `f:cObject` exception. Skip it if
  `ace-tbd-page-template-category-block` landed first with the same case.
- [ ] 1.6 In both extensions, change `Tests/Functional/SiteSet/SiteSetDeliveryTest.php`
  to assert that the content-load set is not registered and that a site on
  the aggregate set has no `styles.content.getContent` (replacing
  `contentLoadOverrideIsDeliveredByItsOwnSetOnly()` and the probe line in
  `Fixtures/TypoScript/Probe.typoscript`), and
  `Tests/Functional/Tca/StaticRegistrationTest.php` to assert that the
  content load static template is not registered. Record that the
  assertions fail on the unchanged code.
- [ ] 1.7 Add a delivery case with a site configuration that still lists the
  removed set next to the component sets: assert that the partner or
  project page renders with HTTP 200 on v13 and v14, which pins the silent
  drop the `Breaking-` entries describe. If core raises an error instead,
  correct the entries and `design.md` before implementing.

## 2. Implementation

- [ ] 2.1 Unless `ace-tbd-page-template-category-block` introduced them
  already, add `page.10.variables.partnerContent` and `projectContent`
  (`CONTENT` on `tt_content`, colPos 0, `orderBy = sorting, uid`) inside the
  doktype conditions of `AcademicPartners.typoscript` and
  `AcademicProjects.typoscript`.
- [ ] 2.2 Split `AcademicPartner.html` into the five `Partner/Page/`
  partials with the resolved page record; `Content.html` renders the content
  area or `partnerContent` and never `styles.content.getContent`. Verify
  tasks 1.1 to 1.5 for partners on v13 and v14.
- [ ] 2.3 Split `AcademicProject.html` into the five `Project/Page/`
  partials in the same way, with `projectContent`; verify tasks 1.1 to 1.5
  for projects on v13 and v14.
- [ ] 2.4 Drop the explicit content-load include from the existing page
  template tests and verify they still pass. Put the `f:cObject` call back
  once, watch task 1.5 go red, restore.
- [ ] 2.5 In both extensions remove `Configuration/Sets/ContentLoad/`,
  `Configuration/TypoScript/ContentLoad/`, the content-load dependency in
  `Configuration/Sets/Full/config.yaml`, the `ContentLoad` line of
  `Configuration/TypoScript/Full/include_static_file.txt` and the
  `addStaticFile()` call of the content load override in
  `Configuration/TCA/Overrides/sys_template.php`; adjust the comments there
  that mention the override. Verify tasks 1.6 and 1.7 pass on v13 and v14,
  and grep that neither extension references the set or the folder any
  more (the `2.4` changelog entries stay as history).
- [ ] 2.6 Adjust the comment on the "-content-load" sets in
  `core-13/config/sites/academics/config.yaml` and
  `core-14/config/sites/academics/config.yaml`, which calls leaving one out
  a fatal error. `ace-tbd-program-page-content-without-getcontent` touches
  the same comment; whichever lands last removes it entirely.

## 3. Documentation

- [ ] 3.1 Document the sections and how to replace one in
  `packages/fgtclb/academic-projects/Documentation/Templates/` and in
  `packages/fgtclb/academic-partners/Documentation/Configuration/Index.rst`.
- [ ] 3.2 Add `Documentation/Changelog/3.0/Feature-PageTemplateSectionsAndSubtitle.rst`
  to both extensions, naming the partials and the PAGEVIEW heading fix, and
  verify both `3.0` indexes list them.
- [ ] 3.3 Add `Documentation/Changelog/3.0/Breaking-ContentLoadSetRemoved.rst`
  to both extensions from `Build/Documentation/Templates/Changelog-Breaking.rst`:
  the removed set, static template and aggregate dependency; the silent
  drop of an unknown set name from a site configuration on v13 and v14; the
  page template that no longer renders `styles.content.getContent`; the
  migration (remove the set or static template entry, switch a customised
  or overridden `styles.content.getContent` to
  `page.10.variables.partnerContent` / `projectContent`, or define
  `styles.content.getContent` in the site package when a site template keeps
  rendering it). If `ace-tbd-page-template-category-block` already shipped a
  `Breaking-` entry for the template switch, link it instead of repeating
  it. Check the reST over/underline lengths and verify both `3.0` indexes
  list the entry.
- [ ] 3.4 Replace the content load parts of
  `Documentation/Configuration/Index.rst` in both extensions (the set table
  row, the section "The content load override" and the static template row):
  the page type needs no set, the page content is the page variable, and
  the override no longer exists.
- [ ] 3.5 Add the difference between `data` and `page` on the two page object
  types, and the page-scoped content variables that replace
  `styles.content.getContent`, to `docs/architecture/typoscript-and-site-sets.md`.

## 4. File the issue

- [ ] 4.1 After implementation, file the ACE issue in YouTrack, verify the key
  and rename the change to `ace-<NNN>-page-templates-sections-subtitle`.
- [ ] 4.2 Commit in TYPO3 Core format `[!!!][FEATURE] ACE-<NNN>: <subject>`,
  subject at most 52 characters, body wrapped at 72; the body names the
  removed `styles.content.getContent` call.

## 5. Definition of done

- [ ] 5.1 `Build/Scripts/runTests.sh -t 13 -s composerUpdate`, then
  `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` with `-t 13` green.
- [ ] 5.2 The same for `-t 14` after its own `composerUpdate`.
- [ ] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 5.4 `docs/` and both `Documentation/` changelog entries are part of the
  change; `README.md` and `CONTRIBUTING.md` still only summarize and link.
- [ ] 5.5 Archive the change as the last commit of the pull request and
  verify both delta specs landed in `openspec/specs/`.
