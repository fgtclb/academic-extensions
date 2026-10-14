## Why

The test coverage round of August 2026 left six issues of small defects,
dead code and slips (ACE-434, ACE-435, ACE-436, ACE-441, ACE-443, ACE-444).
`main` closed them in `ace-434-coverage-round-leftovers`, archived there as
`openspec/changes/archive/2026-10-14-ace-434-coverage-round-leftovers/`. This
change backports them to 2.4, which is not released yet, re-derived for TYPO3
v12 and v13 rather than copied.

## What Changes

- ACE-434: the program page data of `academic_programs` and the list demands
  of `academic_programs`, `academic_partners` and `academic_projects` no
  longer raise PHP warnings for a record without the expected keys. The
  persons part was done by the backport of ACE-438.
- ACE-435: a redirect page of 0 or below, set by a listener of the job
  after-save event, counts as no redirect page for the redirect and the
  message alike.
- ACE-436: the uid of an unsaved partner, and uid and pid of an unsaved
  project, read as empty instead of failing.
- ACE-441: the persons profile builds its HTML sanitizer per setter call
  instead of keeping one per process. The output is unchanged.
- ACE-443: the unused file reference collection of `academic_programs` is
  **deprecated** for removal in 3.0, not removed. An unused factory method of
  `academic_persons_edit`, `@internal`, is removed.
- ACE-444: wrong file headers, a misspelled `@todo` and the missing strict
  types of one settings class are corrected, a profile information type
  without a field name is no longer valid, and the persons manual says which
  of two spellings of one property decides the column.

Each issue is one commit that resolves its key. The behaviour is the same on
TYPO3 v12 and v13. Nothing is breaking, and nothing is renamed.

## Capabilities

### New Capabilities

- `academic-jobs/new-job-form`: what happens after saving when a listener
  hands in a redirect page that is no page. No spec exists for the form on
  this branch yet.

### Modified Capabilities

None.

## Impact

- `academic_programs` (`packages/fgtclb/academic-programs`),
  `academic_partners` (`academic-partners`), `academic_projects`
  (`academic-projects`), `academic_persons` (`academic-persons`),
  `academic_persons_edit` (`academic-persons-edit`) and `academic_jobs`
  (`academic-jobs`). Not `academic_base` and not `academic_contacts4pages`,
  whose part of the `main` change concerns code that only exists there.
- Four phpstan baseline entries fewer in `Core12` and in `Core13`.
- `Changelog/2.4/`: one `Important` entry in `academic_jobs`, one
  `Deprecation` entry in `academic_programs`.
- A new fixture extension in `academic-jobs` for the functional test of
  ACE-435.

## Non-goals

- The removal of the collection, the declared return type of `findAll()` of
  the partner repository and both renames of ACE-444: 3.0 only.
- The upgrade check and the status report entries of ACE-444 item 8: their
  infrastructure does not exist on this branch.
- ACE-443 item 4 and ACE-444 items 6, 12 and 13, won't fix, and ACE-444
  item 9, the documented 2.x contract.
- Strict types in the other six files without them, which change scalar
  coercion in data processors.
- ACE-425, the encoded links of the profile sanitizer.
