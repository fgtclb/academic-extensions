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

### Backend editor

The seed writes a second backend account, an editor, so that the backend can be
looked at the way an editor sees it rather than the way an administrator does:

|          |                                                                        |
|----------|------------------------------------------------------------------------|
| Username | `erika-editor`                                                         |
| Password | `Erika-Editor-1701D.`                                                  |
| Group    | `Academic editors` (`be_groups` uid 10)                                |
| Mounts   | `/`, the storage container, `/legacy/` and the `Seed files` file mount |

The group is the point, not the user: it allows every academic content element,
table and page type, and the columns the academic extensions mark `exclude`,
plus three core columns a plugin or a page type cannot do without:
`tt_content:pages` and `tt_content:recursive`, the record storage of the plugins,
and `pages:doktype`, which turns a page into a programme, project or partner
page.

Three defaults of the core would otherwise lock the account out, and the seed
sets each of them explicitly:

- `be_users.disable` defaults to `1`, so the user is written with `disable: 0`.
- `be_groups.workspace_perms` defaults to `0` in the core TCA, and
  EXT:workspaces, which `fgtclb/academics-monorepo-shared` requires, sets the
  default of `be_users.workspace_perms` to `0` as well. Without access to the
  live workspace the login ends in a `NoAccessibleModuleException`, so the
  group carries `workspace_perms: 1`.
- The file list module is `media_management` on v13 and v14, not
  `file_filelist`, and `groupMods` names that identifier.

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
| Storage  | the `Data · frontend users` folder, page uid 170            |
| Profile  | `Jane Doe`, `tx_academicpersons_domain_model_profile` uid 1 |

To use it:

1. open `/login`, which carries the `felogin_login` plugin — its
   `settings.pages` names page 170, the folder the user record sits on, and a
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

Two more of the fourteen seeded profiles are connected, `Erik Mustermann` to
`erik.mustermann` and `Liam Rhodes` to `liam.rhodes`. `sam.tester` has **no**
profile. That is not an oversight either: it is what the "logged in user
without a profile" case looks like, and the plugin renders its empty state for
it.

## What the instances contain

The page tree is written from the seed set
`packages-dev/dev-site/Configuration/DataFactory/academics-instance/`, one
section per extension and one page per plugin. Every page and record has a
German translation below `/de/`:

| Page                    | What is on it                                                                                                                                                                                                                                                                                                                                                                                |
|-------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `/data/*`               | Storage folders: persons (organisational units and function types with their slugs, profiles, contracts and their children), external persons, jobs, partner roles, contact roles, study plan categories, `sys_category` records with a `category_types` type (two of them parents of a degree), frontend users and groups                                                                   |
| `/persons/*`            | One page per plugin of `EXT:academic_persons`. The list offers both visitor filters, the view mode switch, pagination, only valid contracts and the function type of each contract. The letter navigation lets the active letter reset. The filtered list shows only the contracts that match its filter, list and detail offers the unit filter, and the cards show the first contract only |
| `/login`, `/my-profile` | `EXT:felogin` and the editing form of `EXT:academic_persons_edit`                                                                                                                                                                                                                                                                                                                            |
| `/contacts/*`           | The contacts list of `EXT:academic_contacts4pages`, grouped by role, and a second one with hidden contacts and without the grouping                                                                                                                                                                                                                                                          |
| `/jobs/*`               | The job lists (the full one paginated), the detail page, the new job form and its confirmation, and the b-ite list of `EXT:academic_bite_jobs`                                                                                                                                                                                                                                               |
| `/study-plan`           | A study plan with a collapsible filter, decimal credit points, and the frame and spacing of the Appearance tab                                                                                                                                                                                                                                                                               |
| `/programs/*`           | The programme list (four filters shown, subcategories included, active filter tags, reset link and result count, filter and sorting in the path), a preset list, the programme finder, and six programme pages (`doktype: 20`) with their facts, the back link to the list and application links                                                                                             |
| `/projects/*`           | The project lists, with active filter tags, reset link and result count, filter, state and sorting in the path, and the active state badge on the full one, and six project pages (`doktype: 30`), the accessible learning platform with a subtitle and a link in its text                                                                                                                   |
| `/partners/*`           | The partner list (paginated, with active filter tags, reset link and result count, filter, sorting and page in the path), a preset list, the map, the partnerships plugins, and six partner pages (`doktype: 40`), the TYPO3 Association with a subtitle                                                                                                                                     |
| `/draft`, `/members`    | A hidden page and a page for a frontend user group                                                                                                                                                                                                                                                                                                                                           |
| `/icons`                | Every icon the academic extensions register, in both icon registries, see [below](#the-icon-overview-page)                                                                                                                                                                                                                                                                                   |
| `/legacy/*`             | The same tree again, delivered by a `sys_template` record instead of site sets                                                                                                                                                                                                                                                                                                               |

Three pages of that tree have **nothing to show by design** and are therefore
hidden from the menus (`nav_hide`): `/persons/detail`, `/persons/detail-hidden`
and `/jobs/detail`, in both trees and both languages. Each carries a detail
plugin, and without its argument each of them answers 404 on TYPO3 v13 and v14.
The pages exist so the plugins have a home when a detail URL is built for them
from a list.

`/persons/detail-hidden` is the detail page of the "selected profiles" element
that lists the two hidden profiles: its detail element shows hidden records as
well, so the links of that element lead to a profile rather than to a 404. The
links keep their query arguments, because the slug aspect of the route does not
resolve a hidden profile, and the route enhancers of the site do not name the
page.

Changing that content is a change to the seed set, not a click path — see
[Seeding an instance](environment.md#seeding-an-instance).

### The icon overview page

`/icons` (German `/de/symbole`, and both again below `/legacy/`) carries one
content element of the seed package, `academicsdevsite_icons`. It lists every
icon of the academic extensions as the two icon registries hold them when the
page renders, one section per registry:

| Section  | Registry                                                                         | Rendered with                                              |
|----------|----------------------------------------------------------------------------------|------------------------------------------------------------|
| Frontend | the frontend icon registry of `academic_base`, `Configuration/FrontendIcons.php` | `<ab:icon … alternativeMarkupIdentifier="inline" />`       |
| Backend  | the icon registry of TYPO3, `Configuration/Icons.php`                            | `<core:icon … />` in its default markup, the backend's own |

Both sections hold the `tx-academic*` identifiers, grouped by extension prefix
and group, and the `category_types.*` and `category_types_group.*` identifiers of
the category types, grouped by category type group. Each icon is a tile with its
identifier and its registry, rendered at 1em in a line of text, at 2rem and on a
dark ground. A tile says when the other registry holds the identifier as well,
which by the rule in [Icons](../architecture/icons.md) is true for the category
type and group icons only.

| Instance  | `/` tree                                      | `/legacy/` tree                                      |
|-----------|-----------------------------------------------|------------------------------------------------------|
| `core-13` | <https://core13-academics-v3.ddev.site/icons> | <https://core13-academics-v3.ddev.site/legacy/icons> |
| `core-14` | <https://core14-academics-v3.ddev.site/icons> | <https://core14-academics-v3.ddev.site/legacy/icons> |

Nothing on it is configured. An icon an extension adds or renames is on the page
after a cache flush, without a change to the seed. A tile showing the
`default-not-found` placeholder, an icon in the wrong section, or one that stays
dark on the dark ground is the defect to look for. The rendering definition
comes from the `ext_localconf.php` of the seed package rather than from a set,
so it reaches both trees without a change to the site configurations, see
[TypoScript and site sets](../architecture/typoscript-and-site-sets.md).

Above the two registry sections, a section "Frontend icon API" demonstrates the
frontend icon API of `academic_base` (ACE-595) with the module
`Resources/Private/TypeScript/frontend/icon-demo.ts` of the seed package, in
the browser only. One list is filled from a JSON icon map on the page, without
a request. The other one is filled from the icon endpoint in a single request,
and the core icon `actions-add` in it is left out and shown as "not
available", because only the backend registry knows it. Each slot says what
happened in `data-icon-demo-state`, `rendered` or `failed`. The network panel
shows exactly one request to `_academic/icons.json`, answered with
`Cache-Control: public, max-age=31536000, immutable` and without
`Set-Cookie`, see [Icons](../architecture/icons.md#icons-for-frontend-javascript).

The records the seed writes reference **files**, and those cannot live in the
instance: `core-*/public/` is git-ignored. They are committed in the seed
package below `packages-dev/dev-site/Resources/Public/SeedFiles/`, drawn by
`Build/Scripts/generateSeedFiles.php`, and copied into `fileadmin/` by
`config/system/additional.php` on the same first request that installs the
database template — so a fresh clone gets the database and the files it points
at together. See
[Seed files, and how they reach an instance](environment.md#seed-files-and-how-they-reach-an-instance).

## The stylesheet of the instances

The academic extensions ship no stylesheet, and the instances bring one of
their own, as a site package would: `academic-extensions.css` of
`packages-dev/dev-site`, compiled from one SCSS partial per extension, two for
the persons. It is linked on every page of both trees, in both instances:

- the `/` tree gets it from the set `fgtclb/academics-dev-site-stylesheet`,
  which `core-*/config/sites/academics/config.yaml` names last, after the theme;
- the `/legacy/` tree gets it from the static template of `academics_dev_site`,
  which its root `sys_template` record already includes.

It is what makes the study plan, the partner map, the profiles and the profile
editor of the instances look and work as intended: without it the map has no
height, the semester and fold-out headers show both of their glyphs, regions
the profile editor hides stay on screen and its image cropper cannot be
dragged. A change to it is a
change to the SCSS, followed by `buildJs` and a commit of the compiled file.

To check that it reaches a page, look for it in the source, and fetch it:

```bash
cd core-13
curl -sk "$(ddev describe -j | jq -r '.raw.primary_url')/study-plan" \
    | grep -o 'href="[^"]*academic-extensions\.css[^"]*"'
```

The layout of the sources, the delivery and the test that asserts it are
described in
[Frontend assets](frontend-assets.md#the-stylesheet-of-the-development-instances).

## Mail and site constants

Both instances send mail over SMTP to `127.0.0.1:1025`
(`config/system/settings.php`), which is the Mailpit that DDEV runs inside the
web container. `ddev launch -m` opens its inbox. The web container of DDEV has
no `/usr/sbin/sendmail`, so the `sendmail` transport `typo3 setup` writes fails
on the first mail, the notification of the job form among them. An instance
served by a host stack instead puts a different transport into a git-ignored
`config/system/additional/*.php`.

The `/` tree is delivered by site sets, and one more file belongs to that
delivery: `config/sites/academics/constants.typoscript`. The two theme switches
`page.theme.googleFont.enable` and `page.theme.cookieconsent.enable` are site
settings of the type `bool`, and TYPO3 v13 and v14 write a disabled one into the
constants as the empty string. The theme reads the second one in the condition
`[{$page.theme.cookieconsent.enable} == 1]`, which then reads `[ == 1]`, an
expression that is no valid condition. TYPO3 v14 logs it as an error on every
uncached page, and the backend module "Active TypoScript" shows it on both
versions. The site's own `constants.typoscript` is read
after the site settings and states both as `0`. `settings.yaml` keeps them,
because the backend and PHP code read the settings rather than the constants.

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
- [Icons](../architecture/icons.md) — the rules the icons on `/icons` follow.
- [Seed verification](../testing/seed-verification.md) — the checks that keep the
  seed definition, the committed snapshots and the manifest in agreement.
- [Frontend assets](frontend-assets.md#the-stylesheet-of-the-development-instances)
  — the stylesheet of the instances, its sources and its delivery.
