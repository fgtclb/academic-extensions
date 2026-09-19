## Why

Most list queries on this branch carry no `ORDER BY`, so their order is
whatever the database returns — insertion order on SQLite, MySQL and MariaDB
in practice, and not the same list twice on PostgreSQL. `main` fixed this in
ACE-482 and ACE-491, both before OpenSpec was set up there; neither reached
this branch. Four backports since (ACE-625, ACE-669, ACE-681, ACE-687) each
recorded the gap and left it out of scope; ACE-431 tracks it, and this change
closes it for everything `main` has fixed.

## What Changes

Affects TYPO3 v12 and v13 alike; no query differs between the two.

- `academic_persons` (`academic-persons`): the profile, contract, location,
  function type and organisational unit queries order by `uid` when nothing
  else asks for an order, a demanded profile ordering gets `uid` as a
  tiebreaker, and so does the manual selection query. Contract addresses and
  e-mail addresses break equal backend sorting by `uid`, as phone numbers
  already do.
- `academic_partners` (`academic-partners`): partnerships and partners follow
  the backend sorting with a `uid` tiebreaker; the geocoding queue is
  processed oldest first; the list gets a `uid` tiebreaker.
- `academic_programs` (`academic-programs`), `academic_projects`
  (`academic-projects`): the list gets a `uid` tiebreaker; program page media
  follow the order the editor arranged them in.
- `academic_jobs` (`academic-jobs`): jobs tied on start time follow `uid`.
- `category_types` (`typo3-category-types`): every category list follows the
  backend sorting of the categories, with a `uid` tiebreaker.

## Capabilities

### New Capabilities

None. `main` has no capability for the category, partnership or media orders
either — ACE-491 predates OpenSpec there — and specs stay in step with it.

### Modified Capabilities

- `academic-programs/program-list-sorting`: programs equal in the selected
  ordering render in the same relative order on every request, the scenario
  the ACE-625 backport left out because the tiebreaker was missing here.

## Impact

Repository queries of six extensions, their functional tests, one changelog
entry per affected behaviour in `Documentation/Changelog/2.4/`, rule 3 of
`docs/architecture/database-queries.md` and its summary in `AGENTS.md`.
No schema, TCA, TypoScript or template changes. Editors who reordered
categories or partnerships see the frontend follow that order.

## Non-goals

- Ordering the `findAll()` methods of the address, e-mail, phone number and
  profile information repositories, which `main` leaves unordered as well —
  the rest of ACE-431, to be fixed on `main` first.
- Reproducing an editor's uid selection order in the repository; the
  controllers keep restoring it in PHP.
- A `uid` tiebreaker for the contacts of `academic_contacts4pages`, which
  `main` lacks too.
