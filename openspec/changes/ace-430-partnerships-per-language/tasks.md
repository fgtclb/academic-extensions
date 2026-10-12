## 1. Premises and the decision

- [ ] 1.1 Confirm `design.md` decision 4 (no upgrade wizard) with the
  maintainer. If he chooses a wizard or an upgrade check finding, update the
  spec, the design and this list through `/opsx:update` first. Record the
  answer in the pull request.
- [ ] 1.2 Re-read the premises of `design.md`, Context, on HEAD against both
  installed cores: the repository and its two pinned tests, the callers and
  the page uid they pass on a translated page, the language block of the data
  mapper, and `ContactRepository` as the pattern. Stop and report if one has
  changed.

## 2. Tests first

- [ ] 2.1 Flip the two pinned tests of `PartnershipRepositoryFindByPidTest`
  to the default language result: one object, uid 1, localized uid 1, role
  "Research partner". Record them red on v13 and v14 before the change.
- [ ] 2.2 Add a fixture with a translated partner page and, for one partner
  page: a partnership translated onto the page translation, one with a copy
  on the default page, one untranslated, one of the German language only, one
  for all languages and a hidden one, with a translated role. Add the cases of
  `design.md` decision 5 in the German language aspect and in a language
  without translations, `count()` against the iteration included. Run them
  before the change on v13 and on v14 separately and record which fail on
  which version and why.
- [ ] 2.3 Add a functional test of the partner page or the partnership list
  in German through a frontend request, asserting each partnership once with
  the translated role, so the result is proven where the visitor sees it and
  not only on the repository. Show it red before the change.

## 3. Implementation

- [ ] 3.1 In `packages/fgtclb/academic-partners/Classes/Domain/Repository/PartnershipRepository.php`,
  resolve the uids per language in a private pre-query and fetch them with the
  overlay pinned to `OVERLAYS_MIXED`, as `design.md` decisions 1 to 3 describe,
  with a docblock that names ACE-430 and the contacts as the model. Verify
  every test of group 2 is green on v13 and v14.
- [ ] 3.2 Verify `ContactRepository`, the TCA and `ext_tables.sql` are not
  part of the diff.
- [ ] 3.3 Check what a frontend preview with hidden records shows before and
  after the change (`design.md`, Risks). If it differs, name it in the
  changelog entry.

## 4. Documentation

- [ ] 4.1 Add `packages/fgtclb/academic-partners/Documentation/Changelog/3.0/Important-PartnershipsRenderOncePerLanguage.rst`
  from `Build/Documentation/Templates/`: each partnership once per language,
  the role in the language of the translation, the fallback setting of the
  site no longer deciding, no cleanup needed, and a reference to the queries
  of `Important-TranslatedPartnershipsKeepTheirRole` for installations that
  want to delete the copies. Check that it renders without a definition list.
- [ ] 4.2 `docs/architecture/translation-synchronization.md`, "The read side
  converges on `OVERLAYS_MIXED`": name the partnerships next to the page
  contacts and the difference of decision 3.

## 5. Branch `2`

- [ ] 5.1 Not part of this pull request. The backport is a change of its own
  on `2`, derived from a backport analysis, and covers the partnerships and
  the repository half of ACE-484 for the page contacts, as `proposal.md`
  says. Leave this unticked here.

## 6. Definition of done

- [ ] 6.1 After `composerUpdate` for TYPO3 v13: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` on SQLite (`-j auto`), PostgreSQL
  (`-b docker -j 8`) and MariaDB (`-j 8`) green.
- [ ] 6.2 After `composerUpdate` for TYPO3 v14: the same gates green.
- [ ] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.4 `docs/` updated, `README.md` and `CONTRIBUTING.md` still only
  summarize and link.
- [ ] 6.5 Commit as `[BUGFIX] ACE-430: Show each partnership only once`
  in TYPO3 Core format, body at 72 columns, footers `Resolves: ACE-430`,
  `Related: ACE-484` and `Related: ACE-874`, one key per line, no
  attribution of any kind.
- [ ] 6.6 Archive the change as the last commit of the same pull request.
