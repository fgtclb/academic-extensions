# TYPO3 Academic Extensions (development)

## Description

`academic-extensions` is a mono repository to develop a couple of academic related TYPO3 extensions,
which may depend on others. To keep the maintenance burden across the set of extension small while
increasing the cross-over development and testing experience.

## Repository version support

| Branch | Version       | TYPO3     | PHP                                          |
|--------|---------------|-----------|----------------------------------------------|
| main   | ^3, 3.x-dev   | v13 + v14 | 8.2, 8.3, 8.4, 8.5                           |
| 2, 2.x | ^2, 2.x-dev   | v12 + v13 | 8.1, 8.2, 8.3, 8.4, 8.5 (depending on TYPO3) |
| 1      | ^1, 1.x-dev   | v11 + v12 | 8.1, 8.2, 8.3, 8.4 (depending on TYPO3)      |

**Testing 2.x.x extension version in projects (composer mode)**

It is already possible to use and test the `2.x` version in composer based instances,
which is encouraged and feedback of issues not detected by us (or pull-requests).

Your project should configure `minimum-stabilty: dev` and `prefer-stable` to allow
requiring each extension but still use stable versions over development versions:

```shell
composer config minimum-stability "dev" \
&& composer config "prefer-stable" true
```

and than for example:

```shell
composer require 'fgtclb/academic-persons':'2.*.*@dev'
```

That way, current main branch will be included and updated and as soon as 2.0.0 is released switcht to the release on
update.

## Upgrade from `1.x`

Upgrading from `1.x` to `2.x` includes breaking changes, which needs to be
addressed manualy in case not automatic upgrade path is available. See the
`UPGRADE.md` file of each extension for details.

### Extension Version Support Matrix

| Extension               | v11 | v12     | v13     | v14 |
|-------------------------|-----|---------|---------|-----|
| academic_base           | -   | <2>     | <2> (3) | (3) |
| academic_bite_jobs      | <1> | <1> <2> | <2> (3) | (3) |
| academic_contacts4pages | <1> | <1> <2> | <2> (3) | (3) |
| academic_study_plan     | -   | <2>     | <2> (3) | (3) |
| academic_jobs           | <1> | <1> <2> | <2> (3) | (3) |
| academic_partners       | <1> | <1> <2> | <2> (3) | (3) |
| academic_persons        | <1> | <1> <2> | <2> (3) | (3) |
| academic_persons_edit   | <1> | <1> <2> | <2> (3) | (3) |
| academic_persons_sync   | <1> | <1> <2> | <2> (3) | (3) |
| academic_programs       | <1> | <1> <2> | <2> (3) | (3) |
| academic_projects       | <1> | <1> <2> | <2> (3) | (3) |
| category_types          | <1> | <1> <2> | <2> (3) | (3) |

Legend:

```
  <X>   Allowed and used with X.y.z
  {X}   Allowed but not tested/verified with X.y.z, but may/could work
  -X-   Allowed but absolutely not tested and most likely not working (yet)
  (X)   Planned for the upcoming X.y.z line, not yet available/tested
```

## List of TYPO3 extension and the split repositories (READ ONLY)

| Composer                       | TYPO3                   | Path                                                                                       | Split Repository                                                                     |
|--------------------------------|-------------------------|--------------------------------------------------------------------------------------------|--------------------------------------------------------------------------------------|
| fgtclb/academic-base           | academic_base           | [packages/fgtclb/academic-base](packages/fgtclb/academic-base/README.md)                   | [fgtclb/academic-base](https://github.com/fgtclb/academic-base)                      |
| fgtclb/academic-bite-jobs      | academic_bite_jobs      | [packages/fgtclb/academic-bite-jobs](packages/fgtclb/academic-bite-jobs/README.md)         | [fgtclb/academic-bite-jobs](https://github.com/fgtclb/academic-bite-jobs)            |
| fgtclb/academic-contacts4pages | academic_contacts4pages | [packages/fgtclb/academic-contact4pages](packages/fgtclb/academic-contact4pages/README.md) | [fgtclb/academic-contact4pages](https://github.com/fgtclb/academic-contact4pages)    |
| fgtclb/academic-study-plan     | academic_study_plan     | [packages/fgtclb/academic-study-plan](packages/fgtclb/academic-study-plan/README.md)       | [fgtclb/academic-study-plan](https://github.com/fgtclb/academic-study-plan)          |
| fgtclb/academic-jobs           | academic_jobs           | [packages/fgtclb/academic-jobs](packages/fgtclb/academic-jobs/README.md)                   | [fgtclb/academic-jobs](https://github.com/fgtclb/academic-jobs)                      |
| fgtclb/academic-partners       | academic_partners       | [packages/fgtclb/academic-partners](packages/fgtclb/academic-partners/README.md)           | [fgtclb/academic-partners](https://github.com/fgtclb/academic-partners)              |
| fgtclb/academic-persons        | academic_persons        | [packages/fgtclb/academic-persons](packages/fgtclb/academic-persons/README.md)             | [fgtclb/academic-persons](https://github.com/fgtclb/academic-persons)                |
| fgtclb/academic-persons-edit   | academic_persons_edit   | [packages/fgtclb/academic-persons-edit](packages/fgtclb/academic-persons-edit/README.md)   | [fgtclb/academic-persons-edit](https://github.com/fgtclb/academic-persons-edit)      |
| fgtclb/academic-persons-sync   | academic_persons_sync   | [packages/fgtclb/academic-persons-sync](packages/fgtclb/academic-persons-sync/README.md)   | [fgtclb/academic-persons-sync](https://github.com/fgtclb/academic-persons-sync)      |
| fgtclb/academic-programs       | academic_programs       | [packages/fgtclb/academic-programs](packages/fgtclb/academic-programs/README.md)           | [fgtclb/academic-programs](https://github.com/fgtclb/academic-programs)              |
| fgtclb/academic-projects       | academic_projects       | [packages/fgtclb/academic-projects](packages/fgtclb/academic-projects/README.md)           | [fgtclb/academic-projects](https://github.com/fgtclb/academic-projects)              |
| fgtclb/category-types          | category_types          | [packages/fgtclb/typo3-category-types](packages/fgtclb/typo3-category-types/README.md)     | [fgtclb/fgtclb/typo3-category-types](https://github.com/fgtclb/typo3-category-types) |
