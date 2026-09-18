# Design: Make the job contact phone link dialable

## Context

See `proposal.md` — *Why*. What matters for the approach:

- The contact block lives in `Resources/Private/Partials/Job/Contact.html` and
  is rendered from `Templates/Job/Show.html` only, guarded by a `contact`
  variable that is set when any of contact name, e-mail or phone is filled. The
  job list does not render it, so the detail view is the single call site.
- `academic_persons` solved the same problem twice already, in
  `Profile/Contract/Field.html` and `Profile/PublicProfile/Contact.html`
  (ACE-679). Both build the target with the `f:replace` ViewHelper and leave
  the link text alone.
- The property is a plain `string` on the job model with no normalisation
  anywhere; whatever an editor types is what is stored.
- TYPO3 v13 and v14 are identical here, and so is v12 on branch `2`: the
  ViewHelper is already in use on that branch in `academic_persons`.

## Goals / Non-Goals

**Goals**

- One line in one partial, no PHP, no core version switch.
- The same construct as `academic_persons`, so the three call sites read alike
  and a future prefix could be added to all of them the same way.

**Non-Goals**

- Beyond the proposal's non-goals: no shared partial or ViewHelper extracted
  for the two extensions. `academic_jobs` does not depend on
  `academic_persons`, and a shared helper in `academic_base` for one attribute
  would be more coupling than the duplication costs.

## Decisions

**Strip the spaces in the template, not in the model.**
The stored value is what an editor typed and what the form redisplays; the
model's getter is also read by the new-job form, where the spelling with spaces
is the wanted one. Deriving the target where it is used keeps one value with
one meaning.
*Rejected:* a `getContactPhoneLinkTarget()` accessor on the model — it puts a
presentation concern into the domain object and would have to be kept in step
with the visible property. *Rejected:* normalising on save — it would rewrite
existing records on the next edit and lose the reader-friendly spelling.

**Use `f:replace`, not a regular expression.**
Spaces are the only character the stored numbers carry that a `tel:` URI
forbids in practice, and it is what the two persons partials already do. A
broader sanitiser would silently drop characters that are legal in a `tel:`
URI, such as `+`, `-` and `()`.
*Rejected:* `f:format.raw` combined with a PHP-side normaliser, for the reason
above.

**No prefix argument on the partial.**
See the proposal's non-goals. If a job contact prefix is ever wanted it is a
feature with its own issue, and the construct chosen here takes it without a
rewrite — the persons partials show the shape.

## Risks / Trade-offs

- **An installation may already rely on the spaces in the target** — unlikely
  to the point of theoretical: a target with spaces is what the fix removes
  because devices cannot use it. Mitigated by the `Important` changelog entry,
  which names the old and the new target.
- **The stored number may carry other characters a dialer rejects**, for
  example a slash in `089/1234`. Out of scope, and unchanged by this fix: it
  was undialable before and stays so. The changelog entry says that the fix is
  about spaces, not about validating the stored number.
- **An override of the partial keeps the defect.** Named in the changelog
  entry's *Affected Installations*, as `academic_persons` did for the same
  correction.

## Migration Plan

None. No stored data changes, no setting is introduced and no markup an
override could hook into moves. Rolling back is reverting one line.
