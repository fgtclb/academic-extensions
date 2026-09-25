# Development instances

Two ready-to-start TYPO3 instances live at the repository root, one per supported
core version. They exist to look at the extensions in a running backend and
frontend — they are **not** part of any test run, and `runTests.sh` never touches
them (see [Development environment](environment.md#development-instances-are-not-part-of-the-harness)).

| Folder     | TYPO3 | DDEV project          | Theme               | Frontend                                |
|------------|-------|-----------------------|---------------------|-----------------------------------------|
| `core-13/` | v13   | `core13-academics-v3` | `bootstrap_package` | <https://core13-academics-v3.ddev.site> |
| `core-14/` | v14   | `core14-academics-v3` | `bootstrap_package` | <https://core14-academics-v3.ddev.site> |

## Starting one

```shell
cd core-13
ddev start
ddev launch /typo3/        # backend
ddev launch /              # frontend
```

There is no setup step. Both instances run on **SQLite** with no database
container at all (`omit_containers: [db]`), and `config/system/additional.php`
copies the committed template from `sqlite-databases/` into `var/sqlite/` when
the database is missing. Check out, start, log in.

**Switching branches in one checkout collides.** The instance directories have
the same path on every branch but the DDEV project names differ per version line
(`core13-academics-v3` on `main`, `core13-academics-v2` on `2`), and DDEV refuses
a second name for a known path:

```
Failed to start core13-academics-v3: this project root '…/core-13' already
contains a project named 'core13-academics-v2'.
```

`ddev stop --unlist core13-academics-v2` clears it; it removes the registration
only. Two things survive the switch and are then wrong: the database in
`core-*/var/`, which `ddev composer sqlite:apply` resets, and `core-*/vendor/`,
whose autoloader points at the other branch's path packages — `ddev composer
install` rebuilds it.

Two more things survive it and are merely in the way, so the repository ignores
both:

- **`core-*/.ddev/traefik/`** — the router certificate, its private key and the
  router configuration. DDEV generates them per project and ignores them itself,
  by rewriting `.ddev/.gitignore` on every start — but it lists them under the
  *current* project name only. After a switch and a `ddev stop --unlist`, the
  other name's certificate is left outside that list and shows up in
  `git status`. Nothing under that directory is ever committed, a private key
  least of all. Deleting a stale one is safe; DDEV regenerates what it needs on
  the next `ddev start`.
- **The instance folder of the other version line** — `core-12/` here,
  `core-14/` on branch `2`. Switching removes its tracked files and leaves its
  ignored trees, so the folder stays behind as untracked noise.

## Instances in git worktrees

Every checkout carries the same `core-*/.ddev/config.yaml`, and with it the same
project names. A [git worktree](environment.md#git-worktrees) is a second
checkout, so without help its instances claim the names the main checkout
already holds, and DDEV refuses a name that is registered for another directory.
It refuses whether that project is running or only stopped:

```
Failed to start core13-academics-v3: project core13-academics-v3 project root is
already set to …/academic-extensions/core-13, refusing to change it to
…/worktree/core-13; you can `ddev stop --unlist core13-academics-v3` …
```

Unlisting in one checkout to start the other works, and moves the problem to
the next switch. Instead, a linked worktree gets names of its own:

| Checkout        | `core-13/`                     | `core-14/`                     |
|-----------------|--------------------------------|--------------------------------|
| main checkout   | `core13-academics-v3`          | `core14-academics-v3`          |
| linked worktree | `core13-academics-v3-3513e252` | `core14-academics-v3-3513e252` |

`Build/Scripts/ddevWorktreeNames.sh` writes them into
`core-*/.ddev/config.worktree.local.yaml`: the committed name as prefix, and the
first eight hex digits of the git hash of the worktree's resolved absolute path
as suffix, so both instances of one worktree share it. DDEV merges every
`.ddev/config.*.yaml` over `config.yaml` in lexical order, so the file overrides
the committed name. It also sorts after a `config.local.yaml` of your own and
wins over that file's `name`, while the rest of your file still applies. The
file is git-ignored, and nothing tracked changes. The instances need no other
change either: the site configurations use host-less `base` values and
`trustedHostsPattern` is `.*`, so the instance answers under
`https://core13-academics-v3-3513e252.ddev.site` as it does under the committed
name.

The main checkout is never touched: the script recognizes it by `git rev-parse
--git-dir` being the common git directory and does nothing there. A
`config.worktree.local.yaml` the script did not write, recognized by its first
line, is left alone with a warning.

### Opting in: the post-checkout hook

The script runs by itself once the repository's hooks directory is enabled,
**once per clone** — the setting lives in the shared git configuration and
therefore covers every worktree:

```shell
git config core.hooksPath Build/git-hooks
```

`Build/git-hooks/post-checkout` then runs the script on `git worktree add`,
before DDEV has ever seen the new worktree, and again on every branch checkout
inside a worktree. A switch to the other version line brings the other instance
folder, which needs its name too. A file checkout does nothing, and the hook
never fails a checkout.

Git takes the hook from the checkout the command is started in, relative to its
top level, and runs it inside the checkout that changed. The hook therefore
calls the script of the new worktree, never its own copy, and a branch that does
not carry the script runs nothing. `git worktree add --no-checkout` runs no
hook at all.

Without the hook, or for a worktree created before it, run the script by hand
before the first `ddev start` there:

```shell
Build/Scripts/ddevWorktreeNames.sh
```

### What the names do not cover

- **A worktree that was already started under the committed name** holds that
  registration. Release it there first, then name it and start it again:

  ```shell
  cd core-13 && ddev stop --unlist core13-academics-v3
  ../Build/Scripts/ddevWorktreeNames.sh && ddev start
  ```

- **Removing a worktree** leaves its projects behind — containers, volumes and
  the registration — because git has no hook for `git worktree remove`. Delete
  them first, in each instance of the worktree:

  ```shell
  ddev delete -Oy
  git worktree remove <path>
  ```

  A project whose directory is already gone is deleted by name from anywhere:
  `ddev delete -Oy core13-academics-v3-3513e252`. `ddev list` shows its
  approot.

- **Moving a worktree** changes its path and therefore its names. `ddev delete
  -Oy` before `git worktree move`, run the script afterwards.

- **Switching branches inside one worktree** behaves like switching them in the
  main checkout, described above: the prefix changes with the version line, so
  the same directory gets a second name, and `ddev stop --unlist <old name>`
  clears it.

## Accounts

### Backend

The admin account is the one of the
[TYPO3 contribution guide](https://docs.typo3.org/m/typo3/guide-contributionworkflow/main/en-us/Quickstart/5-TYPO3.html),
so it is the same account a core contributor already has in their fingers. It is
created by `typo3 setup` during a rebuild and shipped in both committed
templates:

|          |                        |
|----------|------------------------|
| Username | `john-doe`             |
| Password | `John-Doe-1701D.`      |
| E-mail   | `john.doe@example.com` |

The **install tool password** is not that one. `typo3 setup` writes it into
`config/system/settings.php`, but that file is tracked and is restored from git
at the end of a rebuild, so what stays is the hash committed in the repository.

### Frontend

The seed set creates four frontend users in two groups, and the first of them
exists for a reason: **`EXT:academic_persons_edit` cannot be looked at without
it.** Its controller refuses every action when no frontend user is logged in,
and it finds the profile to edit through the `frontend_users` relation of the
profile record rather than through a storage page — so a login *and* a connected
profile are both required. `jane.doe` is that user; `erik.mustermann`,
`liam.rhodes` and `sam.tester` carry the same password and exist so that a
second profile, a second group and an unconnected account can be looked at.

|          |                                                             |
|----------|-------------------------------------------------------------|
| Username | `jane.doe`                                                  |
| Password | `Frontend-User-1701D.`                                      |
| Group    | `Website users` (`fe_groups` uid 1)                         |
| Storage  | the `Data` folder, page uid 2                               |
| Profile  | `Jane Doe`, `tx_academicpersons_domain_model_profile` uid 1 |

To use it:

1. open `/login`, which carries the `felogin_login` plugin — its
   `settings.pages` names page 2, the folder the user record sits on, and a
   value naming the wrong folder makes a correct password fail silently;
2. log in; the plugin redirects to `/my-profile`
   (`settings.redirectMode = login`);
3. that page carries the `academicpersonsedit_profileediting` plugin and is set
   to `fe_group: -2`, "show at any login", so it is neither in the menu nor
   reachable for a visitor who is not logged in — anonymously it answers 403.

The editing form locks `firstName`, `middleName` and `lastName`: the shipped
`profile` validation set marks them `disabled`, because a profile name is owned
by the connected frontend user record. That is deliberate, not a defect — see
[Validation settings](../architecture/validation-settings.md).

The other two seeded profiles (`Erik Mustermann`, `Alina Sorge`) have **no**
connected frontend user. That is not an oversight either: it is what the
"logged in user without a profile" case looks like, and the plugin renders its
empty state for it.

## What the instances contain

The page tree is written from the seed set
`packages-dev/dev-site/Configuration/DataFactory/academics-instance/` — one
section per extension, one page per plugin:

| Page                    | What is on it                                                                                                                                                                               |
|-------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `/`                     | start page                                                                                                                                                                                  |
| `/data`                 | storage folder: frontend user and group, organisational units, function types, a location, three profiles with contracts, addresses, phone numbers, e-mail addresses, vita and publications |
| `/academic-persons/*`   | one page per plugin of `EXT:academic_persons`: list, list and detail, detail, card, selected profiles, selected contracts                                                                   |
| `/login`, `/my-profile` | `EXT:felogin` and the editing form of `EXT:academic_persons_edit`                                                                                                                           |
| `/data-categories`      | storage folder: `sys_category` records carrying a `category_types` type                                                                                                                     |
| `/academic-programs`    | the list plugin of `EXT:academic_programs`, and three program pages (`doktype: 20`), each carrying the details plugin                                                                       |
| `/data-partners`        | storage folder: partner roles                                                                                                                                                               |
| `/academic-partners/*`  | the four plugins of `EXT:academic_partners`, and two partner pages (`doktype: 40`)                                                                                                          |

Changing that content is a change to the seed set, not a click path — see
[Seeding an instance](environment.md#seeding-an-instance).

The records the seed writes reference **files**, and those cannot live in the
instance: `core-*/public/` is git-ignored. They are committed in the seed
package below `packages-dev/dev-site/Resources/Public/SeedFiles/`, drawn by
`Build/Scripts/generateSeedFiles.php`, and copied into `fileadmin/` by
`config/system/additional.php` on the same first request that installs the
database template — so a fresh clone gets the database and the files it points
at together. See
[Seed files, and how they reach an instance](environment.md#seed-files-and-how-they-reach-an-instance).

## Database backup and restore

The instance database is git-ignored (`core-*/var/`); the template next to it is
committed. Five composer scripts move state around, all run from inside the
instance directory:

| Script                         | Does                                                                        |
|--------------------------------|-----------------------------------------------------------------------------|
| `ddev composer sqlite:backup`  | instance → `sqlite-databases/core-NN.sqlite`, the file that is committed    |
| `ddev composer sqlite:apply`   | template → instance, discarding its database, and clears the rebuild marker |
| `ddev composer instance:fresh` | drops the database and suppresses the automatic seeding                     |
| `ddev composer instance:seed`  | writes the seed definition into an **empty** page tree                      |
| `ddev composer system:refresh` | flush and warm caches, update languages, run `extension:setup`              |

`sqlite:backup` rewrites a multi-megabyte binary that git cannot delta compress,
so commit it when the content genuinely changed, not on every run. Both
directions go through `Build/Scripts/sqliteSnapshot.php` rather than `cp`,
because a running instance keeps its newest writes in a write ahead log that a
plain copy leaves behind —
[Snapshotting an instance database is not a copy](environment.md#snapshotting-an-instance-database-is-not-a-copy).

Rebuilding one from nothing, including the exact `typo3 setup` invocation and
the two things it leaves behind, is
[Rebuilding an instance from nothing](environment.md#rebuilding-an-instance-from-nothing).

A committed template that no longer matches the seed definition next to it is
what `SnapshotManifestTest` reports — the two used to drift apart in silence.
Change the seed and the snapshot in the same change, and regenerate the manifest
with them: [Seed verification](../testing/seed-verification.md).

## See also

- [Development environment](environment.md) — the harness, and the seeding and
  rebuild procedures these instances use.
- [Monorepo layout](monorepo-layout.md) — where the instances and the seed
  package sit in the repository.
- [Validation settings](../architecture/validation-settings.md) — why the name
  fields of a profile are read only in the editing form.
- [Seed verification](../testing/seed-verification.md) — the checks that keep the
  seed definition, the committed snapshots and the manifest in agreement.
