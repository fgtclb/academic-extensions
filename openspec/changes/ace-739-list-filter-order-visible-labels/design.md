## Context

See `proposal.md` for the motivation, and the design of the `main` change for
the decisions; this file names what differs on branch `2`. Verified at
`ae249b1c1`:

- `FilterTypeResolver` and `FilterTypes` do not exist here (ACE-736 is `main`
  only). `CategoryCollection`, `CategoryRepository` and the category type
  registry are identical, so the resolver rules hold unchanged.
- The three list controllers have no list events, no filter redirect and no
  pagination; they assign the repository's categories directly.
- The program list partial is the old loop over
  `categories.allCategoriesByType`; there is no program finder.
- None of the three extensions has a `settings` block, constants beyond the
  view paths, or a `settings.definitions.yaml`. `academic_jobs` and
  `academic_persons` declare theirs with the aggregate set.
- The testing helper has five traits and no POST helper; a filter submission
  reaches the list as a POST that renders it directly.
- TYPO3 v12 builds the `_LOCAL_LANG` path the way v13 does: with
  `extensionName: 'academic_<group>'` it reads `plugin.tx_academic_<group>`
  only. Measured on v12 with the label tests of this change.

## Decisions

### The same code as `main`, where the branch allows it

The partials, the changelog entries, the resolver's settings reader and the
tests are those of `main`. `FilterTypeResolver` and `FilterTypes` are `final
class` with `readonly` properties instead of `final readonly class`: this
branch supports PHP 8.1.

### Site settings on v13, constants on both

The settings are declared with each aggregate set, as on `main`. TYPO3 v12
has no site sets, so the constants are the only way there; the site set tests
carry the group `not-core-12`.

### Tests without the redirect

The active filter tests post the filter to the list page and read the page
the list renders, instead of following a redirect.

## Risks / Trade-offs

- [The program filter types arrive here without the element field] → Site-wide
  only, the key `settings.filter.categoryTypes` is the one `main` uses, so a
  later upgrade to 3.0 keeps the value.

## Migration Plan

None. Opt-in settings. Sites that set label overrides of the filters under
`plugin.tx_academic_<group>._LOCAL_LANG` copy them, see the Important entries.

## Open Questions

None.
