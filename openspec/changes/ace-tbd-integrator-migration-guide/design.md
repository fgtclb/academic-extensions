## Context

See `proposal.md` for the motivation. On main:

- `academic-persons/Documentation/Upgrade/Index.rst` orders six steps for
  persons and the profile editor. `academic_base`'s manual has no upgrade
  section.
- The `Documentation/guides.xml` of the manuals declare only core
  inventories, so a `:ref:` cannot cross from one manual into another.
- The facts the chapters rest on were verified in the sources:
  - `academic_base` removed `Environment\*` and `FileUploadConverter`
    (`Changelog/3.0/Breaking-Removed*.rst`).
  - `TsConfig/` became `TSconfig/` in nine extensions
    (`Important-PageTsConfigDirectoryRenamed.rst`).
  - Every content element is hidden by `Configuration/page.tsconfig` and
    re-enabled by its component set.
  - `ExtensionManagementUtility::addPiFlexFormValue()` is deprecated on v14
    (#107047), and `TcaManipulator::addContentElementPluginFlexForm()` covers
    both versions.
  - `academic:persons:settings:migrate` exists.
  - `Important-PageTemplateNameIsSetExplicitly.rst` (programs, partners,
    projects) already states that an override named `Academicprogram.html` is
    no longer used. What it lacks is why such an override still resolves
    where the static template of the Bootstrap Package is parsed after the
    sets, and that moving that package to its site set (ACE-601) changes it.
  - The profile editor renders the synchronisation toggle only when
    `special.skipSync` is configured (`Partials/Profile/Header.html:26`). Its
    endpoint accepts `skipSync` only as an editable special field
    (`ProfileUpdateValidationService::getEditableProperties()`) and otherwise
    answers 422 `invalid_profile_data`; this is a code reading, confirmed by a
    request while writing.
  - `academic_programs` names the category types `program_type` and
    `begin_program` (`Configuration/CategoryTypes.yaml`); the unit labels are
    `credits` (study plan) and `program.creditPoints` (programs).

## Goals / Non-Goals

**Goals:**

- One entry point, ordered, with every step linking its owning changelog.
- Extension detail stays in the extension's manual.

**Non-Goals:**

- Describing the TYPO3 core update itself.

## Decisions

### Entry point in `academic_base`, chapters in the owning manuals

`academic-base/Documentation/Upgrade/Index.rst` carries the ordered steps:

1. core and PHP floor;
2. plugin migration wizards, still on v13;
3. database compare;
4. removed `academic_base` APIs;
5. site sets or static templates, one mechanism per site;
6. `TSconfig/` imports;
7. hidden content elements;
8. FlexForm registration of project plugins;
9. settings migration of persons;
10. page templates and include order;
11. removed content-load sets and `styles.content.getContent`;
12. TYPO3's `#[AsEventListener]`;
13. template override check.

It then links the extension chapters: `academic-persons-edit/Documentation/Upgrade/Index.rst`
(new), the existing persons page, `academic-programs/Documentation/Upgrade/Index.rst`,
`academic-study-plan/Documentation/Upgrade/Index.rst` and a section in
`typo3-category-types/Documentation/`.

Rejected: one monolithic page in `academic_base`. Its detail would drift from
the changelogs it summarizes, and a reader of the programs manual on
docs.typo3.org would not find it. Rejected: a Markdown file in the mono
repository, which integrators do not read. Rejected: notes per project.

### Links between manuals are absolute

Cross-manual links use the rendered docs.typo3.org URL of the package,
together with the `:composer:` role the persons page already uses. Rejected:
inventories for the sibling manuals in `guides.xml`, which would make
`checkRstRenderingAll` depend on published renderings of 3.0 that do not
exist before the release.

### Profile editor chapter: intent to setting

A table maps each 2.x override intent to its 3.0 setting:

- no add, delete or sort: `actions`;
- a locked section: `readonly`;
- hiding the synchronisation toggle: remove `special.skipSync`;
- hiding a field: remove it from the profile map;
- labels: the `profileEditing.*` keys;
- icons: `Icons.php`.

It names the gaps without a replacement (`ace-tbd-managed-fields-editor`,
`ace-tbd-editor-before-write-event`, `ace-tbd-editor-custom-profile-fields`)
and lists the 2.x override paths with their fate.

### Pending changes are labelled

A chapter that depends on a change not released when the guide is written
(`ace-tbd-program-finder-element`, `ace-tbd-program-application-link`,
`ace-tbd-study-plan-decimal-credit-points`,
`ace-tbd-study-plan-partials-js-contract`, `ace-tbd-study-plan-asset-switch`,
`ace-tbd-keep-hidden-ctype-selectable`, `ace-tbd-legacy-typoscript-paths`,
`ace-tbd-program-facts-field-list`,
`ace-tbd-program-page-content-without-getcontent`,
`ace-tbd-page-templates-sections-subtitle`,
`ace-tbd-upgrade-check-template-overrides`,
`ace-tbd-upgrade-check-configuration`) carries a note naming it as not yet
available.

### Decided: the static template step names the 2.x paths as deprecated

The 2.x TypoScript paths stay until 4.0 (`ace-tbd-legacy-typoscript-paths`),
so step 5 documents them as deprecated and points to the "All components"
static template, or to the site set, as the replacement. Keeping the paths
turns the step from "your site renders nothing" into a deprecation to clean
up.

### Decided: decimal credit points are a 3.0 topic only

The credit points change of `ace-tbd-study-plan-decimal-credit-points` ships
on `main` only; it is not backported to branch `2`. The 3.0 study plan
chapter covers it, and the branch `2` backport of the guide neither mentions
decimal credit points nor announces a schema change for them. Whether a 2.x
minor release may carry a schema change therefore has no subject in this
guide.

### Decided: the content-load sets are documented as removed, not deprecated

The three content-load sets (`fgtclb/academic-partners-content-load`,
`fgtclb/academic-programs-content-load`,
`fgtclb/academic-projects-content-load`) and the programs partial
`Partials/Program/Categories.html` are removed in 3.0 as a breaking change,
not deprecated for 4.0. Step 11 of the guide therefore documents a removal and
its migration:

- the sets redefined the global `styles.content.getContent` for every page of
  a site, and the aggregate set of each extension depended on them;
- the upstream page templates of programs, partners and projects render their
  content without that path in 3.0, so a site using them needs nothing;
- a site template or project page template that still renders
  `styles.content.getContent` defines it itself, and the guide shows the
  definition the sets carried;
- a project override of `Partials/Program/Categories.html` moves to the facts
  partial of `ace-tbd-program-facts-field-list`.

The removals are owned by three changes, and the step links their `Breaking-`
entries, which own the detail:

- `ace-tbd-program-page-content-without-getcontent` removes the
  academic_programs set;
- `ace-tbd-program-facts-field-list` removes `Partials/Program/Categories.html`;
- `ace-tbd-page-templates-sections-subtitle` removes the academic_partners and
  academic_projects sets and switches their page templates off
  `styles.content.getContent`.
 The first draft asked whether to deprecate the sets for removal in
4.0; removing them in the unreleased 3.0 spares projects a second migration.

### Category type rename by SQL

`UPDATE sys_category SET type = 'program_type' WHERE type = 'course_type'`,
and the same for `begin_course`. The statement covers translations and
workspace versions, because they carry the same `type`. Rejected: an
`Install\Updates` wizard, see Non-goals.

## Risks / Trade-offs

- [The guide goes stale] → `docs/workflow/changelog-and-documentation.md`
  makes a step in the guide part of every future breaking change.
- [Absolute links break when a manual moves] → They point at package URLs,
  which docs.typo3.org keeps stable.

## Open Questions

None.
