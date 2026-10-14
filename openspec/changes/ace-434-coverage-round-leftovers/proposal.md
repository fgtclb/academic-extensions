## Why

The test coverage round of August 2026 left six issues of small defects,
dead code and slips (ACE-434, ACE-435, ACE-436, ACE-441, ACE-443, ACE-444).
Parts are done elsewhere since, the rest is still open on `main` and easier
to close together. Two of them change what an integrator observes.

## What Changes

- ACE-434: the program page data and the list demands of
  `academic_programs`, `academic_partners` and `academic_projects` no longer
  raise PHP warnings for a record without the expected keys. The persons part
  of the issue is taken by the change of ACE-438.
- ACE-435: a redirect page of 0 or below, set by a listener of the job
  after-save event, counts as no redirect page for the redirect and the
  message alike.
- ACE-436: the uid of an unsaved partner, and uid and pid of an unsaved
  project, read as empty instead of failing.
- ACE-441: the persons profile builds its HTML sanitizer per setter call
  instead of keeping one per process.
- ACE-443: **BREAKING** the unused file reference collection of
  `academic_programs` is removed. The dead null check of the partner select
  goes, which ACE-672 left in place, and **BREAKING** `findAll()` of the
  partner repository declares the result interface it returns. The missing
  test attribute was added by ACE-748, the test is only shown to fail here.
- ACE-444: wrong file headers and a misspelled `@todo` are corrected. Two
  fields of the persons settings on one column keep giving the column the
  flags of the later field, which the manual now says, and the upgrade check
  and the persons settings status name such a column. **BREAKING**: the
  misspelled synchronizer namespace of `academic_persons` and the lower case
  start date sorting constant of `academic_projects` are renamed.

Each issue is one commit that resolves its key. The behaviour is the same on
TYPO3 v13 and v14.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-jobs/new-job-form`: what happens after saving when a listener
  hands in a redirect page that is no page.
- `academic-base/upgrade-configuration-check`: a column that two fields of
  the persons settings share is reported.
- `academic-persons/settings-overrides`: the persons settings status names
  the same column.

## Impact

- `academic_programs` (`packages/fgtclb/academic-programs`),
  `academic_partners` (`academic-partners`), `academic_projects`
  (`academic-projects`), `academic_persons` (`academic-persons`),
  `academic_persons_edit` (`academic-persons-edit`), `academic_jobs`
  (`academic-jobs`), `academic_base` (`academic-base`) and
  `academic_contacts4pages` (`academic-contact4pages`, tests only).
- Five phpstan baseline entries per core version disappear.
- `Changelog/3.0/`: one `Important` entry in `academic_jobs`, and one
  `Breaking` entry each in `academic_programs`, `academic_partners`,
  `academic_persons` and `academic_projects`.
- Backport to branch `2`, a change of its own: ACE-434, ACE-435 (in the jobs
  controller there) and ACE-436, ACE-443 with a deprecation instead of the
  removal and with the factory method open only there, the header and strict
  types slips of ACE-444, and ACE-441 only together with ACE-425. No rename.

## Non-goals

- The ACE-443 item on the unreachable validator exception, proposed as won't
  fix.
- The non-nullable parent setters of five persons models (ACE-444), proposed
  as won't fix.
- Strict types in the data processors of partners and projects, which change
  scalar coercion and deserve a change of their own.
- The sanitizer preset of the core for profile texts (ACE-441), and the
  encoded links of ACE-425.
