## Context

See `proposal.md` for the motivation. State on `main`:

- No consent column, property or option exists (`grep -ri
  'public_display\|consent'` over `Classes/` and `Configuration/` finds
  nothing).
- `showHiddenRecords` is read in `ProfileController` for the card, detail,
  selected profiles, selected contracts and list actions. It is passed to
  `ProfileRepository::findByUids()` and `ContractRepository::findByUids()` as
  a `bool $showHidden` parameter, and to `findByDemand()` through
  `ProfileDemand::setShowHiddenRecords()`.
- `detailAction()` answers a missing profile through `ErrorController::
  pageNotFoundAction()`.
- The plugins use six FlexForm files: `Core13/` and `Core14/` copies of
  `List.xml` and `Detail.xml`, plus `SelectedProfiles.xml` and
  `SelectedContracts.xml`. `List.xml` serves list, listanddetail and card.

## Goals / Non-Goals

**Goals:**

- A query-level constraint, so that counts, pagination and letter
  availability agree with what is shown.
- An opt-in per plugin or per site, so existing sites see no change.

**Non-Goals:**

- A global restriction for every profile query.

## Decisions

### Column and property

Add `public_display` to the profile TCA as `type => check` with a toggle,
`default => 0` and `l10n_mode => exclude`, so that consent belongs to the
person and not to a language. `Profile::isPublicDisplay()` exposes it. The
column is derived from TCA by core on v13 and v14; the implementation checks
with `DefaultTcaSchema` on both versions which definition core derives
before deciding whether `ext_tables.sql` needs an entry.

### Decided: the constraint goes through the query events

An event listener of `academic_persons`, registered with TYPO3's
`#[AsEventListener]`, listens to `ModifyProfileQueryEvent` and
`ModifyContractQueryEvent` of `ace-tbd-profile-query-constraint-event`. When
`settings.publicDisplayOnly` of the event context is set, it adds
`equals('publicDisplay', true)` to the profile query and
`equals('profile.publicDisplay', true)` to the contract query. That covers
`findByDemand()` and both uid lookups, which receive the plugin context
through the context-aware finders of that change. Neither `findByUids()`
gains a parameter, and `ProfileDemand` gains no property.

The first draft added `bool $publicDisplayOnly` as the third parameter of both
`findByUids()` methods, the slot the query event change also claimed for its
context. Any change of that signature fatally breaks the repository XCLASSes
that projects carry over from 2.x, and the event already carries exactly this
kind of condition.

The letter availability of `ace-tbd-letter-navigation-availability` builds its
own query from the list demand. It has to apply the constraints of the profile
query event too, or a letter can link to an empty page; a task below verifies
that.

Rejected: a template condition, which is what one project does today. It
leaks the profile through lists, counts and pagination. Also rejected:
storage folders per visibility (an ACE-20 option), which breaks the
one-profile-per-person model. Also rejected: a custom query restriction that
applies to every query, which would hide profiles from the frontend editor,
from imports and from `academic_contacts4pages` without an opt-in.

### Detail action

`detailAction()` answers through the same `pageNotFoundAction()` path when
consent is required and the profile does not consent. Both the detail and the
listanddetail plugin read the option from their own FlexForm, combined with
the site setting below.

### Decided: profile-level consent now, field-level consent later

Consent is one flag per profile (ACE-50). Consent per field (ACE-474) is a
change of its own, later.

The only analysed production consent is profile-level. Field-level consent
touches every public element of the persons `Settings.yaml` profile and
needs a design of its own.

### Decided: a site setting or the plugin option requires consent

A site setting `plugin.tx_academicpersons.publicDisplay.required` (bool,
default false) is declared in `Configuration/Sets/Full/settings.definitions.yaml`
and repeated with the same default in
`Configuration/TypoScript/Default/constants.typoscript`, and mapped to
`settings.publicDisplay.required`. The listener and the detail action
require consent when either that setting or the plugin's
`settings.publicDisplayOnly` is on.

An institution where consent is always mandatory switches it on once for
the site; the analysed project that requires consent defaults the option to
on in four plugin FlexForms to get the same. The OR keeps the plugin option
meaningful on sites without the setting, because a check field cannot
express "use the site default". The two values live under different keys on
purpose: a FlexForm value overrides the TypoScript setting of the same path,
so one key could not be ORed.

Rejected: the plugin option alone, which leaves mandatory consent to every
editor. Rejected: the site setting alone, which removes the per-element
opt-in for sites that need consent only in some places.

### Decided: contacts for pages and the frontend editor follow in 3.0

`academic_contacts4pages` and the frontend editor of `academic_persons_edit`
get consent support in two follow-up changes, released together with this
one in 3.0. They are not part of this change.

`academic_contacts4pages` loads its contacts through its own
`ContactRepository::findByPid()` (`ContactsController.php:28`,
`ContactsProcessor.php:33`), so it never sees the profile query event and
would show non-consenting profiles. Consent is given by the owner of a
profile, who edits it in the frontend editor, and the analysed project that
requires consent adds exactly that field to the editor's profile form.

## Risks / Trade-offs

- [`academic_contacts4pages` still shows non-consenting profiles] → Closed by
  its follow-up change in the same release; until that lands, the changelog
  of this change names the gap.
- [Imports create profiles without consent] → Irrelevant while the option is
  off; mapping consent from a source is a `persons-data` follow-up.
- [Six FlexForm files to keep in sync] → A functional test asserts the field
  in every data structure on both core versions.

## Migration Plan

Nothing to migrate upstream. An installation with its own consent column
copies it once, for example with
`UPDATE tx_academicpersons_domain_model_profile SET public_display = <own_column>`,
and then enables the plugin option or the site setting. Rollback: switch
both off; the column is ignored by the previous version.

## Open Questions

None.

Guessed layout — a sketch, not a design:

```text
Plugin > General (GUESSED)
[x] Only show profiles whose owner agreed to public display
Profile record > Visibility (GUESSED)
Public display  [ on ]   "Shown on public directories"
```
