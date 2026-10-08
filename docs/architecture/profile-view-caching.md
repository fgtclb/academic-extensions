# Profile view caching

The list and the detail plugin of `academic_persons` are cached with their page. They
tag the page, so a change of a profile can reach the cached pages without flushing the
whole page cache. This page lists the tags, every write path that flushes them, and the
views that carry no tag at all.

## The tags

| Tag                         | Set by                              | Meaning                         |
|-----------------------------|-------------------------------------|---------------------------------|
| `profile_list_view`         | `ProfileController::listAction()`   | a page that lists profiles      |
| `profile_detail_view`       | `ProfileController::detailAction()` | a page that shows any profile   |
| `profile_detail_view_<uid>` | `ProfileController::detailAction()` | a page that shows profile `uid` |

The `uid` is the one of the default-language record, for a translation too: Extbase
keeps it in `uid` and the uid of the translation in `_localizedUid`. The combined list
and detail element runs the same actions and carries the same tags.

## Who flushes them

| Write                                           | Flushed by                                        |
|-------------------------------------------------|---------------------------------------------------|
| A profile created or saved in the backend       | `Hook\DataHandlerHooks`, after the database write |
| A profile deleted or restored in the backend    | `Hook\DataHandlerHooks`, after the command        |
| Any record of a profile written through Extbase | `EventListener\FlushProfileViewCaches`            |

The hooks flush the tags in every cache, the listener in the caches of the `pages`
group only. The hooks flush the detail tag of the parent of a translation as well. A created
profile, a localized one included, reaches the hook with its `NEW…` id, which the
DataHandler has replaced by the uid in `substNEWwithIDs` by then. Before ACE-844 the
hook reacted to `update` only, so a profile created in the backend appeared on a cached
list only once the page cache expired, and the save of a translation flushed the detail
tag of the translation, which no page carries.

A DataHandler hook never sees an Extbase write. The automatic cache clearing of Extbase
flushes `pageId_<pid>` of the storage folder of a written record and of every numeric
page in the `TCEMAIN.clearCacheCmd` of that folder. TYPO3 v13 adds the tags `<table>`,
`<table>_<uid>` and `<table>_pid_<pid>` of the record, TYPO3 v12 does not. None of them
is a tag of the plugins. The profile editing frontend of `academic_persons_edit` writes
through Extbase, so before ACE-828 a change saved there stayed invisible on the cached
list and detail pages until the cache expired.

That automatic cache clearing runs only at the end of an Extbase bootstrap, a plugin or
a backend module, and only with `persistence.enableAutomaticCacheClearing`. It never
runs in a CLI command, so the profile synchronisation of the create and update profiles
commands flushed nothing at all before. This is why the listener flushes immediately,
per record, instead of pushing its tags onto the tag stack of the Extbase
`CacheService`: that stack exists on TYPO3 v13 only and is not processed on the CLI.

`FlushProfileViewCaches` listens to the three persistence events of Extbase: an entity
added, updated or removed. It resolves the profile of the written entity and flushes
`profile_list_view` and `profile_detail_view_<uid>` in the `pages` cache group:

- a profile is its own profile,
- a contract and a profile information have one,
- an address, an email address and a phone number reach it through their contract,
- a file reference is resolved from its row, since the Extbase model exposes neither
  the table nor the record. The row is read without restrictions because a removed
  reference is already marked deleted when the event is dispatched. A reference of a
  translated profile flushes the detail tag of the translation and of its parent.

Every other entity is left alone. An Extbase write of another extension costs an
`instanceof` chain, a file reference of another table one query to find out. The
events cover every Extbase write of these records, the profile synchronisation from
frontend users and the reordering of the editor through `PersistenceManager::update()`
included, which is why the editor controllers carry no flush of their own.

A write that touches several records flushes once per record: the listener keeps no
state to collect them. Only a changed record dispatches an event, Extbase writes a
record only when it has dirty properties, and each event costs one tag flush of the
caches in the `pages` group. That matters for the create and update profiles commands
on a large installation, where every changed profile flushes on its own.

The listener does not replace the DataHandler hooks and is not reached by them: the
backend never writes through Extbase.

## Views without a tag

The card, the selected profiles and the selected contracts element of
`academic_persons`, and the contacts of `academic_contacts4pages`, add no tag. Neither
a backend save nor an editor save flushes them. They follow the page cache of the page
they are on, a `TCEMAIN.clearCacheCmd` of the storage folder, or, on TYPO3 v13, the
automatic cache tagging of the core when `frontend.cache.autoTagging` is on.

## See also

- [Frontend-user contact import](frontend-user-contact-import.md) - the cleanup
  command, whose hide and delete reach the same tags through the DataHandler hooks
- [Translation synchronization](translation-synchronization.md)
- [Testing](../testing/Index.md)
