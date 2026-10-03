## Why

`category_types` (`packages/fgtclb/typo3-category-types`) registers the icon
of every declared category type and group in the core icon registry only. The
frontend reaches them through that backend registry, and a project cannot
replace one for the frontend, because the registration overwrites any
`Icons.php` entry. With the frontend icon registry of `academic_base`
(`ace-810-frontend-icon-registry`, ACE-810) the category type icons need a second home,
and an extension author needs a way to give the frontend another drawing than
the backend. The unreleased group icon identifier
`category_types.group.<group>` collides with the type icons of a group named
`group`.

## What Changes

- Every type and group icon is registered in the frontend icon registry as
  well, under the identifier it has in the backend. The backend registration
  stays as it is.
- `CategoryTypes.yaml` gains `frontendIcon` and `frontendInlineIcon` for
  types and groups, both optional. The frontend shows `frontendIcon`, or
  `icon` when none is declared. The inline flag belongs to the file next to
  it: an undeclared `frontendInlineIcon` takes `inlineIcon` only while the
  frontend shows `icon`.
- An override (`useExisting` for a type, a later declaration for a group)
  that names a new `frontendIcon` without `frontendInlineIcon` does not
  inherit the flag the earlier declaration set for another frontend file. The
  unreleased 3.0 rule for `icon` and `inlineIcon` (an override of `icon`
  keeps the earlier `inlineIcon`) stays as it is.
- A site package replaces a category type icon in the frontend only through
  its `Configuration/FrontendIcons.php`, which wins over the declaration.
- The group icon identifier becomes `category_types_group.<group>` in both
  registries. No Breaking entry, it was never released.
- A type without an icon file gets no frontend entry.
- TYPO3 v13 and v14 behave the same.

## Non-goals

- Switching the templates of `academic_partners`, `academic_programs` and
  `academic_projects` to the frontend ViewHelper: that is
  `ace-tbd-programs-partners-projects-frontend-icons`.
- Moving the backend registration off `BootCompletedEvent`, and fixing the
  backend registration of a type without an icon file.
- Renaming the type icon identifiers `category_types.<group>.<type>`, or
  adopting the scheme of the icon consolidation pull request.
- Changing the shipped `CategoryTypes.yaml` files, which use one file per
  type for both places.

## Capabilities

### New Capabilities

- `typo3-category-types/category-type-icons`: which file and which rendering
  a category type or group icon gets in the backend and in the frontend, how
  overrides and site packages change it.

### Modified Capabilities

- `typo3-category-types/category-type-groups`: the group icon identifier, its
  availability in the frontend, and a later declaration that names a new icon
  file without its inline flag.

## Impact

- `category_types`: models, loader merge, a listener on the icon collection
  event of `academic_base`, the group identifier, two developer manual
  chapters, a `Feature` entry, amended unreleased entries on inlined and
  group icons.
- `academic_partners`, `academic_programs`, `academic_projects`
  (`packages/fgtclb/academic-partners`, `-programs`, `-projects`): their
  unreleased group icon changelog entries and group icon tests name the new
  identifier.
- `docs/architecture/icons.md` (category type section).
- Integrators: a template or `Icons.php` that addresses
  `category_types.group.<group>` from a 3.0 development state uses
  `category_types_group.<group>`. An override that replaces `frontendIcon` and
  wants it inlined says `frontendInlineIcon: true` again. A frontend only replacement goes
  into `Configuration/FrontendIcons.php`.
- Depends on `ace-810-frontend-icon-registry`.
