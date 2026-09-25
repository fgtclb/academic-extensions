## Context

Re-derived from the file-level diff against `main` after ACE-729:

- The four templates, the TypoScript constants of both extensions, the setup
  of `academic_bite_jobs` and the site settings definition of `academic_jobs`
  are identical to `main` before the change. The setup of `academic_jobs`
  differs only by the shared image partial path of `main`, `Job/New.html`
  only by its editor script include; neither touches the lines the change
  edits.
- The header partial of EXT:fluid_styled_content reads `data` on v12 and v13,
  so no controller assigns `record` here and none has to.
- The testing helper package has four traits here, not the ten of `main`.
- `academic_persons_edit` renders the header partial in its `ProfileEdit`
  layout, unconditionally; `main` has no such layout since ACE-262. Out of
  scope here, see the proposal.
- The job test classes of `academic_jobs` are deliberately narrower than on
  `main` and carry no header test; `academic_bite_jobs` has one that only
  looks for the text.

## Goals / Non-Goals

**Goals:** the behaviour of `main`, on TYPO3 v12 and v13.

**Non-Goals:** porting the wider test coverage of `main`.

## Decisions

### The same switch, name and defaults as on `main`

`settings.renderContentElementHeader` from the constant
`plugin.tx_academicjobs.renderContentElementHeader` /
`plugin.tx_academicbitejobs.renderContentElementHeader`, default `0`, and
`settings.defaultHeaderType = {$styles.content.defaultHeaderType}`. A site
that updates from 2.4 to 3.0 keeps its configuration. The site setting is
declared in the same file as on `main`; it takes effect on v13, where site
sets exist.

Rejected, as on `main`: removing the line without a switch, a switch shared
through `academic_base`, and a layout without a `Header` section for these
CTypes.

### An undefined `styles.content.defaultHeaderType`

Documented, not guarded, for the reason recorded on `main`: TypoScript has no
fallback for an undefined constant, and the elements of
EXT:fluid_styled_content miss the same constant in that case.

### Tests

The header tests of `main` are ported: both job test classes gain them, the
bite jobs test replaces its text assertion. They count headings and header
elements in the DOM through `ContentElementHeaderAssertionTrait`, added to
the testing helper here as well.

### Changelog placement

The `Important` entries go in `Documentation/Changelog/2.4/`, identical to
`main` apart from naming TYPO3 v13 for the site setting.

## Risks / Trade-offs

- [A site whose layout renders no header loses the job headers on update] ->
  The changelog entries lead with the switch.

## Open Questions

None.
