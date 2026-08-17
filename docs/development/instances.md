# Development instances

Two ready-to-start TYPO3 instances live at the repository root, one per supported
core version. They exist to look at the extensions in a running backend and
frontend — they are **not** part of any test run, and `runTests.sh` never touches
them (see [Development environment](environment.md#development-instances-are-not-part-of-the-harness)).

| Folder     | TYPO3 | DDEV project          | Theme               | Frontend                                |
|------------|-------|-----------------------|---------------------|-----------------------------------------|
| `core-12/` | v12   | `core12-academics-v2` | `bootstrap_package` | <https://core12-academics-v2.ddev.site> |
| `core-13/` | v13   | `core13-academics-v2` | `bootstrap_package` | <https://core13-academics-v2.ddev.site> |

## Starting one

```shell
cd core-12
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
(`core13-academics-v2` on `2`, `core13-academics-v3` on `main`), and DDEV refuses
a second name for a known path:

```
Failed to start core13-academics-v2: this project root '…/core-13' already
contains a project named 'core13-academics-v3'.
```

`ddev stop --unlist core13-academics-v3` clears it; it removes the registration
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
- **The instance folder of the other version line** — `core-14/` here,
  `core-12/` on `main`. Switching removes its tracked files and leaves its
  ignored trees, so the folder stays behind as untracked noise.

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

The seed creates one frontend user, and it exists for a reason: **`EXT:academic_persons_edit`
cannot be looked at without it.** Its controller refuses every action when no
frontend user is logged in, and it finds the profile to edit through the
`frontend_users` relation of the profile record rather than through a storage
page — so a login *and* a connected profile are both required.

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

Each instance serves exactly one site, identifier `academics`, `rootPageId: 1`
and `base: /` (`core-12/config/sites/academics/config.yaml`,
`core-13/config/sites/academics/config.yaml`). The page tree behind that root
page is written from
`packages-dev/dev-site/Configuration/Seeds/Instance.yaml` — one section per
extension, one page per plugin:

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

One page of that tree answers **404 by design**: `/academic-persons/detail`
carries the detail plugin with no profile argument, and
`ProfileController::detailAction()` returns a page-not-found response when the
argument is absent
(`packages/fgtclb/academic-persons/Classes/Controller/ProfileController.php:243-251`).
The page exists so the plugin has a home when a detail URL is built for it from
`/academic-persons/list`; on its own it is supposed to be a 404, not a bug to
report.

Changing that content is a change to the definition, not a click path — see
[Seeding an instance](environment.md#seeding-an-instance).

## The TypoScript comes from a `sys_template` record

Both instances are wired through **one root `sys_template` record** the seed
writes — identifier `site-template`, uid 1, `root: 1`, `clear: 3` — whose
`include_static_file` lists `bootstrap_package`, the academic extensions and the
instance's own static template, and whose `constants` field carries the handful
of values a development instance has to correct.

Site sets would be the obvious mechanism today, and they are not available here:
they arrived in TYPO3 v13.1 and this branch also supports v12, where a site
configuration has no `dependencies` key and provides no TypoScript at all. Every
set the academic extensions ship is a plain `@import` of exactly the files that
record includes, so the older mechanism delivers identical TypoScript on both
versions — which is why neither site configuration has a `dependencies` list.

A site `settings.yaml` could not stand in for the constants either. On v12 a
site's settings are inserted at the position the record clears, that is *before*
its static includes, so every static template would overwrite them. The record's
own `constants` field is applied after its children and therefore wins.

What `packages-dev/dev-site` adds on top of the extensions' own TypoScript is
the glue a real installation would put in its site package: the page template
name for the two custom page types (`Configuration/TypoScript/`, registered as a
static template in `Configuration/TCA/Overrides/sys_template.php`) and the
backend layout of the `EXT:academic_partners` page type
(`Configuration/page.tsconfig`), which that extension imports only from its site
set and therefore not at all on TYPO3 v12.

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

## See also

- [Development environment](environment.md) — the harness, and the seeding and
  rebuild procedures these instances use.
- [Monorepo layout](monorepo-layout.md) — where the instances and the seed
  package sit in the repository.
- [Validation settings](../architecture/validation-settings.md) — why the name
  fields of a profile are read only in the editing form.
