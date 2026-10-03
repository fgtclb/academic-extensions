## Purpose

Defines which icon file a category type or a category type group shows in the
backend and in the frontend, whether it follows the text colour there, and how
an extension, an override in `CategoryTypes.yaml` or a site package changes
that.

## ADDED Requirements

### Requirement: Type icons are available in the backend and the frontend

The icon of every declared category type SHALL be available under the icon
identifier `category_types.<group>.<type>` in the backend, where the type
select, the record icons and the page module category summary show it, and to
frontend templates that render icons from the frontend icon registry of
`academic_base`. The identifier SHALL be the same in both places. This SHALL
apply on TYPO3 v13 and v14.

#### Scenario: A shipped type in the frontend

- **WHEN** a frontend template renders the icon
  `category_types.programs.degree` from the frontend icon registry
- **THEN** the icon declared for the type `degree` of the group `programs` is
  shown

#### Scenario: A type a site package declares

- **WHEN** a site package declares the type `topic` in the group `news` with
  an icon and the caches are flushed
- **THEN** `category_types.news.topic` is shown in the type select of a
  category and is available to frontend templates

### Requirement: The frontend can show its own icon file

A type or group that declares `frontendIcon` SHALL show that file in the
frontend and the `icon` file in the backend. A type or group without
`frontendIcon` SHALL show the `icon` file in both places.

#### Scenario: A dedicated frontend file

- **WHEN** a type declares `icon: Degree.svg` and `frontendIcon:
  DegreeFrontend.svg`
- **THEN** the backend shows `Degree.svg`
- **AND** the frontend shows `DegreeFrontend.svg`

#### Scenario: No frontend file

- **WHEN** a type declares only `icon: Degree.svg`
- **THEN** the backend and the frontend both show `Degree.svg`

### Requirement: Inlining follows the file it is declared for

In the frontend, an SVG icon SHALL be inlined into the page, so that it follows
the colour of the surrounding text, when `frontendInlineIcon` is true, and
SHALL be shown as an image when it is false. When `frontendInlineIcon` is not
declared, the frontend SHALL follow `inlineIcon` while it shows the `icon`
file, and SHALL show a declared `frontendIcon` as an image. The backend SHALL
keep following `inlineIcon` alone. A bitmap file SHALL be shown as an image
whatever the flags say.

#### Scenario: One file, inlined everywhere

- **WHEN** a type declares `icon: Degree.svg` and `inlineIcon: true`
- **THEN** the icon is inlined in the backend and in the frontend

#### Scenario: A frontend file without its own flag

- **WHEN** a type declares `icon: Degree.svg`, `inlineIcon: true` and
  `frontendIcon: DegreeFrontend.svg`
- **THEN** the backend inlines `Degree.svg`
- **AND** the frontend shows `DegreeFrontend.svg` as an image

#### Scenario: A frontend file with its own flag

- **WHEN** a type declares `frontendIcon: DegreeFrontend.svg` and
  `frontendInlineIcon: true`
- **THEN** the frontend inlines `DegreeFrontend.svg`

#### Scenario: The same file, inlined only in the backend

- **WHEN** a type declares `icon: Degree.svg`, `inlineIcon: true` and
  `frontendInlineIcon: false`
- **THEN** the backend inlines `Degree.svg`
- **AND** the frontend shows `Degree.svg` as an image

#### Scenario: A bitmap

- **WHEN** a type declares `frontendIcon: Degree.png` and
  `frontendInlineIcon: true`
- **THEN** the frontend shows `Degree.png` as an image

### Requirement: An override keeps a file and its inline flag together

When a package changes a type with `useExisting` and names a new
`frontendIcon` without `frontendInlineIcon`, the new file SHALL be shown in the
frontend as an image rather than inherit the flag the earlier declaration set
for its own frontend file. An override that names a new `icon` without
`inlineIcon` SHALL keep the earlier `inlineIcon`, as it does today, in the
backend and in the frontend while the frontend shows `icon`. An override that names a flag without a file SHALL apply the flag
to the file already declared, and an override that names neither SHALL keep
both. An override of `icon` SHALL NOT change a declared `frontendIcon`.

#### Scenario: A project replaces the icon of an inlined type

- **WHEN** a type is declared with `icon: Degree.svg` and `inlineIcon: true`
- **AND** a project overrides it with `useExisting` and `icon:
  ProjectDegree.svg` only
- **THEN** the backend and the frontend inline `ProjectDegree.svg`, as they
  did before this change

#### Scenario: A project replaces the icon and asks for inlining

- **WHEN** a project overrides a type with `useExisting`, `icon:
  ProjectDegree.svg` and `inlineIcon: true`
- **THEN** the backend and the frontend inline `ProjectDegree.svg`

#### Scenario: A project replaces the frontend file only

- **WHEN** a type is declared with `frontendIcon: DegreeFrontend.svg` and
  `frontendInlineIcon: true`
- **AND** a project overrides it with `useExisting` and `frontendIcon:
  ProjectDegree.svg` only
- **THEN** the frontend shows `ProjectDegree.svg` as an image
- **AND** the backend is unchanged

#### Scenario: A project changes only the priority

- **WHEN** a type is declared with `icon: Degree.svg` and `inlineIcon: true`
- **AND** a project overrides it with `useExisting` and a priority only
- **THEN** the backend and the frontend still inline `Degree.svg`

#### Scenario: A project replaces the icon of a type with a frontend file

- **WHEN** a type is declared with `icon: Degree.svg` and `frontendIcon:
  DegreeFrontend.svg`
- **AND** a project overrides it with `useExisting` and `icon:
  ProjectDegree.svg`
- **THEN** the backend shows `ProjectDegree.svg`
- **AND** the frontend still shows `DegreeFrontend.svg`

### Requirement: A site package can replace a category type icon for the frontend

An icon a site package registers in its `Configuration/FrontendIcons.php`
under the identifier of a category type or of a category type group SHALL
replace that icon in the frontend, whatever the `CategoryTypes.yaml` files
declare, and SHALL leave the backend icon unchanged.

#### Scenario: A frontend only replacement

- **WHEN** a site package registers `category_types.programs.degree` in its
  `Configuration/FrontendIcons.php` and the caches are flushed
- **THEN** frontend templates show the site package's icon for that type
- **AND** the type select of a category still shows the declared icon

### Requirement: A type without an icon file has no frontend icon

A type or group that declares neither `icon` nor `frontendIcon` SHALL NOT
provide a frontend icon. Rendering its identifier in the frontend SHALL show
what the frontend shows for any icon it does not know, and SHALL NOT fail.

#### Scenario: A type declared without an icon

- **WHEN** a type is declared without `icon` and without `frontendIcon`
- **AND** a frontend template renders its icon identifier from the frontend
  icon registry
- **THEN** the page renders, with the icon shown for an unknown identifier
