## Context

See `proposal.md` for the motivation. State on `main` (3ac4f5f3a), re-checked
on 2026-09-27:

- `tx_academicpersons_domain_model_profile.hidden` is the `disabled` enable
  column since the initial commit, a `checkboxToggle` with `l10n_mode =>
  exclude`. Contracts, addresses, emails and phone numbers have `hidden` since
  the same commit, profile information rows since 2023-11-08.
- `Profile` has no `hidden` property. `Contract` and `ProfileInformation` have
  one, which the editor writes through `toggleDocumentVisibilityAction()` and
  `toggleContractContactVisibilityAction()` (ACE-524).
- The synchronisation switch is the special field `skipSync` of
  `academic_persons` `Settings.yaml`, rendered in `Partials/Profile/Header.html`
  and written by `updateSkipSyncAction()`. `ProfileFactory::mayApplyProperty()`
  refuses a property whose validation is `readOnly` or `disabled`, and the
  partial disables the checkbox for both.
- The editor reaches profiles only through
  `ProfileRepository::findByFrontendUser()` without `$showHidden`:
  `listAction()` and `ProfileUpdateRequestService::findEditableProfile()`.
  `LocalizedProfileUidResolver::resolve()` answers `null` for a hidden row. A
  hidden profile therefore gives its owner "Profile not editable".
- `academic_contacts4pages` drops a contact whose profile is hidden
  (`PageContactsProvider`), whatever "Show hidden records" says.
- ACE-50 names two uses: a public directory with only consenting profiles, and
  an internal directory after login that shows the others too.

## Goals / Non-Goals

**Goals:**

- One visibility fact per profile, owned by the existing `hidden` column.
- An owner who hides the own profile can always show it again.
- An installation decides whether owners hold that switch.

**Non-Goals:**

- A second column, a plugin option or a site setting.

## Decisions

### Decided: `hidden` is the consent, no new column

The switch writes `hidden`. Every public output already honours it, so the
change needs no query listener, no FlexForm option and no follow-up in
`academic_contacts4pages`. The column is already shared by all languages.

Rejected: a column `public_display` with a plugin option and a site setting,
as first proposed. It needed a listener on the profile and contract query
events, six FlexForm files, a letter availability check and a follow-up for
`academic_contacts4pages`, only to repeat what `hidden` does. It would also
leave two answers to "is this profile public" that disagree.

### Decided: the internal directory uses "Show hidden records"

An internal directory behind a login switches the existing plugin option "Show
hidden records" on. It then lists every hidden profile, including those an
editor hid for other reasons, and `academic_contacts4pages` still omits hidden
profiles. The `academic_persons` documentation states both limits.

Rejected: a separate consent flag to keep the two reasons apart. The analysed
project defaults its own option to "public only" in every plugin, and no
analysed project shows a directory that needs the distinction.

### The owner reaches the own hidden profile

`ProfileRepository` gains `findByFrontendUserIncludingHidden()`, which ignores
only the `disabled` enable field, like `findByUidIncludingHidden()`. Start and
end time and frontend user groups keep applying. `findByFrontendUser()` stays
as it is: its `$showHidden` belongs to the synchronisation and lifts all four
enable fields. The editor's list and the editable profile lookup use the new
method. The image upload maps its `Profile` argument through the persistence
session, which already holds the profile the ownership check loaded. The
public plugins do not change.

Extbase overlays the translation through `PageRepository`, which reads the
visibility aspect of the context rather than the query settings. The new
method therefore executes its query right away, with the aspect lifted to
hidden content and restored afterwards, and returns a list. TYPO3 v14.3.7
does the same inside Extbase, v13 and v14.3.6 do not.

Two reads of the profile row used the default restrictions and therefore
treated a hidden profile as missing: `ProfileImageRelationWriter` and
`ProfileImageMetadataService`. Both keep only the deleted restriction now, so
the image of a hidden profile is written and named like any other.

### The switch is a special field like `skipSync`

A special field `hidden` joins `skipSync` in `Settings.yaml` and in the header
partial, inverted in the display ("Show my profile publicly" is on while
`hidden` is 0). It is written by one JSON endpoint that accepts exactly one
boolean `hidden`, following `updateSkipSyncAction()`. The endpoint answers 403
when the special field is `readOnly`, `disabled` or removed with `~`, the check
the image endpoints use. It is available by default because ACE-50 asks for
owner control.

`ProfileFormData` gets no `hidden` property. The general `update` endpoint
therefore keeps refusing `hidden` as an unknown property, and the switch has
one write path only.

Rejected: a hard-coded switch without configuration, which lets an owner undo
an editor's hide on every installation.

### The switch writes through the DataHandler, on the default language

The endpoint submits `hidden` for the default-language uid of the profile as a
DataHandler datamap, run as the synthetic backend user of
`DataHandlerExecutionContext` and marked `ProfileWriteCorrelation::Internal`,
the way `ProfileImageRelationWriter` writes the image. Core's
`DataMapProcessor` then carries the `l10n_mode => exclude` column into every
translation in the same run. A frontend request in a workspace preview is
refused with 409, as for the image.

Rejected: the Extbase path through `ProfileFactory`. In a translated site
language the editor holds the translation overlay, and Extbase writes the
translation row, so the default language would stay public. The translations
would also depend on `SyncChangesToTranslations`, which only runs for the
languages in `profile/allowedLanguages`, so an installation with translations
outside that list would keep them public.

### The switch's flags stay out of the backend TCA

`Settings.yaml` validations of the profile update set are merged into the
profile TCA (`TcaValidationMerger`). For `hidden` that would make the backend
checkbox read-only exactly when an installation takes the switch away from
owners, which is when editors need it. The TCA file leaves out the validation
of the table's `disabled` enable column.

### Translations follow the default language

A translation keeps `hidden` of its default record, through the DataHandler
write above. A standalone translation, one without a default record as in a
site language with `fallbackType: free`, has no record to follow and is
written itself. The image uid resolution still refuses a translation that is
hidden while its default record is visible, a row the visitor may not see. It
accepts the rows of a profile whose default record is hidden, so the owner
edits the image of a hidden profile in every language.

The owner lookup lifts the visibility aspect while its query runs, so that
Extbase overlays a hidden translation at all. As a side effect a translation
hidden on its own while its default record is visible, a state only direct
database writes produce because `hidden` is `l10n_mode => exclude`, is now
shown and edited by the text endpoints, while the image endpoints keep
refusing it with 404. The inconsistency is accepted for that unreachable
state rather than handled.

## Risks / Trade-offs

- [An owner shows a profile an editor hid] → The installation takes the switch
  away through `Settings.yaml`. The changelog says so.
- [Owners now reach profiles an editor hid] → Intended, they can edit the
  texts but not show the profile while the switch is taken away. The
  changelog says so.
- [An internal directory with "Show hidden records" also lists profiles an
  editor hid] → Documented as a limit. A project that needs the distinction
  keeps a column of its own.
- [A project's own consent column] → A one-time migration sets `hidden` from
  it. The changelog shows an example.

## Migration Plan

Nothing to migrate upstream. An installation with its own consent column sets
`hidden = 1` for every profile without consent, default language and
translations alike, keeps profiles hidden that already were, and then drops
the column. An import that created profiles without consent sets `hidden = 1`
on new profiles instead. Rollback: remove the special field, the column is
unchanged.

## Open Questions

None.
