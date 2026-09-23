## Context

The backport analysis, per `docs/workflow/backporting.md`, against `main` at
`d32fbd530` (ACE-721 merged) and this branch at `c444f7ac0`.

| File                                                                       | Differs between the branches                                                    | Does the difference touch this change?                                 |
|----------------------------------------------------------------------------|---------------------------------------------------------------------------------|------------------------------------------------------------------------|
| `Configuration/TypoScript/Page/AcademicPrograms.typoscript`                | yes: `main` adds the `academic_base` partial and template root paths of ACE-710 | no - the variable goes in next to `dataProcessing`, which is identical |
| `Resources/Private/Pages/AcademicProgram.html`                             | yes: `main` renders the image through the `Academic/Image` partial of ACE-710   | no - the `f:cObject` line is identical                                 |
| `Tests/Functional/Pages/AcademicProgramPageTemplateTest.php`               | yes: `main` has the image and partial root path tests                           | the content tests are ported, the ACE-710 tests stay on `main`         |
| `Configuration/Sets/ContentLoad/`, `Configuration/TypoScript/ContentLoad/` | identical                                                                       | removed on `main`, kept here with a corrected comment                  |
| `docs/architecture/database-queries.md`                                    | yes: `main` has the tie paragraph ACE-721 added                                 | ported, with this branch's core versions                               |

Checked against TYPO3 v12.4.45 and v13.4.35:

- `styles.content.get` is defined by `cms-frontend` itself
  (`ext_localconf.php`, `addTypoScriptSetup()`): a `CONTENT` of colPos 0
  ordered by `sorting`, on both versions. The removed global override copied
  it; the new variable is the same object plus a `uid` tiebreaker.
- `FluidTemplateContentObject` evaluates `variables.` on v12 and v13.
  `PageViewContentObject` and site sets do not exist on v12, so the tests of
  those two integrations carry the group `not-core-12` (on this branch that
  means v13 only).

## Decisions

The same variable as on `main`, inside the doktype 20 condition of
`AcademicPrograms.typoscript`, rendered as `{programContent -> f:format.raw()}`.
Rejected alternatives and the reasons are those of the `main` change.

Kept, unlike `main`: the content-load set, its static template, the
aggregate's dependency and the `Full` static template entry. Removing them is
breaking and is 3.0's. Their comments and the documentation no longer say the
page type throws without them. The unreleased 2.4 Breaking entry of the
configuration split carried that warning; it now points to the new
`Important` entry instead of a trap that no longer exists.

Not ported: the `academic:upgrade:check` finding `unavailable-set`. It exists on
`main` because 3.0 removes a set a site configuration may still name; this
branch removes none, and the upgrade check command itself is 3.0 only.

## Risks / Trade-offs

- [A site customised `styles.content.getContent` for program pages] → The
  customisation no longer reaches program pages; the `Important` entry names
  the variable to move it to.
- [A site template override of `AcademicProgram.html` renders
  `styles.content.getContent`] → Keeps working as long as the site includes
  the content-load component, which the aggregate still does.

## Migration Plan

None required. Rollback is the code only.

## Open Questions

None.
