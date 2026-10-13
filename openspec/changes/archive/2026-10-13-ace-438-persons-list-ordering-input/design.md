## Context

Measured on `main` `e75f86b95` in `packages/fgtclb/academic-persons`, against
TYPO3 13.4.35 (`core-13/vendor`) and 14.3.7 (`.Build/vendor`). The files named
here are identical on branch `2` `7ebded7b8` unless stated otherwise.

**The value list parser exists twice.**
`Classes/DemandValues/AbstractDemandValues.php:38-54` and
`Classes/Types/AbstractTypes.php:40-64` split one extension configuration
string with `GeneralUtility::trimExplode(',', $string)` without
`removeEmptyValues`, so `''`, `a,` and `a,,b` each yield the item `'' => ''`.
`SortByValues` and `GroupByValues` read `demand/allowedSortByValues` and
`demand/allowedGroupByValues`. The three type classes feed the type selects of
email, phone number and address records, whose TCA already ships an
"undefined" item with the value `''`.

**An entry with an empty side raises a warning today.** Each entry that holds
`=` is split with `trimExplode('=', $entry, true, 2)` and destructured into two
variables. `trimExplode()` (`GeneralUtility.php`, v13.4.35 `:822`, v14.3.7
`:825`) removes the empty side before the limit applies, so `firstName=` and
`=First` both return one element and the destructuring raises "Undefined array
key 1". `firstName=` ends up labelled `null`, `=First` ends up as the value
`First`, and `=` raises two warnings and ends up as the key `''` labelled
`null`. The triage report assumed `=First` yields an empty value. It does not,
it yields a PHP warning. On PHP 8.5, `=` also raises the deprecation "Using
null as an array offset", which a unit test under `failOnDeprecation` reports.
Re-measured on 2026-10-13 against the real classes on both cores: passing
`removeEmptyValues` to the split by `,` alone removes the empty item, but
leaves every warning of an entry with an empty side, because the split by `=`
already passes it and that is what drops the side.

**The allow list accepts the empty value.**
`ProfileRepository::getOrderingsFromDemand()` (`:668-683`, `2` `:346-361`)
checks `groupBy` and `sortBy` with a strict `in_array()` against the keys of
those lists. The "none" item of both FlexForm selects is the value `''`
(`Configuration/FlexForms/Core13|Core14/List.xml:73,87`), neither key is in
`ignoreFlexFormSettingsIfEmpty`, so an element on "none" demands `''`. With an
empty item in the list, `$orderings['']` is set. The ACE-482 fallback is
appended with `+` (`:461`), it does not replace it. Extbase turns the property
`''` into the column `''` and calls
`addOrderBy('tx_academicpersons_domain_model_profile.', ...)`
(`Typo3DbQueryParser.php:242-244`, same legacy path on v13). What each DBMS
does with that identifier is not measured, task 2.5 measures it.

**The configuration read of the demand values is unguarded.**
`AbstractDemandValues.php:44` calls `ExtensionConfiguration::get()` without a
`try`. `AbstractTypes` has caught `ExtensionConfigurationExtensionNotConfiguredException`
and `ExtensionConfigurationPathDoesNotExistException` since ACE-365 and checks
`is_string()`, because `get()` returns an array for a path that is a prefix of
deeper options. On v13 and v14 `get()` restores a missing path from
`ext_conf_template.txt` before it throws, so it throws one of the two only for
an extension or a path the template does not declare. When the settings file
cannot be written, the restore throws `SettingsWriteException` from
`ConfigurationManager::writeLocalConfiguration()` instead, which neither class
catches (found in the review, 2026-10-13).

**The direction is validated only in the repository.** `getOrderingsFromDemand()`
accepts exactly `asc` and `desc` (`:673`) and skips the sort ordering
otherwise, leaving the uid fallback. `ProfileDemand::setSortByDirection()`
(`Classes/Domain/Model/Dto/ProfileDemand.php:89-93`, `2` `:81-85`) stores any
string, and the class is `@api` on `main` (not on `2`). The FlexForm offers
`asc` and `desc` only, so the sources of another value are the free text site
setting `plugin.tx_academicpersons.demand.sortByDirection`
(`Configuration/Sets/Full/settings.definitions.yaml:50-54`), the TypoScript
constant, an empty FlexForm value, and a `ModifyProfileDemandEvent` listener.
The list action receives the demand through Extbase property mapping, which
writes through the setter: `ObjectConverter` calls `ObjectAccess::setProperty()`,
which uses the Symfony property accessor on v13.4.35 and v14.3.7 alike.

**The precedent.** `ProgramDemand::setSorting()` of `academic_programs` keeps
its previous value for an unknown input. ACE-625 kept that and rejected
validating in the repository because "it drops the ordering on bad input
instead of making the offered input valid"
(`openspec/changes/archive/2026-09-16-ace-625-program-sorting-direction-discarded/design.md`).

**Tests that pin today's behaviour.** `Tests/Unit/DemandValues/AbstractDemandValuesTest.php:76-86`
and `Tests/Unit/Types/AbstractTypesTest.php:73-84` assert the `'' => ''` item
for the three inputs. `Tests/Unit/Domain/Model/Dto/ProfileDemandTest.php` pins
only the defaults and a valid round trip. No functional test orders a list by
a demanded field, and none uses an invalid direction.

**Documentation.** The manual `Documentation/Configuration/General/Index.rst:63-68`
gives the group values as the default of `demand.allowedSortByValues`, the
shipped default is `firstName=...,lastName=...` (`ext_conf_template.txt:26`).
`docs/architecture/class-design.md:156-157` names both abstract classes with
the line `:17`, `AbstractTypes` is already at `:19`.

## Goals / Non-Goals

**Goals:**

- No configured value list carries an item with an empty value, and no entry
  of it raises a PHP warning.
- The demand values read the extension configuration the way the types do.
- A sort direction that differs from `asc` or `desc` only in letter case or
  surrounding whitespace is applied, and an unknown one keeps the ordering.

**Non-Goals:**

- A shared parser class. The two parsers get the same fix in place.
- Validating `sortBy` or `groupBy` in the demand. The allow list is extension
  configuration, which the data object cannot read without a service.
- Making the program list accept other letter cases.

## Decisions

### 1. Two commits, one per issue, in one pull request

ACE-438 (the value lists and the configuration read) and ACE-439 (the sort
direction) touch disjoint files: the two parsers with their unit tests and the
manual, against the demand data object with its unit test. Each one is a
complete fix that is green on its own, carries its own test and its own
`Important` changelog entry, and resolves its own key, which keeps the history
and the later backport per issue. Order: ACE-438 first, then ACE-439, then the
archive commit of the change. The repository merges by rebase, so all three
land as they are.

Rejected: one commit resolving both keys. It is smaller to review once, but it
mixes a parser fix with an `@api` behaviour change, and one issue could not be
reverted or backported without the other.

Rejected: two pull requests as a stack. Possible, since each commit is green
alone, but the archive commit then belongs to the top one and the gain over
one pull request with three commits is small.

Confirmed by Stefan on 2026-10-13: one pull request with three commits.

### 2. Empty values are dropped in the parser

Both parsers split with `removeEmptyValues`, then split each entry into value
and label without dropping an empty side (`trimExplode('=', $entry, false, 2)`
padded to two elements). An entry whose value is empty is skipped, an entry
whose label is empty is labelled with its value. Applied identically to
`AbstractDemandValues` and `AbstractTypes`, because the issue names both and
the type selects otherwise offer a second unlabelled empty item next to
"undefined".

Rejected: a `!== ''` check in `getOrderingsFromDemand()` only. It fixes the
query but leaves the empty item in both backend selects and the warning in the
parser. With the parser fixed, such a check is unreachable, so it is not added
either.

Rejected: keeping today's result for `=First` (the value `First`). It came
with a PHP warning on every read and was never a documented form.

Keeping the empty side also keeps an equals sign at either end of a label:
`firstName==First` is labelled `=First` and `a=b=` is labelled `b=`, where the
old split dropped them. The unit tests pin the first.

The empty side of an entry is also item 3 of ACE-434. The change
`ace-434-coverage-round-leftovers` (pipeline #130, after this one) leaves that
item to this change and does not plan it again, so the ACE-438 commit names
ACE-434 as related, and the backport to `2` covers it there as well.

### 3. The demand values read the configuration like the types

`AbstractDemandValues` catches the same two exceptions as `AbstractTypes` and
treats a non-string as an empty list. The select then offers only "none" and
every list falls back to the uid order instead of failing the frontend
rendering. The comment of `AbstractTypes` is reused in substance.

Rejected: letting the exception through so that a broken installation is
noticed. The two exceptions mean a path the template does not declare, which a
list should survive like the type selects do.

Not caught: `SettingsWriteException`. An unwritable settings file fails every
caller of `get()` that needs a restore, in the core as well, and the guard stays
the one `AbstractTypes` carries.

### 4. The setter normalises the direction and keeps the previous value

`ProfileDemand::setSortByDirection()` lowercases and trims its input. A result
of `asc` or `desc` is stored, anything else leaves the property unchanged, so
a fresh demand stays at its default `asc` and a listener that writes an
unknown value after `desc` keeps `desc`. This follows `ProgramDemand::setSorting()`.
The repository keeps its strict check as a second guard for another
`DemandInterface` implementation and stays unchanged.

Rejected: normalising in the repository. It is what ACE-625 rejected, and
`getSortByDirection()` would keep returning a value the list does not apply,
which is what a listener reads.

Rejected: resetting an unknown value to `asc` instead of keeping the previous
one. For property mapping both give `asc`, for a listener the precedent keeps
the earlier valid choice, and the two extensions then agree.

The setter rewrites input of an `@api` class. Valid input behaves as before,
so this is an `Important` entry, not a `Breaking` one.

### 5. An unknown sort field keeps the uid order (Decision for the maintainer)

ACE-439 also asks what an unknown `sortBy`, such as a renamed allow list
entry, should do. Options:

- (a) Keep today's behaviour: no sort ordering, uid order. Recommended. The
  allow list lives in the extension configuration, the demand cannot check it,
  and any fallback field is a guess about the site.
- (b) Fall back to the first allowed value in the repository.
- (c) Fall back to the shipped default `lastName` when it is allowed.

The recommendation keeps this change to the direction. Stefan chose (a) on
2026-10-13, so the spec gains no requirement and task 6.2 does not apply.

## Risks / Trade-offs

- [A record was stored with a type that came from `=Label`] → It loses its
  item and the backend shows it as an invalid value. Accepted: that
  configuration raised a PHP warning on every form render.
- [A listener relies on reading back an uppercase direction it wrote] → It now
  reads the lowercase form. Named in the `Important` entry.
- [The DBMS reaction to the empty identifier is unknown] → Task 2.5 measures
  it on SQLite, PostgreSQL and MariaDB. If SQLite accepts it, the functional
  reproduction is red on the other two only, and the unit tests stay the red
  proof on SQLite.
- [The doc line references in `class-design.md` drift again] → Corrected in
  the same commit that moves them.

## Migration Plan

None. Only configurations that are broken today render differently. No data is
written, no upgrade wizard.

Backport to `2`: a change of its own after a backport analysis. The parsers,
the unit tests and the setter are byte-identical there, the repository method
is at `:346-361`, `ProfileDemand` is not `@api`, the FlexForms are
`Core12|Core13`, the changelog folder is `Changelog/2.4/`, and the v12
behaviour of `ExtensionConfiguration::get()` has to be read in the v12 vendor
tree before the guard comment is copied.
