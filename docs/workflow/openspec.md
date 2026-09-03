# OpenSpec

[OpenSpec](https://github.com/Fission-AI/OpenSpec) is a spec-driven development
workflow for AI coding agents. A change is planned as a set of Markdown
artifacts — proposal, delta specs, design, tasks — before any code is written,
implemented against those tasks, and archived afterwards, which folds the delta
specs into the living specification under `openspec/specs/`. The artifacts are
plain files in the repository, so they are reviewed in the pull request like
everything else.

This repository uses it for changes that are worth planning before coding: a
new capability, a behaviour change that spans extensions or core versions, or a
defect whose fix needs a design decision. A one-line fix does not need a
proposal, and nothing here forces one.

## What it adds to the repository

| Path                                                   | Tracked | What it is                                                                                                                              |
|--------------------------------------------------------|---------|-----------------------------------------------------------------------------------------------------------------------------------------|
| `openspec/config.yaml`                                 | yes     | The workflow schema (`spec-driven`), the project context shown to the agent, and per-artifact rules.                                    |
| `openspec/specs/<capability-path>/spec.md`             | yes     | The **main specs**: the current behaviour contract, one file per capability. Empty at the moment — it fills up as changes are archived. |
| `openspec/changes/<change-name>/`                      | yes     | One **active change**: its artifacts, plus a generated `.openspec.yaml` and `README.md`.                                                |
| `openspec/changes/archive/<YYYY-MM-DD>-<change-name>/` | yes     | Archived changes, moved there by `openspec archive` after their delta specs were folded into the main specs.                            |
| `.claude/commands/opsx/`, `.claude/skills/openspec-*/` | yes     | The Claude Code commands and skills — **generated**, see below.                                                                         |
| `.gemini/commands/opsx/`, `.gemini/skills/openspec-*/` | yes     | The same for Gemini CLI.                                                                                                                |
| `.junie/commands/`, `.junie/skills/openspec-*/`        | yes     | The same for JetBrains Junie.                                                                                                           |
| `.opencode/commands/`, `.opencode/skills/openspec-*/`  | yes     | The same for OpenCode.                                                                                                                  |
| `.agents/skills/openspec-*/`                           | yes     | The tool-independent skills directory (`agents` tool id): skills only, no commands, for tools that read `.agents/skills/`.              |

The tool directories were produced by `openspec init --tools
agents,claude,gemini,junie,opencode` (OpenSpec 1.12.0, recorded as `generatedBy` in
each skill's front matter) and are refreshed by `openspec update` after a CLI
upgrade. **Never edit them by hand** — the next `openspec update` overwrites
them. For the same reason the `lintMarkdown` gate skips those directories
(`Build/markdown.mjs`, `skippedDirectories`); the `openspec/` tree itself is
hand-written and is linted like every other Markdown file.

`.gitignore` used to ignore `/.claude` wholesale. It now ignores only
`/.claude/*.local.*`, so the generated commands and skills are tracked while a
personal `settings.local.json` still is not.

## Installing the CLI

The CLI is a global Node.js package. OpenSpec 1.12.0 requires Node.js 20.19.0
or newer (`engines.node` in its `package.json`).

```bash
node --version                                  # >= 20.19.0
npm install -g @fission-ai/openspec@latest
openspec --version
```

Any global package manager works (`pnpm add -g`, `bun add -g`,
`yarn global add` on Yarn 1.x); the choice has nothing to do with how this
repository's own Node dependencies are installed, which is always `npm ci`
through `runTests.sh -s npm`. With a Node version manager — fnm, nvm, volta,
asdf — the binary belongs to the Node version that was active during the
install, so `openspec` is only on `PATH` while that version is. A shell where
`node` is not found is the version manager not being initialized, not a missing
package.

The CLI is **not** part of the container harness: `runTests.sh` never calls
it, and no gate depends on it. It is a developer tool that the agent commands
call locally.

## The artifacts

The `spec-driven` schema builds four artifacts in a fixed order. `openspec
status --change <name>` shows which are done and which are still blocked:

| Artifact   | File                              | Answers                             | Blocked by        |
|------------|-----------------------------------|-------------------------------------|-------------------|
| `proposal` | `proposal.md`                     | Why, what changes, which capability | —                 |
| `specs`    | `specs/<capability-path>/spec.md` | What the system must do — a *delta* | `proposal`        |
| `design`   | `design.md`                       | How, and which alternative lost     | `proposal`        |
| `tasks`    | `tasks.md`                        | The implementation steps, checkable | `specs`, `design` |

A delta spec is not the whole spec. It lists what the change does to the main
spec of that capability under `## ADDED Requirements`, `## MODIFIED
Requirements`, `## REMOVED Requirements` or `## RENAMED Requirements`. Each
requirement is a `### Requirement: <name>` heading with normative text (SHALL
or MUST) and at least one `#### Scenario: <name>` block in WHEN/THEN form. The
heading levels matter: a scenario with three hashes is silently not a
scenario, and `openspec validate` rejects a change without a single delta.

```markdown
## ADDED Requirements

### Requirement: Profile translations follow the default language
The system SHALL copy every exclude column of a profile into its translations
when the default-language record is saved.

#### Scenario: Editor saves the default-language profile
- **WHEN** a backend user saves a profile in the default language
- **THEN** every translation of that profile carries the same exclude column values
```

A change that touches no behaviour — tooling, documentation, a refactoring —
sets `skip_specs: true` in its `.openspec.yaml` instead of inventing a
requirement to satisfy validation.

`openspec instructions <artifact> --change <name>` prints the template and the
guidance for one artifact, with the context and rules from `config.yaml`
merged in. That output is the authoritative shape of an artifact; this page
only summarizes it.

## The lifecycle

1. **Explore** *(optional)* — think the problem through with the agent, read
   code, compare options. Nothing is written until a decision is made.
2. **Propose** — create the change and generate all four artifacts. This is
   planning only: the agent stops before touching code, even when the request
   said "fix it".
3. **Apply** — implement `tasks.md`, ticking tasks off as they are done, with
   proposal, specs and design as context.
4. **Archive** — verify every task is done, fold the delta specs into
   `openspec/specs/`, and move the change to `openspec/changes/archive/` under
   a date-prefixed name.

Two further commands sit beside the lifecycle: **update** revises the existing
artifacts of a change after a decision moved (it never edits code), and
**sync** folds the delta specs into the main specs without archiving, for a
long-running change whose specs should already be visible.

## Invoking it from your tool

The spelling differs per tool. It is taken from the generated files, not from
memory:

| Tool        | Commands                                                                                       | Skills                                           |
|-------------|------------------------------------------------------------------------------------------------|--------------------------------------------------|
| Claude Code | `/opsx:propose`, `/opsx:apply`, `/opsx:archive`, `/opsx:explore`, `/opsx:sync`, `/opsx:update` | `/openspec-propose`, `/openspec-apply-change`, … |
| Gemini CLI  | `/opsx:propose`, … (`.gemini/commands/opsx/*.toml`)                                            | `openspec-propose`, … (`.gemini/skills/`)        |
| Junie       | `/opsx-propose`, `/opsx-apply`, `/opsx-archive`, `/opsx-explore`, `/opsx-sync`, `/opsx-update` | `openspec-propose`, … (`.junie/skills/`)         |
| OpenCode    | `/opsx-propose`, … (`.opencode/commands/opsx-*.md`)                                            | `openspec-propose`, … (`.opencode/skills/`)      |

The six skills are `openspec-propose`, `openspec-apply-change`,
`openspec-archive-change`, `openspec-explore`, `openspec-sync-specs` and
`openspec-update-change`. A command and the skill of the same name do the
same thing; the command is the shorter spelling.

Any other tool is added with `openspec init --tools <id>` — `openspec init
--help` lists the ids — and its generated directory is committed the same way.

## Quickstart

With an agent, in Claude Code:

```text
/opsx:explore   the profile sync writes to translations that are not exclude columns
/opsx:propose   ace-500-sync-only-exclude-columns
/opsx:apply     ace-500-sync-only-exclude-columns
/opsx:archive   ace-500-sync-only-exclude-columns
```

The same steps by hand, which is also what the agent runs underneath:

```bash
openspec new change ace-500-sync-only-exclude-columns      # scaffold the change
openspec status --change ace-500-sync-only-exclude-columns # what is done, what is blocked
openspec instructions proposal --change ace-500-sync-only-exclude-columns
#   … write proposal.md, then specs/…/spec.md and design.md, then tasks.md
openspec validate ace-500-sync-only-exclude-columns --strict
openspec list                                              # active changes
openspec view                                              # dashboard of specs and changes
openspec archive ace-500-sync-only-exclude-columns         # after the last task is ticked
```

`openspec doctor` reports whether the OpenSpec root resolves, and `openspec
validate --all --strict` checks every change and spec at once.

## Conventions in this repository

- **A change is named after its issue.** Kebab-case, prefixed with the verified
  YouTrack key: `ace-NNN-<short-slug>`. The same key goes into the commit
  subject, see [Commit messages](commit-messages.md). A change without an
  issue is named after what it does.
- **A change travels with its pull request and is archived in it.** The active
  change directory is committed with the implementation, and the last commit of
  the pull request archives it, so a merged branch never carries an active
  change and the main specs on `main` describe what `main` does.
- **Specs are organized per extension.** `<capability-path>` is
  `<extension-directory>/<capability>`, for example
  `academic-persons/profile-translation-sync` — the directory name under
  `packages/fgtclb/`, which is not always the extension key, see
  [Monorepo layout](../development/monorepo-layout.md#the-directory-name-is-not-the-extension-key).
- **Specs describe behaviour, not code.** What an editor, integrator or site
  visitor observes, on which core versions. Class names, ViewHelper names and
  query shapes belong in `design.md`.
- **The artifacts add to the existing rules, they replace none of them.** The
  gates in [Quality gates](../development/quality-gates.md) still run for both
  core versions, [`docs/`](../Index.md) and the extension's `Documentation/`
  changelog are still updated in the same change, and the commit message still
  follows the TYPO3 Core conventions. `tasks.md` lists those steps so the apply
  workflow does not stop before them — `config.yaml` says so under `rules`.
- **Tooling-only changes set `skip_specs: true`.** A change to the harness,
  the documentation or the development instances has no behaviour to specify.
- **A backport is a change of its own on the target branch.** Specs are
  branch-scoped and are never synced between `main` and `2`; the backport pull
  request re-derives the delta from its analysis, see
  [Backporting](backporting.md#backporting-an-openspec-change).

## Upgrading the CLI

After `npm install -g @fission-ai/openspec@latest`, run `openspec update` in
the repository root and commit what it regenerates. Read the diff of the
generated files as an upgrade, not as a change of ours — and run `lintMarkdown`
afterwards: it skips the generated directories, but `openspec/` is linted.

## See also

- [Pull requests](pull-requests.md) — the pre-flight checklist that a
  `tasks.md` has to end with.
- [Backporting](backporting.md#backporting-an-openspec-change) — what of a
  change travels to branch `2`, and what never does.
- [Commit messages](commit-messages.md) — the verified issue reference the
  change name is derived from.
- [Changelog and documentation](changelog-and-documentation.md) — the
  user-facing documentation a change still has to ship.
- [Monorepo layout](../development/monorepo-layout.md) — where `openspec/`
  and the generated tool directories sit among the other top-level
  directories.
- `AGENTS.md` in the repository root — the rules an agent applies while
  writing and implementing the artifacts.
- [OpenSpec on GitHub](https://github.com/Fission-AI/OpenSpec) — the upstream
  README and documentation;
  [issues](https://github.com/Fission-AI/OpenSpec/issues) for the CLI itself.
