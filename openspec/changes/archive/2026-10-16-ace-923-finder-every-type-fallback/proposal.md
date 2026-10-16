## Why

Without filter types in its element and in the site setting, the program
finder offers the degree and the topic. A site that names its program types
differently, or removed the two shipped types, gets a finder without a single
select, and therefore without a form, until an editor fills in the field. The
program list offers every type with categories in the same situation.

## What Changes

- `academic_programs` (`packages/fgtclb/academic-programs/`): a finder without
  filter types in its element and in the site setting keeps offering the degree
  and the topic, or the one of the two that has categories. When neither has a
  category, because the types are removed, renamed or not used, it offers every
  category type with categories, in the type order of the group, as the program
  list does.
- Filter types the element or the site names are not replaced: a finder whose
  named types have no category still renders no form, like a list.
- The backend description of the field, the site setting, the constant, the
  manual and the unreleased 3.0 changelog entry of the finder say so.

Both supported core versions, TYPO3 v13 and v14, behave the same. The finder
exists on this branch only.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-programs/program-finder`: the default category types fall back to
  every type with categories when neither the degree nor the topic has one.

## Impact

- `ProgramController::finderAction()` of `academic_programs`. No TCA, FlexForm,
  template or public API change.
- The English and German backend description of the finder field, the
  description of the site setting and the label of the TypoScript constant.
- `Documentation/Integrator/ProgramFinder.rst`,
  `Documentation/Configuration/Index.rst`, the 3.0 entry
  `Feature-ProgramFinderContentElement.rst`, and
  `docs/architecture/list-filter-types.md`.
- The development seed relies on the degree and the topic and stays unchanged,
  with its manifests and screenshots.

## Non-goals

- Changing the default itself to every type with categories, which would turn
  a default finder into a dozen selects.
- A backend hint for a finder that offers no select.
- Replacing filter types an element or a site names when none of them has a
  category.
