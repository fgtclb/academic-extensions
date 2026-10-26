## Context

See `proposal.md`. The backport analysis compared every file the change of
`main` touched:

- The three `Changelog/2.4/Important-PageTemplateNameIsSetExplicitly.rst`
  were byte-identical before the change and are copied from `main`.
- `academic-base/Documentation/Upgrade/Index.rst` differs only by the rows and
  toctree entries of the 3.0 pages, which this branch does not have.
- The page TypoScript of programs, partners and projects differs between the
  branches, but sets the template name with the same `templateName >` block.
  The TYPO3 v12 part of the include order was proven on this branch, in the
  `core-12` instance with Bootstrap Package 15.0.4: the static template of the
  extension after the one of the Bootstrap Package renders the page, the
  reverse order ends in `Default/Academicprogram.html` not found.

## Decisions

The section keeps the label and the wording of `main` and drops what concerns
3.0: the TYPO3 v12 item names the path from 2.3 to 2.4 only, and a
development constraint is replaced with `^2.4`.

## Risks / Trade-offs

- [`Changelog/2.4/` drifts between the branches] → copied with `git show
  origin/main:<file>` and compared with `cmp`.
