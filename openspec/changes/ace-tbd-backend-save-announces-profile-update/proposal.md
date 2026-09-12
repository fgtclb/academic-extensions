## Why

Saving a profile in the backend, or writing it through a DataHandler based
import, updates neither its translations nor its slug. The profile update
event is dispatched only by the frontend editor and the frontend-user
commands. Projects work around this with their own DataHandler hooks and a
faked frontend request. The translation listener also has to guess the site
from the global request.

## What Changes

- A backend save of a default-language profile announces the update once per
  profile and run. Translations and the slug follow, exactly as after a
  frontend edit.
- The profile update event carries the site of the profile and where the
  update came from: creation, synchronisation, frontend editing, backend,
  import or unknown. Every existing dispatcher passes its origin.
- An import marks its DataHandler run as an import. Its saves are announced
  like backend saves, synchronously and once per profile, with the origin
  import.
- The translation listener uses the site from the event and falls back to its
  current lookup only when none is given.
- The DataHandler runs the extensions start themselves are never announced a
  second time: the translation sync, and the image writes of the frontend
  editor. This prevents recursion and double dispatch.

This affects `academic_persons` (`packages/fgtclb/academic-persons`), which
has the event, the hook and the image writer, and `academic_persons_edit`
(`packages/fgtclb/academic-persons-edit`), which has the translation and slug
listeners. The behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-persons/profile-update-announcement`: when a profile update is
  announced, to whom, with which site and origin, and which writes are never
  announced twice.

### Modified Capabilities

None.

## Impact

- Listeners of the profile update event now also run for backend saves and
  DataHandler based imports. This is not an API break, but it gets an
  `Important-` changelog entry.
- The event gains optional constructor arguments, and a new origin enum is
  added. Existing listeners and dispatchers keep working.
- Bulk imports through DataHandler synchronise every touched profile in the
  same request.
- Depends on `ace-tbd-fresh-profile-is-not-a-translation`: without it, a
  profile created in the backend could be misreported as a translation.

## Non-goals

- Workspace saves and workspace publishing. Only live saves are announced.
- Batch or deferred synchronisation for large imports. Imports are
  synchronised per profile in the same request; listeners can tell them
  apart by the origin import.
- A backport to branch `2`, whose synchronizer is not routed through the
  DataHandler.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`persons-data-03`). Three of the six analysed projects are affected today:
one carries a DataHandler hook with request faking, one a command-line
workaround, and the third, whose import is DataHandler based, would get
translations and slugs from this change without code of its own. No
YouTrack issue is filed yet; the change is renamed to `ace-<NNN>-<slug>` when
the issue is filed after implementation.
