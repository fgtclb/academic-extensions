## 1. `[FEATURE] ACE-584: Ship a shared icon set` (academic_base, testing helper, docs)

- [x] 1.1 Ship the 38 files of the shared set in `academic-base/Resources/Public/Icons/{action,state,info}/` with `LICENSE-font-awesome.txt`, and register them in `Configuration/FrontendIcons.php` next to `default-not-found`, verified by `SharedIconsTest` (registration, provider, not a backend icon, house format, `ab:icon` markup), shown red by an unlisted registration, a file without `width`/`height` and a registration with the core provider
- [x] 1.2 Add `FrontendIconRegistry::getAllRegisteredIconIdentifiers()`, verified by the unit tests `allRegisteredIconIdentifiersAreListedInRegistrationOrder` and `aCachedEntryIsListedWithoutBuilding`, shown red by sorting the result
- [x] 1.3 Add the backend checks to `ColourSchemeAwareIconsTrait` (house format, naming of `Icons.php` with optional groups, house format walk, type ownership), the frontend checks to `FrontendIconsAssertionTrait` (house format, naming of `FrontendIcons.php` with optional groups, house format walk) and `IconFilesAssertionTrait` (orphan check over both registries, notice check), verified by `IconRulesTest` against the fixture `test_icon_rules`, each check shown red by a broken fixture
- [x] 1.4 Rewrite `docs/architecture/icons.md` (scheme, group rule, shared set, files, house format, licence, the checks), update `docs/testing/testing-helper.md`, `docs/testing/fixture-extensions.md`, the trait counts in `docs/` and `AGENTS.md`, verified by `lintMarkdown -n`
- [x] 1.5 Add the `Icons` chapter of the `academic_base` manual, `Feature-SharedIconSet.rst`, and move the examples of `Feature-CurrentColorSvgIconProvider.rst` and `Configuration/Index.rst` to a frontend icon, verified by `checkRstRenderingSingle academic-base`
- [x] 1.6 Write `proposal.md`, `design.md`, this task list and the delta spec `academic-base/shared-icon-set`, verified by `openspec validate ace-816-icon-consolidation --strict`

## 2. `[!!!][TASK] ACE-585: Use the shared icons in persons` (academic_persons)

- [x] 2.1 Render `tx-academicbase-info-{email,phone,location,room,time}` and `-action-{expand,collapse}` in the public profile, delete the persons `FrontendIcons.php` and its files, verified by the public profile plugin and icon replacement tests, shown red by an old identifier left in a partial
- [x] 2.2 Rename the record and content element icons to `tx-academicpersons-record-*` and `-plugin-*` in `Icons.php`, TCA and wizard TSconfig, verified by `RecordIconsTest` and `ContentElementIconsTest`, shown red by a stale wizard `iconIdentifier`
- [x] 2.3 Fold the rename into `Breaking-PublicProfileIconsMovedToTheFrontendIconRegistry.rst` and write the delta spec `academic-persons/public-profile-icons`, verified by `checkRstRenderingSingle` and `openspec validate --strict`

## 3. `[!!!][TASK] ACE-586: Share the profile editor icons` (academic_persons_edit)

- [x] 3.1 Render the sixteen shared `tx-academicbase-action-*` and `-state-*` controls in every editor template and delete the editor `FrontendIcons.php`, verified by the editor rendering, prototype and icon replacement tests, shown red by an old identifier in a template
- [x] 3.2 Rename the plugin icon to `tx-academicpersonsedit-plugin-profile-editing`, verified by `ProfileEditingIconsTest` and the site set wizard test, shown red by the old `persons_edit_icon` registered again
- [x] 3.3 Fold the rename into `Breaking-ProfileEditingIconsMovedToTheFrontendIconRegistry.rst` and write the delta spec `academic-persons-edit/profile-editing-icons`, verified by `checkRstRenderingSingle` and `openspec validate --strict`

## 4. `[!!!][TASK] ACE-587: Use the shared icon set in jobs` (academic_jobs)

- [x] 4.1 Register the 17 property icons as `tx-academicjobs-info-<property>` drawing files of the shared set, render them inline, verified by `FrontendIconsTest` and the list and detail plugin tests, shown red by a property identifier left on its old name
- [x] 4.2 Rename the record and plugin icons, both drawing the shared briefcase `info/employment.svg`, verified by `RecordIconsTest` and `PluginIconsTest`, shown red by the job detail content element on the record icon, the plugin icon on the core provider, the record icon drawing another file and the job table on a core icon
- [x] 4.3 Fold the rename and the change from `<img>` to inline markup into `Breaking-JobIconsMovedToTheFrontendIconRegistry.rst`, write the delta specs `academic-jobs/job-detail` and `academic-jobs/job-contact`, verified by `checkRstRenderingSingle` and `openspec validate --strict`

## 5. `[!!!][TASK] ACE-588: Replace the BITE jobs icon` (academic_bite_jobs)

- [x] 5.1 Replace the plugin icon by `tx-academicbitejobs-plugin-bite-jobs`, TCA and wizard alike, verified by `PluginIconsTest` and the wizard registration test, shown red by a stale wizard entry
- [x] 5.2 Write the Breaking entry about the icon, verified by `checkRstRenderingSingle`

## 6. `[!!!][TASK] ACE-589: Use the shared contact icons` (academic_contacts4pages)

- [x] 6.1 Rename the plugin and record icons, the role icon drawing the shared file, verified by `RecordIconsTest`, shown red by an unregistered `typeicon_classes` value
- [x] 6.2 Write the Breaking entry about the icons, verified by `checkRstRenderingSingle`

## 7. `[!!!][TASK] ACE-590: Replace the partner icons` (academic_partners)

- [x] 7.1 Rename the plugin, page type and record icons, move the category type files to `Icons/category-type/`, the group icon to the plugin file, verified by `RecordIconsTest` and the category type frontend icon tests, shown red by the 2.x identifier `academic-partners` registered again and by the page type, a content element and a wizard entry naming another icon
- [x] 7.2 Fold the rename into `Breaking-RecordAndCategoryIconsFollowTheColourScheme.rst` and write the delta spec `academic-partners/partner-page`, verified by `checkRstRenderingSingle` and `openspec validate --strict`

## 8. `[!!!][TASK] ACE-591: Replace the program icons` (academic_programs)

- [x] 8.1 Rename the plugin and page type icons, replace the category type files in `Icons/category-type/` and the group file in `Icons/category-group/`, verified by `RecordIconsTest`, `FactIconsTest` and the program facts frontend icon test, shown red by the page type on the plugin icon, a wizard entry naming another icon, a category type file that does not exist and the standard period on a copy of the shared clock
- [x] 8.2 Fold the rename into `Breaking-CreditPointsIconIsAFrontendIcon.rst`, verified by `checkRstRenderingSingle`

## 9. `[!!!][TASK] ACE-592: Replace the project icons` (academic_projects)

- [x] 9.1 Register a plugin and a page type icon of the extension, replace the category type and group files, verified by `RecordIconsTest` and the category type frontend icon tests, shown red by a missing category type file, a wizard entry naming another icon and the page type icon on the core provider
- [x] 9.2 Fold the rename into `Breaking-CategoryIconsFollowTheColourScheme.rst` and write the delta spec `academic-projects/project-page`, verified by `checkRstRenderingSingle` and `openspec validate --strict`

## 10. `[!!!][TASK] ACE-593: Use shared icons in study plan` (academic_study_plan)

- [x] 10.1 Render `tx-academicbase-action-{expand,collapse,close}`, rebuild the stylesheet for the new wrapper classes and delete the study plan `FrontendIcons.php`, verified by the content element and icon replacement tests and `checkJsBuildClean`, shown red by an old identifier left in a partial and by the stylesheet selecting the old icon class
- [x] 10.2 Rename the record and plugin icons, verified by `RecordIconsTest`, shown red by a `typeicon_classes` value and a wizard entry naming another icon
- [x] 10.3 Fold the rename into the Breaking entry about the control icons and write the delta spec `academic-study-plan/frontend-markup-contract`, verified by `checkRstRenderingSingle` and `openspec validate --strict`

## 11. `[TASK] ACE-584: Enforce the icon rules everywhere`

- [x] 11.1 Call the naming, house format, type ownership, orphan and notice checks from the icon tests of every extension that ships icons, with the content element and page types each one registers, verified by the functional suite on both cores, each kind of check shown red in at least one extension by breaking one of its registrations, files or types
- [x] 11.2 Update `docs/architecture/icons.md`, `docs/testing/testing-helper.md` and `docs/testing/fixture-extensions.md` for the renamed extensions, and drop the `tt_content` exemption of the record type walk once no content element keeps the core provider, verified by `lintMarkdown -n` and the functional suite, the walk shown red by the profile editing content element icon on the core provider

## 12. `[TASK] ACE-594: Add an icon overview page`

- [x] 12.1 Add a page of the development seed that lists every icon of both registries, the frontend ones through `ab:icon` and the list method of the frontend registry, verified by its functional test and `LegacyDeliveryTest`, shown red by an identifier missing from the page
- [x] 12.2 Regenerate the seed manifests with `seedManifest` for both cores and the SQLite snapshots, verified by the seed verification tests

## 13. `[TASK] ACE-816: Archive the icon consolidation`

- [ ] 13.1 Archive this change with `openspec archive ace-816-icon-consolidation` as the last commit of the pull request, verified by `openspec validate --specs --strict` and the delta specs merged into `openspec/specs/`

## 14. Definition of done

- [ ] 14.1 `lintPhp`, `cgl -n`, `phpstan` and `unit` green for TYPO3 v13 and v14, each after its own `composerUpdate`
- [ ] 14.2 `functional` green for TYPO3 v13 and v14 on SQLite, MariaDB, MySQL and PostgreSQL
- [ ] 14.3 Every new or changed assertion shown red by breaking what it covers, listed per commit
- [ ] 14.4 `docs/` updated and `lintMarkdown -n` green, every extension's `Documentation/` with its Breaking entry about icons, `checkRstRenderingAll` green
- [ ] 14.5 Every commit message in TYPO3 Core format with its verified ACE issue, no attribution to a tool
