## Why

An action of an academic plugin that dispatches several events hands each of
them a context of its own. The partner and project lists build one context for
their demand and list events, and the plugin view event builds a second one
from the request and the settings. The persons plugins build one for each
query and another one for the view event, and the profile editor one for the
form data and another one for every write it announces. A listener that
follows one rendering through its events cannot rely on getting the same
context, and in the persons list the settings of the contexts differ: the
query sees the pagination switched on while the view event sees it switched
off under a letter.

## What Changes

- Every action builds its plugin context once, after its settings are
  settled, and hands the same context to every event it dispatches: the
  demand and list events of the partner and project lists, the query events
  and the page title event of the persons plugins, the write event of the
  profile editor, and the plugin view event.
- The persons list decides before its query that a letter filter switches off
  the pagination, so the one context carries that setting from the start.
- Affected extensions: `academic_base` (`packages/fgtclb/academic-base`),
  `academic_bite_jobs` (`academic-bite-jobs`), `academic_contacts4pages`
  (`academic-contact4pages`), `academic_jobs` (`academic-jobs`),
  `academic_partners` (`academic-partners`), `academic_persons`
  (`academic-persons`), `academic_persons_edit` (`academic-persons-edit`),
  `academic_programs` (`academic-programs`) and `academic_projects`
  (`academic-projects`).

Behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-base/plugin-action-context`: one context per rendering of a
  plugin, shared by all its events.

## Impact

- The internal helper that dispatches the plugin view event takes the context
  instead of the request and the settings. It is `@internal`, so no project
  calls it.
- A listener of a persons query event sees the pagination switched off while
  a letter is filtered, as the view event already did.
- The persons plugins hand their deprecated persons context to the view event
  as well. It implements the `academic_base` interface the event declares.

## Non-goals

- Carrying the context on events that no plugin action dispatches, such as
  the program page data event planned with the program events of ACE-766.
- Removing the deprecated persons context, which stays until 4.0 (ACE-747).
- Backporting to branch `2`.

## Source

Raised in the review of ACE-766. Implements ACE-767, a follow-up of ACE-750.
