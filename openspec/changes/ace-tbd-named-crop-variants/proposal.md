## Why

The projects' templates request named crop variants (`portrait`, `square`,
`landscape`) for profile images and academic page media, but no academic
extension defines any crop variant. Six projects therefore add the same crop
variants to TCA themselves, and editors on a fresh installation only get the
free default crop.

## What Changes

- The profile image of academic_persons (`packages/fgtclb/academic-persons`)
  offers the crop variants `default` (free), `square` (1:1) and `portrait`
  (3:4).
- The page media of the program and project page types offers `default`
  (free), `landscape` (16:9) and `portrait` (3:4):
  - academic_programs (`packages/fgtclb/academic-programs`): program pages;
  - academic_projects (`packages/fgtclb/academic-projects`): project pages.
- The page media of partner pages (academic_partners,
  `packages/fgtclb/academic-partners`) keeps only the free `default`, because
  it is the partner logo.
- `default` stays the first variant with the free ratio, so stored crop data
  keeps working.
- The behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-persons/profile-image-crop-variants`: the crop variants an editor
  gets for the profile image.
- `academic-programs/page-media-crop-variants`: the crop variants for the
  media of program pages.
- `academic-projects/page-media-crop-variants`: the same for project pages.
- `academic-partners/page-media-crop-variants`: partner pages keep only the
  free default crop.

### Modified Capabilities

None.

## Impact

- The profile TCA of academic_persons and the page type TCA overrides of
  academic_programs and academic_projects; no TCA change in
  academic_partners.
- Projects that define the same crop variant names keep their own
  definitions; upstream variants they do not define appear next to theirs.
- No database, template or dependency change.

## Non-goals

- Using the named variants in upstream templates (candidates
  `cross-cutting-01` and `cross-cutting-02` render `default`).
- Crop variants for job images and for `tt_content` media.
- Named crop variants for partner page media; they can be added later without
  a breaking change.
- Frontend cropping in academic_persons_edit (ACE-263).
- Backporting to branch `2`.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`cross-cutting-03`). Five of the six analysed projects carry their own code for
this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-named-crop-variants` when the issue is filed after implementation.

Relates to ACE-572 (free-ratio crop for partner logos) and ACE-263.
