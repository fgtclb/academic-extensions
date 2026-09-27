## Why

`category_types` lets a later package change a category type that another
package ships: `useExisting: true` merges the given keys onto the loaded type,
and `remove: true` drops it. When this change was proposed, neither was
documented, and projects redeclared complete types to swap an icon. One
project redeclares twelve of them, and its labels broke when the upstream
label keys moved.

ACE-664 has since added the page `Documentation/Developers/CategoryTypes/` to
`category_types`, on `main` and on `2`. It lists the keys of a type and shows
a relabel through `useExisting` and a removal. What it still leaves out is the
case the projects need most and the edges they run into:

- no icon-only override, and nothing on how `inlineIcon` behaves in one;
- nothing on a redeclaration without `useExisting` resetting every key it
  leaves out to its default;
- the load order is stated for `composer.json` only, and the failure of an
  override is stated only as "no earlier extension declared" the type, without
  its message and without spelling out that a declaring extension loaded later
  or an earlier removal fail the same way;
- nothing on a removal that comes before the definition, or on removing a type
  of an extension that is not always installed;
- `getExtensionKey()` is described as the declaring extension, while an
  override hands it to the overriding one;
- nothing on the `groups:` section two academic extensions ship, or on the
  identifier being stored without its group.

## What Changes

- The existing page `Documentation/Developers/CategoryTypes/Index.rst` of
  `category_types` (`packages/fgtclb/typo3-category-types`) gains:
  - `useExisting` and `remove` in the list of keys, with their defaults;
  - an icon-only override example (`useExisting: true`, `icon`,
    `inlineIcon: false`), with the rule that an override which leaves out
    `inlineIcon` keeps the earlier value;
  - the rule that a redeclaration without `useExisting` replaces the type as
    a whole, keys left out included, and keeps its position;
  - the load order rule for `composer.json` and `ext_emconf.php`, and the
    exception message for an override whose type is not loaded yet;
  - the removal order rule, and `suggest` for removing a type of an extension
    that is not always installed;
  - the extension key after an override;
  - what the loader does not do today: the `groups:` section is not read, and
    the type column stores the identifier without its group, so an identifier
    has to be unique across groups.
- The icons page links the override section.
- Loader unit tests for the icon-only override, the redeclaration, the
  override that comes before its definition and the override of a removed
  type; a functional test class for the
  items, the type icons and the group headings of the `sys_category` type
  select.

Nothing changes at runtime, on TYPO3 v13 or v14.

## Capabilities

### New Capabilities

None. The change documents existing behaviour and sets `skip_specs: true`.

### Modified Capabilities

None.

## Impact

- One existing documentation page extended, one link added to the icons page.
- Four unit tests with three fixture packages, one functional test class.
- No code, configuration or dependency change.

## Non-goals

- A new page. The proposal asked for
  `Documentation/Developers/CategoryTypesYaml/`; ACE-664 created the page
  this belongs on in the meantime, and a second page would repeat it.
- A PSR-14 event to modify category types; the YAML keys already do it, and a
  second mechanism would compete with them.
- Changing the loader. The sharp edges it has are documented as they are, and
  fixed by `ace-tbd-category-type-identifier-collision`,
  `ace-tbd-category-type-group-labels` and
  `ace-tbd-category-type-priority-order`, each of which updates this page.
- A changelog entry: nothing an installation observes changes.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`cross-cutting-14`). All six analysed projects carry their own code for this
today. Filed after implementation as ACE-751, a subtask of the category types
epic ACE-42.

Relates to ACE-547.
Relates to ACE-575.
Relates to ACE-664.
