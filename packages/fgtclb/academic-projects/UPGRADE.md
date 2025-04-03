# Upgrade 2.0

## BREAKING: Removed methods from `\FGTCLB\AcademicProjects\Domain\Model\Dto\ProjectDemand`

Following class methods has been removed:

* `ProfileDemand::getHideCompletedProjects()`
* `ProfileDemand::setHideCompletedProjects()`

Custom usage should usually not exit in consuming projects, but in some case it may be possible that
the `ProjectsRepository` has been used for custom project code implementation or plugins, using the
demand based method to retrieve data. In these cases, the new methods:

* `ProfileDemand::setActiveState(string $activeState): void`
* `ProfileDemand::getActiveState(): string`

can be used to adopt for corresponding code places. Take care of changed value types, as the new
setter expects string of valid PHP Enum `\FGTCLB\AcademicProjects\Domain\Model\Dto\ActiveState`
cases and the getter returns a string.

## BREAKING: Changed plugin FlexForm option and values

FlexForm options of following plugins has changed:

* `academicprojects_projectlist`
* `academicprojects_projectlistsingle`

Changed options:

* `settings.hideCompletedProjects` => `settings.activeState`
* `settings.filter.options` => `settings.hideFilter`
* `settings.sorting.options` => `settings.hideSorting`

In case of `settings.activeState` that also includes supported values for the field, which also matches possible
values of newly added PHP Enum `\FGTCLB\AcademicProjects\Domain\Model\Dto\ActiveState`:

| flexform  | Enum                   | old value            |
|-----------|------------------------|----------------------|
| all       | ActiveState::ALL       | any value except `1` |
| active    | ActiveState::ACTIVE    | 1                    |
| completed | ActiveState::COMPLETED | no old value         |

> [!NOTE]
> An TYPO3 UpgradeWizard `academicProjects_flexFormUpgradeWizard` is provided to migrate old
> plugin FlexForm options and values to the new settings and values, in case of `activeState`
> also transforming the value based on mapping mentioned in the table above.

> [!IMPORTANT]
> The upgrade wizard should be executed after the plugin `list_type` to `CType` upgrade wizard
> has been executed and before lowlevel flexform data cleanup (v13) has been executed.

> [!WARNING]
> Note that renamed option has an impact in the fluid templates, and projects overriding the
> default shipped templates and partials requires to adopt changes in the overridden templates.

## BREAKING: Plugin changed from `list_type` to `CType`

TYPO3 v13 deprecated the `tt_content` sub-type feature, only used for `CType=list` sub-typing also known
as `list_type` and mostly used based on old times for extbase based plugins. It has been possible since
the very beginning to register Extbase Plugins directly as `CType` instead of `CType=list` sub-type, which
has now done for `2.0.0`.

Technically this is a breaking change, and instances upgrading from `1.x` version of the plugin needs to
update corresponding `tt_content` records in the database and eventually adopt addition, adjustments or
overrides requiring to use the correct CType.

> [!NOTE]
> An TYPO3 UpgradeWizard `academicProjects_pluginUpgradeWizard` is provided to migrate
> plugins from `CType=list` to dedicated `CTypes` matching the new registration.

## BREAKING: Switch to `EXT:category_types 2.0`

Category type based handling has been streamlined and centralized within the `EXT:category_types` extension
and the known implementation based on deprecated TYPO3 Enumeration has been replaced with a modern PHP API
provided by the `EXT:category_type` extension.

Extension specific category types are now grouped and are now defined by newly introduced yaml file format,
following a concrete convention to look and auto-register these files.

`EXT:academic_projects` now ships a default set of project related category types, which can be found
in [Configuration/CategoryTypes.yaml](./Configuration/CategoryTypes.yaml).

`EXT:academic_projects` related category types can be extended by any other TYPO3 extension providing a
`Configuration/CategoryTypes.yaml` file containing category-types using the group-identifier `projects`.

`Configuration/CategoryTypes.yaml` format uses following syntax:

```yaml
types:
  - identifier: <unique_identifier>
    title: '<type-translation-lable-using-full-LLL-syntax'
    group: projects
    icon: '<icon-file-using-EXT-syntax-needs-to-be-an-as-svg>'
```

> [!IMPORTANT]
> TYPO3 Enumeration based classes has been removed from the extension codebase
> and is considerable breaking, allowed to be done for a major version upgrade.
> Please adopt accordingly to the new handling.
