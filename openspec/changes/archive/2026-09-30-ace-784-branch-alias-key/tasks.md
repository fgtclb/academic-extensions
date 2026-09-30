## 1. Branch version name

- [x] 1.1 Add `Build/Scripts/composerBranchVersion.sh <branch>` as designed:
  `dev-<name>`, `N.x-dev`, `N.M.x-dev`, `N.M.P.x-dev`, exit 1 with a message on
  stderr for `vN`, `VN`, `N.M.P.Q`, `N.x.M` and an empty argument. Verify with
  a run for `main`, `2`, `2.x`, `2.2`, `v3`.
- [x] 1.2 Add `packages-dev/testing-helper/Tests/Unit/Build/ComposerBranchVersionTest.php`
  in the shape of `DdevWorktreeNamesTest::execute()`: a data provider for the
  mapping table and one for the refusals (status 1, message). Shown red by a
  script that prints `dev-<name>` for every branch, the behaviour of today.

## 2. The alias writer in `bin/set-version`

- [x] 2.1 Derive `BRANCH_ALIAS_KEY` from `--source-branch` through the helper
  right after the type validation, in every mode, and die on a refused name and
  on a numeric key whose prefix is not a prefix of `MAJOR.MINOR.x-dev`. Verify
  with `--dry-run` for `--source-branch=v3` and for `--source-branch=2` with
  `3.0.0`, both exit 1 before any tool line is printed.
- [x] 2.2 `apply_branch_alias()` writes the whole object through
  `composer config --json extra.branch-alias` and asserts it with `jq -e`.
  Header comment, help text of `--source-branch`, the derivation comment and
  the `info` line name the key instead of `dev-<source-branch>`.
- [x] 2.3 Verify on a `git archive` export of `HEAD` with the tools on `PATH`:
  `bin/set-version 3.0.0 post-release` produces the same diff as the unmodified
  script (no alias line), and `bin/set-version 3.0.0 dev --source-branch=3`
  changes exactly the thirteen alias lines to `3.x-dev` on top of that.

## 3. Data invariants

- [x] 3.1 `DocumentationGuidesTest::branch()` maps `dev-<name>` to `<name>` and
  `<N[.M[.P]]>.x-dev` to the bare version, and throws for anything else or for
  more than one key. Docblocks name both forms. Shown red by the old method
  against a root key `2.x-dev` with the twelve attributes set to `2` (throws
  1790668801), green with the new one. Both edits reverted afterwards.
- [x] 3.2 Add `packages-dev/monorepo-shared/Tests/Unit/BranchAliasKeyTest.php`:
  the root and every `packages/fgtclb/*/composer.json` carry the same alias
  object with exactly one entry, its key round-trips through
  `composerBranchVersion.sh` from the branch `DocumentationGuidesTest` derives,
  and a numeric key's target lies inside it. Shown red by a root key `dev-2`,
  by a thirteenth file with a second key, and by `"3.x-dev": "4.0.x-dev"`.

## 4. `bin/cut-branch`

- [x] 4.1 `bin/set-version` writes `edit-on-github-branch` of every
  `packages/fgtclb/*/Documentation/guides.xml` from `--source-branch`, in every
  mode, and asserts one attribute per file with that value. Verify that a real
  run on `main` changes no `guides.xml`, and that `--source-branch=3` changes
  exactly the twelve attributes.
- [x] 4.2 Add `bin/cut-branch <branch> <next-version>` with `--source-branch`
  (default `main`), `--dry-run`, `--execute` and `--help`, the guards, phase 1,
  phase 2 and the final checklist as designed. No version example in its help.
- [x] 4.3 Rehearse in a scratch clone below `.agent/tmp/` with a local bare
  repository as `origin`: `--dry-run` for `3 4.0.0` prints both phases and
  changes nothing, the default mode creates `cut-3` with the thirteen alias
  lines, the twelve edit branches and the two default lines of each of the
  three scripts, and
  `set-version-4.0.0` with the `4.0.0-dev` versions, `dev-main` to
  `4.0.x-dev` and the five example lines. The guards refuse `v3`, `3` with
  `3.1.0`, `4` from a `3.0.0-dev` source, an existing branch and a dirty tree.
  `--execute` is not run.

## 5. Documentation

- [x] 5.1 `docs/workflow/releasing.md`: the key sentence, the `--source-branch=2`
  advice for a `2` release from `main` (contradicts the page on `2`), the line
  references into `bin/set-version`, the edit branch now written by the
  script, and a section `bin/cut-branch` with the checklist.
- [x] 5.2 `docs/testing/unit-tests.md` (the links of the manuals, the new
  alias test), `docs/testing/testing-helper.md` (the new script test),
  `AGENTS.md` (six `monorepo-shared` tests, the script tests of
  `testing-helper`), and every page that states a measured test count.
- [x] 5.3 `README.md` "Releasing (maintainers)": the third script, in the
  summarize-and-link style the section has.
- [x] 5.4 The version tables of `README.md` and the twelve package READMEs:
  `main` as `^3, 3.0.x-dev (dev-main)`, `2` as `^2, 2.4.x-dev (2.x-dev)`, branch
  cell `2`. Under "Testing 3.x.x", the sentence that says `2.x`. Verify with
  `git grep -n '3\.x-dev'` returning nothing outside `openspec/changes/archive`.
- [x] 5.5 `Documentation/Changelog/2.4/Important-BranchAliasKey.rst` in all
  twelve packages from `Build/Documentation/Templates/Changelog-Important.rst`,
  each with its own label and `ext:<key>` index. It describes the `2.x`
  branch: `2.4.x-dev` installs, inline aliases can go. Check the over and
  underline lengths.

## 6. Definition of done

- [x] 6.1 `lintPhp` green.
- [x] 6.2 After `-t 13 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional -j auto` (SQLite) green with `-t 13`.
- [x] 6.3 The same with `-t 14` after its own `composerUpdate`.
- [x] 6.4 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 6.5 `README.md` and `CONTRIBUTING.md` still only summarize and link.
- [x] 6.6 Commits in TYPO3 Core format, subjects at most 52 characters, body
  wrapped at 72, no attribution of any tool:
  `[BUGFIX] ACE-784: Key branch alias to composer name` (groups 1 to 3 with
  their docs), `[TASK] ACE-784: Add a script to cut a version branch` (group 4
  with its docs), `[DOCS] ACE-784: Name existing dev versions in README`,
  `[DOCS] ACE-784: Add branch alias key changelog`. Each commit green
  on its own.
- [x] 6.7 Archive the change as the last commit of the pull request
  (`skip_specs`, no main spec changes).
- [ ] 6.8 Backport: a change of its own on branch `2` after the backport
  analysis (`docs/workflow/backporting.md`), with the thirteen `composer.json`
  keys as an extra commit after the test changes, and the Packagist check of
  the twelve packages after its merge.
