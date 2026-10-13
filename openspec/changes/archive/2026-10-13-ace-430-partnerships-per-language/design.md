## Context

See `proposal.md` for the motivation. Read on `main` at `e75f86b95` and on
`origin/2` at `7ebded7b8`, against the installed TYPO3 13.4.35 and 14.3.7, on
2026-10-12. Every point is a premise to measure again before code is written.

- `PartnershipRepository::findByPid()`
  (`academic-partners/Classes/Domain/Repository/PartnershipRepository.php:21-52`,
  byte-identical on `2`) forces `LanguageAspect::OVERLAYS_ON` (`:25-32`),
  lifts `respectSysLanguage` (`:33`) and the storage page (`:34`), matches
  `page = $pid` only (`:36-38`) and orders by `sorting` and `uid` (`:46-49`).
- Its callers pass the default language page uid: `PartnershipProcessor.php:33`
  (the current page record) and `PartnerController.php:171` and `:200` (the
  `pid` of the content element).
- The defect is pinned in the default language, with no language aspect set:
  `PartnershipRepositoryFindByPidTest::aTranslatedPartnershipIsReturnedNextToItsDefaultLanguageRecord()`
  (`:152`, uids `[1, 1]`, localized uids `[1, 2]`) and
  `theTranslatedPartnershipStillCarriesTheDefaultLanguageRole()` (`:178`).
  No test covers a translated language.
- The partnership table: `hidden` is its only enable column, `l10n_mode`
  `exclude` like `partner` and `role`. `page` is a `group` column without
  `l10n_mode`. The table is workspace aware and sorted by `sorting`.
- Translating the partner page localizes its inline partnerships onto the
  page translation (`TranslatedPartnershipsRoleTest.php:32` and `:52-53`,
  `page => 12` for page 10). Those rows never match the default page uid. A
  visitor reaches them only through the overlay of their default record.
- Rows that do match in another language: copies created by localizing a role
  until ACE-874 (`a4f130f11`), and a partnership localized on its own in the
  List module, which copies `page` verbatim. The 2.4 changelog entry
  `Important-TranslatedPartnershipsKeepTheirRole.rst` already lists the
  copies of ACE-873 and ACE-874 with SQL queries and asks to delete them by
  hand.
- ACE-484 (`cb0cef0e2`) fixed the same query of the page contacts:
  `ContactRepository::findByPid()` (`:53-104`) resolves one uid per contact
  and language in a raw pre-query (`:127-177`), then fetches exactly those
  uids through Extbase with the overlay pinned to `OVERLAYS_MIXED`.
  `docs/architecture/translation-synchronization.md`, "The read side
  converges on `OVERLAYS_MIXED`", explains why the pin makes v13 and v14
  agree.
- With `respectSysLanguage` lifted on the parent query, the data mapper
  resolves a relation in the language of the parent object
  (`DataMapper.php:468-480` on v13, `:498-510` on v14). That is what makes
  the role of a partnership that renders as its translation follow the
  translation.

## Goals / Non-Goals

**Goals:**

- One row per partnership and language, the same on v13 and v14.
- Tests in a translated language, which do not exist today.

**Non-Goals:**

- Touching `ContactRepository`.
- Any DataHandler hook, TCA or schema change.

## Decisions

### 1. The pattern of ACE-484

`findByPid()` resolves the matching uids per language first, with the rule
of `ContactRepository::resolveContactUidsForLanguage()`: records in language
"all", in the default language only default records, in a translated
language its own records (translations and records of that language only)
plus the default records without a translation there, the lowest uid winning
when one record has several translations in one language. The Extbase query
then fetches exactly those uids with `respectSysLanguage` lifted and the
overlay pinned to `OVERLAYS_MIXED`, keeps the `sorting`, `uid` order, and
matches `uid = 0` for an empty list, as the contacts do.

The pre-query applies what the Extbase query applies to this table:
`DeletedRestriction`, the `WorkspaceRestriction` of the current workspace and
`HiddenRestriction`, the last one unless the visibility of the context
includes hidden content, as a frontend preview of hidden records does. The
Extbase frontend query honours that visibility for the `hidden` column, so the
pre-query does too. The partnership has no option of its own to show hidden
records.

Rejected: respecting the language of the site instead, by dropping the lifted
`respectSysLanguage`. No overlay type selects records of a language only
together with the untranslated default records, v13 and v14 treat the
overlay types differently, and the partnerships would then follow other rules
than the page contacts of the same page tree.

### 2. A private method, not a shared service

The pre-query is a private method of `PartnershipRepository`, mirroring the
one of `ContactRepository`.

Rejected for now: one stateless service in `academic_base` for both
repositories. It would touch a finished and tested repository of another
extension inside a partners change, and the two already differ, the contacts
having the option to show hidden records. A third repository with the same
need would make the extraction worth it. Name this in the pull request so
the reviewer can ask for it.

### 3. Which row stands for a partnership

When a default record has both a translation on the page translation and a
copy on the default page in the same language, the pre-query sees only the
copy, and the copy decides the position of the partnership through its
`sorting`, as a translated page contact follows the `sorting` of its
translation. The Extbase overlay then maps the row through its default record
and looks the translation up by `l10n_parent` and language without an order
(`PageRepository::getLanguageOverlay()`), so which of the two the object is
mapped from belongs to the database: SQLite returned the copy, PostgreSQL the
translation, found by the PostgreSQL gate. Both carry the partner and the role
of the default record, since those columns are not translated, so the visitor
sees the same partnership. The test of this case asserts one object in the
position of the copy and leaves the localized uid open.

### 4. No upgrade wizard for copies made before ACE-874 (maintainer's decision: no wizard)

- **No wizard (recommended).** This change stops the copies from rendering
  twice, which is the defect a visitor sees. A translated partnership carries
  nothing of its own, so a copy left in the database changes nothing. The
  2.4 entry `Important-TranslatedPartnershipsKeepTheirRole.rst` already names
  the copies, gives the query to find them and asks to delete them by hand.
  ACE-484 decided the same for the contacts. A new wizard would also add a
  call site to the API that blocks the TYPO3 v15 migration (ACE-294).
- **An upgrade wizard that deletes them.** It cleans the data, but it has to
  tell a copy from a translation an editor made on purpose in the List
  module, which has the same shape, and it adds the call site named above.
- **A finding of `academic:upgrade:check`** (ACE-712). It lists the copies on
  every run without deleting anything. More visible than a changelog query,
  and more code than this defect needs.

The maintainer chose no wizard before the implementation.

### 5. The role groups compare the role by uid (found in the implementation)

The partnership list and teaser group the partnerships by role. The
controller and `PartnershipProcessor` collect the roles keyed by uid, and the
templates compare `{partnership.role} == {role}`, the role objects as a
whole. A partnership that renders as its translation carries the role in the
visitor's language, an untranslated one the role of the default language, and
the identity map of Extbase holds them as two objects. The one collected last
named the group, and the partnerships carrying the other object fell out of
the list. Measured in German with two translated partnerships and one
untranslated of one role: TYPO3 v13 listed only the untranslated one, before
and after this change. TYPO3 v14 listed the translated ones before, because
it dropped the untranslated one, and would list only the untranslated one
after it.

The templates compare `role.uid`, and the roles are collected by one small
stateless service used by the controller and the processor, which keeps the
role object in the visitor's language when a group has both. Maintainer's
decision during the implementation.

Rejected: resolving the role of an untranslated partnership in the visitor's
language. The data mapper resolves a relation in the language of the parent
record, so that needs a second lookup around it, and it changes the role
requirement, which names the default language role for such a partnership.

### 6. Tests

- The two pinned tests flip: in the default language one object, uid 1,
  localized uid 1, role "Research partner". They are the red proof, red today.
- New cases in the German language aspect, the way
  `ContactRepositoryFindByPidTest::setUpLanguageAspect()` sets it: a
  partnership translated onto the page translation (renders once, as its
  translation, with the translated role), a copy on the default page
  (renders once), an untranslated partnership (renders as its default record),
  a record of that language only, a record for all languages, a language
  without any translation, a hidden partnership, and `count()` against the
  iteration in every language. Each case that fails today is recorded red
  before the change on v13 and v14, separately, since the issue measured the
  two differently.
- If the role of a partnership that renders as its translation does not
  follow the translation after the change, stop: the role requirement and
  this design are updated first.

## Risks / Trade-offs

- [A copy and a proper translation coexist] → Decision 3: one renders, both
  show the same partner and role. The changelog query finds the copies.
- [The pre-query and the Extbase query disagree on visibility] → Both apply
  `deleted`, `hidden` and the workspace. Measured on TYPO3 v14 in a frontend
  request whose visibility includes hidden content: the query before the
  change returned the hidden partnership, and a pre-query that filtered
  `hidden` always did not, so the pre-query follows the visibility of the
  context. Found in the implementation and covered by a repository test.
- [TYPO3 v13 names the default language role after an untranslated
  partnership] → The data mapper of v13 resolves a relation from the identity
  map by the plain uid (`DataMapper.php:742` on 13.4.36), so a role loaded in
  the default language for an untranslated partnership is handed to every
  later partnership with that role, translated or not. v14 keys the identity
  map by language (`DataMapper.php:771` on 14.3.8). Core behaviour, older than
  this change, pinned per version in the repository test and named in the
  changelog entry. The fixture of the language cases sorts the untranslated
  partnership last, so the role requirement is asserted the same on both.
- [A site language without fallback now shows untranslated partnerships] →
  It is the behaviour of the page contacts since ACE-484, and a translated
  partnership has no content of its own. The `Important` entry says it.

## Migration Plan

None required. The copies stay in the database and stop rendering twice. The
`Important` entry in `Changelog/3.0/` points at the queries of the 2.4 entry
for installations that want to delete them.
