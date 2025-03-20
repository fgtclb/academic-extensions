# TYPO3 academic extension collection (mono repository)

## Description

`academic-extensions` is a mono repository to develop a couple of academic related TYPO3 extensions,
which may depend on others. To keep the maintenance burden across the set of extension small while
increasing the cross-over development and testing experience.

## Repository version support

| Branch | Version   | TYPO3       | PHP                                     |
|--------|-----------|-------------|-----------------------------------------|
| main   | 2.0.x-dev | ~v12 + ~v13 | 8.1, 8.2, 8.3, 8.4 (depending on TYPO3) |
| 1      | 1.2.x-dev | v11 + ~v12  | 8.1, 8.2, 8.3, 8.4 (depending on TYPO3) |

> [!IMPORTANT]
> The 2.x TYPO3 v12 and v13 support is not guaranteed over all extensions
> yet and will most likely not get it. It has only been allowed to install
> all of them with 1.x also in a TYPO3 v12 to combining them in the mono
> repository.
> Support in work and at least planned to be archived when releasing `2.0.0`.

### Extension Version Support Matrix

| Extension               | v11  | v12     | v13     | Comment                                                                      |
|-------------------------|------|---------|---------|------------------------------------------------------------------------------|
| academic_bite_jobs      | -1-  | -1- -2- | -1- -2- | Broken - API key required but not configurable, not checked/adopted for v12+ |
| academic_contacts4pages | <1>  | -1- -2- | -1- -2- | 1 in use for v11, may work for v12 but not tested. Same with 2 for v12/v13   |
| academic_jobs           | <1>  | -1- -2- | -1- -2- |                                                                              |
| academic_partners       | -1-  | -1- {2} | -1- {2} | Breaking for 2 pending/needs to be adopted into mono-repo                    |
| academic_persons        | <1>  | {1} {2} | -1- {2} |                                                                              |
| academic_persons_edit   | <1>  | {1} {2} | -1- {2} |                                                                              |
| academic_persons_sync   | <1>  | {1} {2} | -1- {2} |                                                                              |
| academic_programs       | <1>  | {1} {2} | -1- {2} | Breaking for 2 pending/needs to be adopted into mono-repo                    |
| academic_projects       | <1>  | {1} {2} | -1- {2} |                                                                              |
| category_types          | <1>  | {1} {2} | -1- {2} | Breaking for 2 pending/needs to be adopted into mono-repo                    |

Legend:

```
  <X>   Allowed and used with X.y.z
  {X}   Allowd but not tested/verified with X.y.z, but may/could work
  -X-   Allowed but absolutly not tested and most likely not working (yet)
```

## List of TYPO3 extension and the split repositories (READ ONLY)

| Composer                       | TYPO3                   | Path                                                                                       | Split Repository                                                                  |
|--------------------------------|-------------------------|--------------------------------------------------------------------------------------------|-----------------------------------------------------------------------------------|
| fgtclb/academic-bite-jobs      | academic_bite_jobs      | [packages/fgtclb/academic-bite-jobs](packages/fgtclb/academic-bite-jobs/README.md)         | [fgtclb/academic-bite-jobs](https://github.com/fgtclb/academic-bite-jobs)         |
| fgtclb/academic-contacts4pages | academic_contacts4pages | [packages/fgtclb/academic-contact4pages](packages/fgtclb/academic-contact4pages/README.md) | [fgtclb/academic-contact4pages](https://github.com/fgtclb/academic-contact4pages) |
| fgtclb/academic-jobs           | academic_jobs           | [packages/fgtclb/academic-jobs](packages/fgtclb/academic-jobs/README.md)                   | [fgtclb/academic-jobs](https://github.com/fgtclb/academic-jobs)                   |
| fgtclb/academic-partners       | academic_partners       | [packages/fgtclb/academic-partners](packages/fgtclb/academic-partners/README.md)           | [fgtclb/academic-partners](https://github.com/fgtclb/academic-partners)           |
| fgtclb/academic-persons        | academic_persons        | [packages/fgtclb/academic-persons](packages/fgtclb/academic-persons/README.md)             | [fgtclb/academic-persons](https://github.com/fgtclb/academic-persons)             |
| fgtclb/academic-persons-edit   | academic_persons_edit   | [packages/fgtclb/academic-persons-edit](packages/fgtclb/academic-persons-edit/README.md)   | [fgtclb/academic-persons-edit](https://github.com/fgtclb/academic-persons-edit)   |
| fgtclb/academic-persons-sync   | academic_persons_sync   | [packages/fgtclb/academic-persons-sync](packages/fgtclb/academic-persons-sync/README.md)   | [fgtclb/academic-persons-sync](https://github.com/fgtclb/academic-persons-sync)   |
| fgtclb/academic-programs       | academic_programs       | [packages/fgtclb/academic-programs](packages/fgtclb/academic-programs/README.md)           | [fgtclb/academic-bite-jogs](https://github.com/fgtclb/academic-programs)          |
| fgtclb/academic-projects       | academic_projects       | [packages/fgtclb/academic-projects](packages/fgtclb/academic-projects/README.md)           | [fgtclb/academic-bite-jogs](https://github.com/fgtclb/academic-projects)          |
| fgtclb/category-types          | category_types          | [packages/fgtclb/typo3-category-types](packages/fgtclb/typo3-category-types/README.md)     | [fgtclb/academic-bite-jogs](https://github.com/fgtclb/typo3-category-types)       |
