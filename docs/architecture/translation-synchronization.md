# Translation synchronization

Some fields of a translated record are not translatable at all. A geographic
coordinate is the clearest case: a place does not move when the page is
translated, so the translation should carry whatever the default record carries.

TYPO3 spells that as `allowLanguageSynchronization` in the TCA `behaviour` of
the column.

```php
'geocode_latitude' => [
    'config' => [
        'type' => 'input',
        'behaviour' => [
            'allowLanguageSynchronization' => true,
        ],
    ],
],
```

## What it does, and the two things it does not

It is handled by `DataMapProcessor`, which runs **only on the DataHandler write
path**. Three consequences follow, and every one of them has cost a defect:

1. **It repairs nothing that is already stored.** A translation created before
   the default record had a value keeps its own — usually empty — value until
   somebody saves that record again. An upgrade wizard is what brings existing
   rows in line, once.
2. **A write that bypasses the DataHandler bypasses it.** An Extbase repository
   write, or a `Connection::update()`, reaches the default record and leaves
   every translation exactly as it was. Any code path that writes such a field
   in production has to go through the DataHandler, or the declaration applies
   to backend edits and to nothing else.
3. **`l10n_mode => 'exclude'` is not the same thing.** It says the field is not
   translatable either, but it hides the field from the editor entirely, so a
   deliberate exception becomes impossible. Synchronization keeps the field
   visible and lets an editor detach it, which writes `custom` into
   `l10n_state`.

Nothing has to be written into `l10n_state` for synchronization to start:
`Localization\State` treats a field with no stored state as `parent`, which is
the state a newly synchronized field needs.

## Writing from the CLI

A command has no backend user, and the DataHandler needs one. Supplying it is
three traps deep, and each costs a silent wrong result rather than an error:

- **Passing the user to `DataHandler::start()` is not enough.** Parts of the
  localization path go through `BackendUtility` helpers that read
  `$GLOBALS['BE_USER']` directly. The global has to be swapped in for the
  duration of the run and restored in a `finally`, including when the callback
  throws.
- **`BackendUserAuthentication::$workspace` defaults to `-99`**, not to live. A
  synthetic user that never gets a workspace assigned makes the DataHandler act
  in a workspace that does not exist.
- **DataHandler error paths render backend labels** through `$GLOBALS['LANG']`,
  which is therefore set defensively when nothing else provided it.

`EXT:academic_partners`' `Service\GeocodeWriteContext` is the worked example.
It is deliberately small and owned by the extension that needs it rather than
shared, because a shared one would have to be a dependency of everything.

**A refused DataHandler write has to fail its caller.** The DataHandler refuses
silently where an Extbase `persistAll()` threw: it collects messages in
`errorLog` and returns. A command whose queue selects on a status the write was
meant to change would otherwise retry the same record on every run — against an
external service, in the geocoding case.

## Testing it

The declaration itself needs a DataHandler test, or the TCA line can be removed
and nothing goes red: create a translation, assert it inherited the value, then
change the default record and assert the translation followed.

The wizard needs its own test with rows that are already in sync (it must not
touch them), rows that are not (it must), a translation the editor detached
(it must leave that alone) and deleted or workspace rows (likewise).

## See also

- [Database queries](database-queries.md) — the query builder rules.
- [Testing](../testing/Index.md) — the two PHP suites.
- [Fixture extensions](../testing/fixture-extensions.md) — stubbing an external
  service a command calls.
