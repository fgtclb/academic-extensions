## Context

See proposal.md for the motivation. On `main` before this change, icons live in
two registries that do not read each other: core's `IconRegistry`, fed by
`Configuration/Icons.php`, for the backend, and `FrontendIconRegistry` of
`academic_base`, fed by `Configuration/FrontendIcons.php` and the
`CollectFrontendIconsEvent`, for `<ab:icon>` (ACE-810). Five extensions moved
their frontend icons there (ACE-812 to ACE-814) under their old names, and
`FrontendTemplateIconTest` of `packages-dev/monorepo-shared` checks every
literal identifier a template passes to `<ab:icon>` (ACE-815).
`CurrentColorSvgIconProvider` inlines both markups on v13 and v14, with a v13
switch for the sanitiser. Both core versions merge `Icons.php` per package with
`array_merge()`, and the frontend registry copies that rule.

The change is one pull request of thirteen commits: the shared set and the
checks (ACE-584), one commit per extension (ACE-585 to ACE-593), the commit
that enforces the checks in every extension, the icon overview page of the
development seed (ACE-594) and the archive of this change.

## Goals / Non-Goals

**Goals:**

- One rule per question, each checked by a test: the name, the registry, the
  set, the file format, the provider, the licence notice.
- Every commit green on its own, so the stack can be merged commit by commit.

**Non-Goals:**

- A version split. Nothing here differs between v13 and v14 beyond what
  `CurrentColorSvgIconProvider` already switches.
- Rendering changes beyond the icon markup itself, such as the layout of the
  job property list.

## Decisions

**The group decides the registry.** `action`, `state` and `info` identifiers
go into `FrontendIcons.php` only, `record`, `plugin` and `doktype` into
`Icons.php` only. Rejected: registering every icon in both files. It doubles
what a site package has to replace and keeps the backend registry building
frontend icons it never shows. The group is already the answer to "who shows
this", so it is the rule, and the naming checks fail an identifier of one group
in the file of the other. Category type and group identifiers are derived by
`category_types` and contributed to both registries, they keep their names.

**`tx-<key without underscores>-<group>-<name>`.** The key keeps two packages
apart in a flat registry where a duplicate wins silently, without underscores
because `academic_persons_edit` would otherwise be ambiguous, `tx-` keeps out
of core's `actions-`/`apps-` categories, and `[a-z0-9-]` makes a usable CSS
class. Hard rename without `deprecated` entries: the frontend registry has no
such key, a template emits the new name either way, and 3.0 is unreleased.

**One shared set in `academic_base`, files may be shared.** Persons, persons
edit and study plan render `tx-academicbase-*` directly and drop their own
registrations. A backend icon may draw a file of the shared set
(`EXT:academic_base/...`, every extension requires `academic_base`), a file of
another extension may not. Jobs keeps one frontend identifier per job property,
`tx-academicjobs-info-<property in kebab case>`, drawing the shared files,
because ACE-813 promised that a site package replaces the icon of one property.
`default-not-found` keeps core's name, file and provider, and
`tx-academicprograms-info-credit-points` is already in the scheme.

**Font Awesome Free 7.3.1 solid, `svgs-full`, house format.** One set, one
style, one 640 unit grid, `width="1em" height="1em"`, `fill="currentColor"` on
the root, the attribution comment kept in the file, a
`LICENSE-font-awesome.txt` per extension that ships files, because each package
is split out and released alone. Rejected: keeping each extension's set, four
sets in mixed styles and grids, several drawn in fixed colours, and the Font
Awesome regular style, which Free ships for a small subset only.

**Every icon inline in `currentColor`.** `CurrentColorSvgIconProvider` for every
registration, brand marks included, so the record list, the page tree and every
frontend template follow the colour scheme or the theme. The job icons change
from a 16 px `<img>` to inline markup, named in the jobs Breaking entry.

**A list method on the frontend registry.**
`FrontendIconRegistry::getAllRegisteredIconIdentifiers()`, `@internal` like the
class, returning the identifiers in build order. Named after core's method for
the same question. The shared set test, the orphan check and the overview page
(ACE-594) read it, and the frontend icon API of ACE-595 is meant to. Rejected:
exposing the configurations wholesale, which would make the normalised cache
shape API.

**The checks, one trait per registry and one for the files.** The extension
wide checks follow the existing split of the testing helper:
`ColourSchemeAwareIconsTrait` (backend: naming of `Icons.php`, house format of
every backend icon drawn from the extension's files, type ownership),
`FrontendIconsAssertionTrait` (frontend: naming of `FrontendIcons.php`, house
format), and a new `IconFilesAssertionTrait` for the orphan and the notice
check, which ask both registries and belong to neither. Rejected: a registry
parameter on one trait, which would have to know both files and both group
sets. The type ownership check became a method of its own,
`assertEveryTypeOfTheExtensionNamesAnIconOfItsOwn()`, instead of new
parameters of `assertEveryRecordTypeIconIsColourSchemeAware()`: the old method
stays green on `main`'s identifiers, so the shared set commit does not break
the extensions it precedes, and the enforce commit adds the calls once every
extension is renamed. A fixture extension, `test_icon_rules`, holds every shape
the rules allow, so each check is shown to accept them before any extension
relies on it.

**Specs only where a requirement already speaks about icons.** A delta spec
modifies a requirement whose text names an identifier, a file, a provider or
markup this change alters, with the full requirement. The page type, content
element and record icons of the backend get no requirement of their own:
every extension follows the same rules, one identifier of its own per type,
the same one in the CType item, `typeicon_classes` and the wizard entry, drawn
in `currentColor`, and the icon tests of every extension check them, so a
requirement per extension would repeat one rule nine times. Rejected: an
`ADDED` requirement per extension, which would sit in capabilities about
frontend pages, and for which four extensions without an icon capability would
need new capabilities.

**One Breaking entry per extension.** Where ACE-812 to ACE-814 already wrote
one about the move to `FrontendIcons.php`, the rename is folded into it, with a
table of 2.x identifier, 3.0 identifier and registry. Otherwise a new entry.

## Risks / Trade-offs

- [A site package replaced a shared glyph and now changes it in every
  extension] → Intended. Each Breaking entry names the shared identifiers.
- [The backend registry no longer knows frontend icons a backend override
  template rendered] → Named in the Breaking entries, `<ab:icon>` is the
  migration.
- [A file of the shared set is drawn only by another extension's backend icon]
  → The orphan check asks both registries, the house format walk of
  `academic_base` covers icons other extensions draw from its files.
- [Docs carry per extension counts that change with each commit] → The
  extension commits leave `docs/` alone, the enforce commit updates the counts
  once every extension is renamed.

## Migration Plan

Per extension, the Breaking entry: rename identifiers in a site package's
`Configuration/FrontendIcons.php` and `Configuration/Icons.php` by the group
rule, rename `.icon-<identifier>` selectors and template overrides, flush the
system caches. No database migration, no upgrade wizard. Rollback is the
revert of the pull request, there is no persisted state.
