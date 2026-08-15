# Commit messages

This repository follows the TYPO3 Core commit message rules, with the two
adjustments an extension mono repository needs: there is no forge issue, and
there is no Gerrit. Everything below is derived from the actual history of
**this branch**, not from a generic template — the counts quoted were taken from
`git log … origin/2` at the time of writing and can be reproduced.

The reason the rules are enforced at all is that the history is read far more
often than it is written. `git log --oneline` is the index of this repository:
it is how a defect is traced back to the change that introduced it, how a
backport candidate is found, and how a release changelog is assembled. A subject
that does not say what changed, or that is cut off by the terminal, costs that
every time.

## Format

```
[TAG] ACE-NNN: Imperative subject line

Body: what changed and why, wrapped at 72 characters. Separate
paragraphs with a blank line.
```

The issue reference sits **inside the subject**, directly after the tag and
before the description, separated by a colon. It is not a footer here. 84 of the
last 100 commits on `2` carry an `ACE-NNN` key in that position. The 16 that do
not are documentation fixes, support-matrix and branch-model work, the release
automation scripts and a handful of housekeeping commits. Release commits carry
no reference either.

## Subject line

| Rule            | Value                                           |
|-----------------|-------------------------------------------------|
| Target length   | 52 characters, tag and issue reference included |
| Hard cap        | 72 characters                                   |
| Mood            | Imperative — "Add", "Fix", "Drop", "Cover"      |
| First word      | Capitalized                                     |
| Trailing period | None                                            |

What the branch actually does: 46 of the last 50 subjects are within 52
characters, and the four that are not run to 55–63. Widen the window and the
picture changes: only 62 of the last 100 stay within 52, because a single
documentation series (`ACE-253`, 25 commits, all of them over) names an
extension key in every subject. Across the last 600 commits only 320 stay within
52, 34 exceed even the 72 character cap, and the longest subject on record is 93
characters, from July 2025. The 52 character target is the current standard; the
drift is history, not licence.

The budget is tighter than it looks. `[BUGFIX] ACE-358: ` alone is 18
characters, so the description itself has about 34 left. A subject that does not
fit is usually a commit that does two things.

## Body

The body is wrapped at **72 characters** and separated from the subject by a
blank line. It is required for anything that is not trivially self-explaining,
and in practice that is nearly everything: the recent history has substantial
bodies on almost every commit.

Verified over the last 60 commits: most bodies stay at or below 72 characters,
with about 25 prose lines running over, by one to nine characters. The clear
exceptions are literal shell commands and URLs, which are left unwrapped on
purpose — a wrapped command cannot be copied and pasted, and that is the entire
point of putting it in the message.

Write what changed and **why**, not how. The bodies that have proved useful in
this repository consistently contain four things:

- the behaviour before the change, concretely enough to recognize it,
- the cause, named at the level of the class or the API involved,
- the alternative that was rejected and the reason it was rejected,
- how the change was verified, including which core version it was measured on.

## Tags

Taken from the last 600 commits on `2` rather than from a generic list. The
counts are what is in use; a tag that does not appear here is not established
practice on this branch.

| Tag              | Count | Use                                             | Example commit                                                        |
|------------------|-------|-------------------------------------------------|-----------------------------------------------------------------------|
| `[TASK]`         | 339   | Refactoring, tests, CI, dependencies, tooling   | `6f88b64db` `[TASK] ACE-382: Cache only composer's dist archives`     |
| `[BUGFIX]`       | 113   | Fixes incorrect behaviour                       | `1dd253043` `[BUGFIX] ACE-358: Drop the defaults on TEXT columns`     |
| `[DOCS]`         | 75    | Documentation only, shipped or developer facing | `b6a4c9f2b` `[DOCS] ACE-303: Document the WebP requirement`           |
| `[FEATURE]`      | 25    | New, user-facing functionality                  | `ae4d6eb55` `[FEATURE] ACE-236: Select the displayed address records` |
| `[!!!][TASK]`    | 19    | Breaking change, marker first                   | `b768ae21f` `[!!!][TASK] ACE-306: Remove the profile switcher`        |
| `[RELEASE]`      | 17    | Release commits, maintainers only               | `1f5334422` `[RELEASE] 2.3.4`                                         |
| `[!!!][FEATURE]` | 5     | Breaking new functionality                      | `494a94d7a` `[!!!][FEATURE] ACE-242: Synchronize hidden profiles`     |

Four commits in that range carry something else — two `[REMOVE]`, one `[DOC]`
and one `[BUGIX]`. The last two are typos for `[DOCS]` and `[BUGFIX]`; none of
the four is a tag to copy.

The `[!!!]` marker always comes first and is combined with the tag that
describes the nature of the change, never used alone.

`[RELEASE]` commits are the one deliberate exception to the rules above: they
carry no issue reference, and their body is the literal, unwrapped command
sequence the release was produced with, so a release can be reproduced or
audited later.

## Complete examples

Two messages from this branch, verbatim and complete. Both are ordinary commits,
not showcases — this is the expected level of detail.

`1dd253043`, a `[BUGFIX]` that names the exact failure, the core versions and
database systems that see it, the alternative that was rejected and what the
added tests prove. It is also the clearest illustration of a body carrying facts
that are true **on this branch** and nowhere else:

```
[BUGFIX] ACE-358: Drop the defaults on TEXT columns

Five columns were declared as `TEXT` carrying a default value:

    link text NOT NULL DEFAULT '',                  (pages)
    company_name text NOT NULL DEFAULT '',          (job)
    sector text NOT NULL DEFAULT '',                (job)
    required_degree text NOT NULL DEFAULT '',       (job)
    contractual_relationship text NOT NULL DEFAULT '', (job)

MySQL cannot store a default on a `TEXT` column. TYPO3 v13 and above
express it in the `DEFAULT ('')` syntax MySQL 8.0.13 introduced, in
`MySQLDefaultValueDeclarationSQLOverrideTrait`; TYPO3 v12 uses the
Doctrine platform unmodified, which drops the default. The columns
were created `NOT NULL` with no default at all, so every statement
that did not name them was rejected. Creating a page in the backend
was one of them:

    SQL error: "Field 'link' doesn't have a default value" (pages:NEW1)

No page was created. MariaDB, PostgreSQL and SQLite were never
affected, which is why only the two `functional mysql 8.0 (v12, ...)`
jobs could see it.

The columns keep their type and lose the default instead, which makes
them nullable and therefore optional in an insert. `varchar(255)` was
the alternative - it is what core derives from the TCA of the four
`input` fields since v13 - but shortening a column is the one
migration that can abort or lose data, and on `pages` it would have
counted about 1020 bytes against the MySQL row size limit instead of
12.

Two things measured while doing this. Creating a *job* record in the
backend was never broken: those four columns carry a `default` in
their TCA, so the DataHandler fills them; only `pages.link`, a TCA
`link` field without a default, reaches the database unnamed. And
TYPO3 v12 core itself never declares a default on a `TEXT` column -
this shape is not one the v12 baseline can express.

Both extensions gain a functional test that writes a record without
naming the columns, one through a plain insert and one through the
DataHandler. Both fail on v12 with MySQL before this change.

The TCA is left untouched, and an `Important` changelog entry per
extension asks for the database schema to be updated. Nothing is
converted: existing rows keep their empty string, records written
without the column store NULL from now on.
```

`d6eb9191f`, a `[TASK]` that renames tests, explains why the old names were
wrong, and — again branch specific — says plainly that the distinction it
restores is not observable here:

```
[TASK] ACE-381: Fix the language overlay test names

Four functional tests were named "_fallbackMode" but configured
"fallbackType: 'content_fallback'", which is not a fallback type
TYPO3 knows. LanguageAspectFactory lands anything unknown in its
default branch, which means "OVERLAYS_OFF" and, less obviously,
throws the configured fallback chain away and replaces it with [0].
The repositories then lift "OVERLAYS_OFF" to
"OVERLAYS_ON_WITH_FLOATING", so these tests ran strict overlay
semantics under a name promising the opposite.

They now configure "free", the real type producing the same
"OVERLAYS_OFF", and are named "_freeMode". Nothing about their
behaviour changes: both repositories build the LanguageAspect with
three arguments, so its "fallbackChain" defaults to empty and the
site's chain never reaches the query - the only thing that
separated the two values.

Genuine fallback mode is covered by two new tests. It maps to
"OVERLAYS_MIXED", which the repositories leave alone, so the
untranslated selected record is kept in the default language.

On this branch all three tests render the same output, because no
supported TYPO3 version honours the requested overlay type for
untranslated selected records - forge #88886 was fixed in v14.3.6
and never backported to v13.4. The query paths differ all the same,
and on the 3.x line the three diverge, so the docblocks say so and
warn against collapsing them.
```

Note that the two differ in how they quote identifiers — backticks in one,
double quotes in the other. That is not standardized; readability is. Note also
that the first one runs past 72 characters in one place: the SQL is quoted
literally, exactly as the shell commands in a `[RELEASE]` body are.

A third, `2d2e56e58` `[BUGFIX] ACE-345: Resolve category types in Fluid`, is
worth reading in full with `git show`: it names the cause, why the defect stayed
invisible, why the shipped templates were unaffected by accident, and what the
added tests prove.

## Footers

`Resolves:` and `Releases:` come from the TYPO3 Core workflow, where they drive
forge and the cherry-pick targets. Neither exists here, and both **may be
skipped**. The issue reference goes into the subject instead, as described
above.

That is also what the history shows: across the last 600 commits on `2` only
four carry such a footer — three `Resolves:`, which each repeat a reference that
is already in their subject, and one `Related:` pointing at a second issue. They
date from February and March 2026, and nothing since has used one.

`Change-Id:` is a Gerrit artefact and never appears here — review happens on
GitHub.

## Issue references

Two trackers are involved, and they are not interchangeable.

| Reference | Tracker  | Where it appears                                         |
|-----------|----------|----------------------------------------------------------|
| `ACE-NNN` | YouTrack | Commit subjects, pull request titles, changelog entries  |
| `#NNN`    | GitHub   | Pull requests and issues on `fgtclb/academic-extensions` |

`ACE-NNN` are **YouTrack** keys. The public issue and pull request tracker is
**GitHub**, `fgtclb/academic-extensions` — that is what every package's
`composer.json` points `support.issues` at, and it is where external reports
arrive. A GitHub number is therefore never written as an `ACE` key and an `ACE`
key never as `#NNN`.

An issue reference is **verified before it is written into a commit**, never
assumed. `ACE-` plus a plausible number always looks right and is wrong often
enough to matter: a transposed digit points at a real but unrelated issue, and
that wrong link then survives in the history, in the changelog and in the
release notes. Look the key up and confirm it describes the change in hand
before committing.

## Attribution

Contributions are attributed to the person who submits them. Whoever submits a
change is responsible for it in full, regardless of which tools were used to
produce it, and tooling is never credited as an author: no `Co-authored-by:`
trailer for a tool, no `AI-assisted:` trailer, no "Generated with …" notice —
not in a commit, a pull request, an issue or a file. `Co-authored-by:` is used
only for an actual human co-author. Commit messages, pull request texts and
documentation are written in the author's own voice.

## See also

- [Pull requests](pull-requests.md) — branch naming, the rebase merge model and
  the pre-flight gates.
- `AGENTS.md` in the repository root — the working rules this page's
  attribution section restates, plus the quality gates and the backport targets.
- `README.md` — the branch and extension support matrix.
