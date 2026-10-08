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

| Write                                                       | Flushed by                                        |
|-------------------------------------------------------------|---------------------------------------------------|
| A profile created or saved through the DataHandler          | `Hook\DataHandlerHooks`, after the database write |
| A profile deleted or restored through the DataHandler       | `Hook\DataHandlerHooks`, after the command        |
| A function type or organisational unit, through DataHandler | `Hook\DataHandlerHooks`, the lists only           |
| Any record of a profile written through Extbase             | `EventListener\FlushProfileViewCaches`            |

The DataHandler rows cover the backend, the import writer, and the writes of the profile
editor that go through the DataHandler on purpose: the profile image, the visibility of
the profile and the project profile fields. The hooks flush the tags in every cache, the
listener in the caches of the `pages` group only. A created profile, a localized one
included, reaches the hook with its `NEW…` id, which the DataHandler has replaced by the
uid in `substNEWwithIDs` by then. The hooks flush the detail tag of the parent of a
translation as well. Before ACE-858 the save or creation of a translation flushed the
detail tag of the translation only, which no page carries.

A DataHandler hook never sees an Extbase write. The automatic cache clearing of Extbase
flushes `pageId_<pid>` of the storage folder of a written record and of every numeric
page in the `TCEMAIN.clearCacheCmd` of that folder, plus the tags `<table>`,
`<table>_<uid>` and `<table>_pid_<pid>` of the record, on TYPO3 v13 and v14 alike. None
of them is a tag of the plugins.

What reaches a cached page anyway is the automatic cache tagging of the core, the
feature `frontend.cache.autoTagging`, on by default in an instance set up on TYPO3 v13.3
or later and off in an upgraded one. With it, the Extbase `Typo3DbBackend` tags the page
with `<table>_<uid>` of every row a query of the page read, and the automatic cache
clearing above flushes exactly that tag when the row is written. A change of a record
the cached page had read was therefore already visible there.

The profile editor of `academic_persons_edit` writes the profile fields, the contracts,
their addresses, email addresses and phone numbers and the profile information through
Extbase. Before ACE-858 a change saved there stayed invisible on the cached list and
detail pages until the cache expired whenever the automatic tagging did not cover it:
with the feature off, and for a new record, whose row no cached page had read. On a site
with translations the translation synchronisation after the save writes the translations
through the DataHandler, which flushed the list as a side effect.

That automatic cache clearing runs only at the end of an Extbase bootstrap, a plugin or
a backend module, and only with `persistence.enableAutomaticCacheClearing`. It never
runs in a CLI command, so the profile synchronisation of the create and update profiles
commands flushed nothing at all before, with or without the automatic tagging. This is
why the listener flushes immediately, per record, instead of pushing its tags onto the
tag stack of the Extbase `CacheService`, which is not processed on the CLI.

`FlushProfileViewCaches` listens to the three persistence events of Extbase: an entity
added, updated or removed. It resolves the profile of the written entity and flushes
`profile_list_view` and `profile_detail_view_<uid>` in the `pages` cache group:

- a profile is its own profile,
- a contract and a profile information have one,
- an address, an email address and a phone number reach it through their contract.

Every other entity is left alone, an Extbase write of another extension costs an
`instanceof` chain. The events cover every Extbase write of these records, the profile
synchronisation from frontend users and the reordering of the editor through
`PersistenceManager::update()` included, which is why the editor controller carries no
flush of its own.

A write that touches several records flushes once per record: the listener keeps no
state to collect them. Only a changed record dispatches an event, Extbase writes a
record only when it has dirty properties, and each event costs one tag flush of the
caches in the `pages` group. That matters for the create and update profiles commands
on a large installation, where every changed record flushes on its own: the profile,
its contract, and each of its addresses, email addresses and phone numbers.

The listener does not replace the DataHandler hooks and is not reached by them: the
DataHandler never writes through Extbase.

## Views without a tag

The card, the selected profiles and the selected contracts element of
`academic_persons`, and the contacts of `academic_contacts4pages`, add no tag. Neither
a backend save nor an editor save flushes them. They follow the page cache of the page
they are on, a `TCEMAIN.clearCacheCmd` of the storage folder, or the automatic cache
tagging of the core when `frontend.cache.autoTagging` is on.

## See also

- [Frontend-user contact import](frontend-user-contact-import.md) - the create, update
  and cleanup commands, whose writes reach the same tags
- [Translation synchronization](translation-synchronization.md)
- [The profile editing contract](profile-editing-contract.md)
- [Testing](../testing/Index.md)
