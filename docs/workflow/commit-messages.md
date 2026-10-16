# Commit messages

This repository follows the TYPO3 Core commit message rules, with the two
adjustments an extension mono repository needs: there is no forge issue, and
there is no Gerrit. Everything below is derived from the actual history of this
repository, not from a generic template — the counts quoted were taken from
`git log` at the time of writing and can be reproduced.

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

Resolves: ACE-NNN
```

The issue reference sits **inside the subject**, directly after the tag and
before the description, separated by a colon, and is repeated in a footer, see
[Footers](#footers). On 2026-10-16, all of the last 100 commits on `main`
carried an `ACE-NNN` key in that position. The ones further back that do not are
proposals of OpenSpec changes that had no issue yet, a few documentation and
tooling fixes, and the TYPO3 v14 support series, which was carried out as a
sequence of steps rather than per issue. Release commits carry no reference
either. The number of recent subjects without the key:

```bash
git log -100 --format=%s | grep -vcE '^(\[!!!\])?\[[A-Z]+\] ACE-[0-9]+: '
```

## Subject line

| Rule            | Value                                           |
|-----------------|-------------------------------------------------|
| Target length   | 52 characters, tag and issue reference included |
| Hard cap        | 72 characters                                   |
| Mood            | Imperative — "Add", "Fix", "Drop", "Cover"      |
| First word      | Capitalized                                     |
| Trailing period | None                                            |

What the repository actually does: on 2026-10-16, 99 of the last 100 subjects
on `main` are within 52 characters, and the one that is not runs to 61. Across
the last 600 commits 581 stay within 52 and none exceeds 72. Older history is
looser, the longest subject on record is 138 characters, from February 2024.
The 52 character target is the current standard, the drift is history, not
licence. Measured with

```bash
git log -100 --format=%s | awk 'length <= 52' | wc -l
git log -600 --format=%s | awk 'length <= 52' | wc -l
```

The budget is tighter than it looks. `[BUGFIX] ACE-358: ` alone is 18
characters, so the description itself has about 34 left. A subject that does not
fit is usually a commit that does two things.

## Body

The body is wrapped at **72 characters** and separated from the subject by a
blank line. It is required for anything that is not trivially self-explaining,
and in practice that is nearly everything: the recent history has substantial
bodies on almost every commit.

Verified over the last 60 commits: the longest body line is at or below 72
characters in most of them, with a handful of lines running one to three
characters over. The clear exceptions are literal shell commands, which are left
unwrapped on purpose — a wrapped command cannot be copied and pasted, and that
is the entire point of putting it in the message.

Write what changed and **why**, not how. The bodies that have proved useful in
this repository consistently contain four things:

- the behaviour before the change, concretely enough to recognize it,
- the cause, named at the level of the class or the API involved,
- the alternative that was rejected and the reason it was rejected,
- how the change was verified, including which core version it was measured on.

## Tags

Taken from the last 600 commits rather than from a generic list, with

```bash
git log -600 --format='%s' | grep -oP '^(\[!!!\])?\[[A-Z]+\]' | sort | uniq -c
```

The counts are what is in use, measured on `main` on 2026-10-16. A tag that
does not appear here is not established practice in this repository. They are a
moving window, so re-run the command rather than trusting the table after a
long series of commits.

| Tag              | Count | Use                                             | Example commit                                                        |
|------------------|-------|-------------------------------------------------|-----------------------------------------------------------------------|
| `[TASK]`         | 348   | Refactoring, tests, CI, dependencies, tooling   | `94c7aedc5` `[TASK] ACE-382: Cache only composer's dist archives`     |
| `[BUGFIX]`       | 101   | Fixes incorrect behaviour                       | `8b5557037` `[BUGFIX] ACE-358: Drop the defaults on TEXT columns`     |
| `[FEATURE]`      | 78    | New, user-facing functionality                  | `4264cfe4b` `[FEATURE] ACE-236: Select the displayed address records` |
| `[DOCS]`         | 34    | Documentation only, shipped or developer facing | `7d139a44f` `[DOCS] ACE-294: Record the TYPO3 v15 blockers`           |
| `[!!!][TASK]`    | 25    | Breaking change, marker first                   | `63fd7b099` `[!!!][TASK] ACE-306: Remove the profile switcher`        |
| `[!!!][FEATURE]` | 12    | Breaking new functionality                      | `[!!!][FEATURE] ACE-304: Replace a profile image`                     |
| `[!!!][BUGFIX]`  | 2     | Breaking fix of incorrect behaviour             | `661477377` `[!!!][BUGFIX] ACE-793: Keep core page description`       |
| `[RELEASE]`      | 0     | Release commits, maintainers only               | `1f5334422` `[RELEASE] 2.3.4`                                         |

`[RELEASE]` has no commit in that window. The last release commit in the
history of `main`, `1f5334422`, is from May 2026. Release commits still use
this tag.

The `[!!!]` marker always comes first and is combined with the tag that
describes the nature of the change, never used alone.

`[RELEASE]` commits are the one deliberate exception to the rules above: they
carry no issue reference, and their body is the literal, unwrapped command
sequence the release was produced with, so a release can be reproduced or
audited later.

## Complete examples

Two messages from the history, verbatim. Both are ordinary commits, not
showcases, and this is the expected level of detail. Both are from August 2026,
before every commit carried a footer. Written today, they would end with
`Resolves: ACE-381` and `Resolves: ACE-345` below the body.

`bbc4ea33e`, a `[TASK]` that renames tests and explains why the old names were
wrong, which alternative was rejected and on which core versions the result was
measured:

```
[TASK] ACE-381: Fix the language overlay test names

Four functional tests were named "_fallbackMode" but configured
"fallbackType: 'content_fallback'", which is not a fallback type
TYPO3 knows. LanguageAspectFactory lands anything unknown in its
default branch, which means "OVERLAYS_OFF" and, less obviously,
throws the configured fallback chain away and replaces it with [0].
The repositories then lift "OVERLAYS_OFF" to
"OVERLAYS_ON_WITH_FLOATING" (ACE-341), so these tests ran strict
overlay semantics under a name promising the opposite - which is
why they failed next to the strict ones when v14.3.6 started
honouring the requested overlay type.

They now configure "free", the real type that produces the same
"OVERLAYS_OFF", and are named "_freeMode". Nothing about their
behaviour changes: both repositories build the LanguageAspect with
three arguments, so its "fallbackChain" defaults to empty and the
site's chain never reaches the query - the only thing that
separated the two values. Measured on v14.3.6, before and after:
7 tests, 26 assertions, 2 skipped either way.

Switching them to a real "fallback" instead was rejected: it would
have removed the only coverage of "OVERLAYS_OFF" with an
untranslated selected record. That combination is reachable in
production through "fallbackType: free", and the sole other free
test uses a fully localized fixture whose overlay never has to
decide anything.

Genuine fallback mode is covered by two new tests instead. It maps
to "OVERLAYS_MIXED", which the repositories leave alone, so the
untranslated selected record is kept and rendered in the default
language. That holds before and after the fix for forge #88886,
because the requested type is already the one the fix honours, so
these two need no core version guard - verified green on 13.4.34
and on 14.3.6.
```

`7860c783`, a `[BUGFIX]` that names the cause, why the defect stayed invisible,
why the shipped templates were unaffected, and what the added tests prove:

```
[BUGFIX] ACE-345: Resolve category types in Fluid

`CategoryCollection::offsetExists()` answered from `$typeSortedCollection`,
the grouped view the collection builds lazily. That array stays empty
until `getAllCategoriesByType()` has been called once on the instance, so
a registered category type was reported as absent while `offsetGet()`,
which resolves through `getCategoriesByTypeName()`, answered from the
start.

Fluid resolves a path segment on an `ArrayAccess` subject through
`offsetExists()` and reads the offset only when that returned `true`, so
`{categories.researchField}` rendered nothing - without an exception or a
log entry - until something else had computed the grouping first. The
partials shipped here were unaffected by accident: they iterate
`{categories.allCategoriesByType}` before reaching a type by name.

`offsetExists()` now answers from the registered type identifiers, the
same source the lookup it guards resolves against, which is what
`FilterCollection::offsetExists()` already did.

The added tests assert both accessors agree on an untouched collection,
and drive the template expression through Fluid's variable provider so
the reason for the defect is covered, not just its symptom.
```

Note that the two differ in how they quote identifiers — backticks in one,
double quotes in the other. That is not standardized; readability is.

## Footers

Every commit that belongs to an issue ends with at least one footer, separated
from the body by a blank line, one issue per line. The key stays in the
subject as well. Two keywords are in use:

- `Resolves: ACE-NNN` names each issue the commit fixes or delivers.
  `b7f1cfda8` resolves two, `ACE-912` and `ACE-919`.
- `Related: ACE-NNN` names every further issue the commit touches. It also
  names the issue of the subject when the commit delivers nothing of it: the
  proposal and the archive commit of an OpenSpec change, or a step before or
  after the commit that resolves the issue.

An issue delivered in several commits carries `Resolves:` on each commit that
delivers a part of it.

`Resolves:` lines come first. A defect fix that also touches three earlier
issues, `b0741283f`:

```
[BUGFIX] ACE-894: Open study plan dialogs per copy

…

Resolves: ACE-894
Related: ACE-704
Related: ACE-707
Related: ACE-818
```

The archive commit of an OpenSpec change, `dcc1b1c5b`, names the issue it
archives with `Related:`, because the commit before it resolved the issue:

```
[TASK] ACE-922: Archive the select items row change

…

Related: ACE-922
```

A commit without an issue of its own and without a related one, a release for
example, carries no footer. A proposal without an issue of its own names the
issues it touches with `Related:`, as `2d44e4ba7` does. On 2026-10-16, every
one of the last 60 commits on `main` carried `Resolves:` or `Related:`, which
is the practice since late September 2026. Check it with

```bash
git log -60 --format='%h %(trailers:key=Resolves,key=Related,valueonly,separator=%x2C)'
```

`Releases:` comes from the TYPO3 Core workflow, where it names the branches a
change is cherry-picked to. Some commits of August and September 2026 carry
`Releases: main`, none since. Leave it out: the target branch is the one the
pull request is opened against, and a backport is a pull request of its own,
see [Backporting](backporting.md).

`Change-Id:` is a Gerrit artefact and never appears here, review happens on
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

The same YouTrack instance holds the projects of the customers this
repository is developed for. Their keys must **never** appear in this public
repository, not in a commit message, a pull request, a file, or an archived
OpenSpec change. They name a customer and lead to a tracker nobody outside can
read. Link the ACE issue to the project issue in YouTrack instead, and give the
ACE key here. For files, `CustomerIssueKeyTest` in
`packages-dev/monorepo-shared` refuses every reference of the shape `ABC-NNN`
whose prefix is not on its short list of known ones, see
[Customer issue keys](../testing/unit-tests.md#customer-issue-keys). A commit
message and a pull request text are checked by nobody but their author.

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
