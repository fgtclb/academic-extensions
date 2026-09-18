## Context

See `proposal.md` — *Why*. What the backport analysis established, and what
follows from it for the approach:

- `Resources/Private/Partials/Job/Contact.html` is **byte-identical** to the
  copy on `main` (`git diff main origin/2 -- <path>` printed nothing), and it is
  rendered from `Templates/Job/Show.html` only, guarded by a `contact` variable
  set when any of contact name, e-mail or phone is filled. The job list does not
  render it, so the detail view is the single call site here as well.
- The `f:replace` ViewHelper ships with Fluid on both core versions this branch
  supports, with the same arguments — checked in the installed vendor tree of
  each, not recalled. It is also in use on this branch in `academic_persons`,
  but only since the ACE-679 backport of the same day, so that is a sibling
  rather than evidence.
- The property is a plain `string` on the job model with no normalisation
  anywhere, exactly as on `main`.
- The plugin test class here is deliberately narrower than its counterpart on
  `main`: it exists since the ACE-596 backport and covers the job flags, the job
  link and a rendering baseline. Its `jobPages.csv` carries **no contact columns
  at all** and no test asserts on the contact block.

## Goals / Non-Goals

**Goals**

- The same one-line construct as `main`, so the two branches read alike and a
  later change touches the same shape on both.
- Contact coverage where there is none, without disturbing the fixture the flag
  and link tests assert counts against.

**Non-Goals**

- Beyond the proposal's non-goals: no attempt to converge the rest of this test
  class with the one on `main`. Only what this change needs is aligned.

## Decisions

**Strip the spaces in the template, not in the model.**
The stored value is what an editor typed and what the new-job form redisplays.
Deriving the target where it is used keeps one value with one meaning.
*Rejected:* an accessor on the model, which puts a presentation concern into the
domain object. *Rejected:* normalising on save, which would rewrite existing
records on the next edit and lose the reader-friendly spelling.

**A new fixture rather than contact columns in `jobPages.csv`.**
Three tests assert job counts and list contents against that fixture — one of
them with `preg_match_all` on an exact count — so widening it puts unrelated
tests at risk for nothing. `jobPages_contactPhone.csv` carries the three contact
scenarios and nothing else.
*Rejected:* adding the columns to `jobPages.csv` and leaving them empty for the
existing jobs, which would still change every one of its rows.

**Parameterise `setUpTestCase()` with a defaulted first argument.**
The method hardcoded `jobPages.csv`. It now takes the fixture name with
`'jobPages'` as its default, which is the signature the copy on `main` has,
minus the default. Every existing call site stays untouched, because the one
call that passes the language flag passes it by name.
*Rejected:* a required argument as on `main` — it would rewrite eight call sites
of tests this change does not touch.

## Risks / Trade-offs

- **An installation may already rely on the spaces in the target** — theoretical:
  a target with spaces is what the fix removes because devices cannot use it.
  Mitigated by the `Important` changelog entry, which names both targets.
- **The stored number may carry other characters a dialer rejects**, a slash in
  `089/1234` for example, or a non-breaking space. Out of scope and unchanged:
  undialable before, undialable after. The changelog entry says so.
- **An override of the partial keeps the defect**, named in the entry's
  *Affected Installations*.

## Migration Plan

None. No stored data changes, no setting is introduced and no markup an override
could hook into moves. Rolling back is reverting one line.
