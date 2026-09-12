## Context

See `proposal.md` for the motivation. Verified on `main`:

- `Partials/Job/Information.html:8-47` loops over a fixed field list and
  labels each item with `jobs.{item}` (:30). The `else` branch prints the
  value (:41).
- `locallang.xlf` has no `jobs.internationalsWelcome` or
  `jobs.alumniRecommend`; the only related key is
  `create.job.alumniRecommend.label` ("Created by Alumni", form label). The
  flags therefore print an empty label and `1`.
- **`jobs.link` exists** ("Link", `locallang.xlf:227`, German at
  `de.locallang.xlf:292`). The analysis assumed it had to be created; it is
  the row label, and what is missing is the anchor.
- The `link` column is TCA `type => link` (`tx_academicjobs_domain_model_job.php:238-245`),
  so it can hold `t3://` references, which the partial prints literally.
- The partial is used only by `Templates/Job/Show.html:31`.
- The list item partial `Partials/Job/Item.html:29-67`, rendered by
  `Templates/Job/List.html:16`, loops over the same field list with the same
  label and value branches, so the list view has the same flag and link
  defect.

## Goals / Non-Goals

**Goals:**

- Both partials render every item of their list correctly; each stays the
  override point of its view.

**Non-Goals:**

- A field list or formatter setting.

## Decisions

### Flags render their label only

Two new keys `jobs.internationalsWelcome` and `jobs.alumniRecommend` (English
and German, one line per `source`/`target`, two-space indentation) and a
branch for the two flag items that prints the label without the value. The
existing `f:if` on `{job.{item}}` already hides an unset flag.

### The link is a typolink with its own text key

The `link` item renders `f:link.typolink parameter="{job.link}"` with the
text of a new key `jobs.linkText`. The row label `jobs.link` is left as it is,
so a site that overrides one label does not change the other.

Rejected: reusing `jobs.link` as the anchor text. Its current meaning is the
row label "Link", and sites that translated or overrode it would get their
row label as link text.

Rejected: a field-list setting with per-field formatters. That is a feature,
not a fix.

### Decided: frontend wording of the flags and the link

| Key                          | English                          | German                                |
|------------------------------|----------------------------------|---------------------------------------|
| `jobs.internationalsWelcome` | International applicants welcome | Internationale Bewerbungen willkommen |
| `jobs.alumniRecommend`       | Recommended by alumni            | Von Alumni empfohlen                  |
| `jobs.linkText`              | To the job posting               | Zur Stellenausschreibung              |

The backend labels ("Alumni recommends", "Internationals willkommen") are
editor shorthand and read poorly to visitors, so the frontend gets its own
wording. The form label "Created by Alumni" contradicts the meaning of the
flag; this detail wording is the one the new-job form change aligns to.

### Decided: the list item partial is fixed as well

`Partials/Job/Item.html` gets the same flag and link branches as
`Information.html`, with the same keys. Both partials carry the same loop, so
fixing only the detail view would leave the defect in the job list.

### Decided: the change is carried by ACE-596

ACE-371 is closed as a duplicate of ACE-596, and ACE-596's scope grows by the
job link rendering. Both defects sit in the same `else` branch of the two
partials, share one rendering test and one changelog entry, so a second
issue would only split one commit.

### Decided: the link row keeps its label

The link row keeps the label `jobs.link` ("Link:") and renders the anchor
after it. Every item of the loop renders icon, bold label and value, and
keeping `jobs.link` as the row label means sites that overrode it see no
change; rendering the anchor alone would need a special case in the loop.

### Decided: backport to branch `2` as a change of its own

The fix is backported to branch `2` in a separate change with its own
analysis. ACE-596 states that the same partials and XLF files are defective
there; the XLF files of branch `2` are indented with tabs, so the backport
needs its own adaptation rather than a cherry-pick.

## Risks / Trade-offs

- [The link text changes from the stored URL to a label] → Named in the
  `Important-` changelog entry; a site that wants the URL overrides the
  label or the partial.
- [The list view changes as well as the detail view] → Both partials are
  named in the `Important-` changelog entry.

## Migration Plan

None.

## Open Questions

None.
