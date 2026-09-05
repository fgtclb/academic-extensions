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
```

The issue reference sits **inside the subject**, directly after the tag and
before the description, separated by a colon. It is not a footer here. 90 of the
last 100 commits carry an `ACE-NNN` key in that position. The 10 that do not are
documentation fixes and the TYPO3 v14 support series, which was carried out as a
sequence of steps rather than per issue. Release commits carry no reference
either.

## Subject line

| Rule            | Value                                           |
|-----------------|-------------------------------------------------|
| Target length   | 52 characters, tag and issue reference included |
| Hard cap        | 72 characters                                   |
| Mood            | Imperative — "Add", "Fix", "Drop", "Cover"      |
| First word      | Capitalized                                     |
| Trailing period | None                                            |

What the repository actually does: 85 of the last 100 subjects are within 52
characters. The fifteen that are not run to 53–59 characters. Older history is
looser — across the last 600 commits only 339 stay within 52, and the longest
subject on record is 138 characters, from February 2024. The 52 character target is
the current standard; the drift is history, not licence.

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

The counts are what is in use; a tag that does not appear here is not
established practice in this repository. They are a moving window, so re-run
the command rather than trusting the table after a long series of commits.

| Tag              | Count | Use                                             | Example commit                                                        |
|------------------|-------|-------------------------------------------------|-----------------------------------------------------------------------|
| `[TASK]`         | 304   | Refactoring, tests, CI, dependencies, tooling   | `94c7aedc5` `[TASK] ACE-382: Cache only composer's dist archives`     |
| `[BUGFIX]`       | 130   | Fixes incorrect behaviour                       | `8b5557037` `[BUGFIX] ACE-358: Drop the defaults on TEXT columns`     |
| `[DOCS]`         | 83    | Documentation only, shipped or developer facing | `7d139a44f` `[DOCS] ACE-294: Record the TYPO3 v15 blockers`           |
| `[FEATURE]`      | 31    | New, user-facing functionality                  | `4264cfe4b` `[FEATURE] ACE-236: Select the displayed address records` |
| `[!!!][TASK]`    | 29    | Breaking change, marker first                   | `63fd7b099` `[!!!][TASK] ACE-306: Remove the profile switcher`        |
| `[RELEASE]`      | 11    | Release commits, maintainers only               | `1f5334422` `[RELEASE] 2.3.4`                                         |
| `[!!!][FEATURE]` | 10    | Breaking new functionality                      | `[!!!][FEATURE] ACE-304: Replace a profile image`                     |

The `[!!!]` marker always comes first and is combined with the tag that
describes the nature of the change, never used alone.

`[RELEASE]` commits are the one deliberate exception to the rules above: they
carry no issue reference, and their body is the literal, unwrapped command
sequence the release was produced with, so a release can be reproduced or
audited later.

## Complete examples

Two messages from the history, verbatim. Both are ordinary commits, not
showcases — this is the expected level of detail.

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

`Resolves:` and `Releases:` come from the TYPO3 Core workflow, where they drive
forge and the cherry-pick targets. Neither exists here, and both **may be
skipped**. The issue reference goes into the subject instead, as described
above.

That is also what the history shows: across the last 600 commits only four carry
a `Resolves:` or `Related:` footer, all four repeat a reference that is already
in their subject, and the most recent of them is from March 2026.

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
