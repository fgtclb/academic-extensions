# TYPO3 Extension `Academic Bite Jobs` (READ-ONLY)

|                  | URL                                                                     |
|------------------|-------------------------------------------------------------------------|
| **Repository:**  | https://github.com/fgtclb/academic-bite-jobs                            |
| **Read online:** | https://docs.typo3.org/p/fgtclb/academic/academic-bite-jobs/main/en-us/ |
| **TER:**         | https://extensions.typo3.org/extension/academic_bite_jobs/              |

## Description

This extension provides a basic structure for academic B-ite jobs.

As part of the bundling of requirements for the FGTCLB Academic Extensions, the TYPO3 extension "Academic Jobs b-ite"
was created, which only requires the b-ite key to seamlessly display job adverts from the b-ite platform on a TYPO3
website. The developed plug-in currently supports different categorisations, which are read from b-ite, e.g. appointment
procedures, academic staff, non-scientific staff, training positions. List view, tile view and table view are currently
available as display modes. There are also options for grouping and sorting as well as a limit for the number of job
adverts to be displayed.

> [!NOTE]
> This extension is currently in beta state - please notice that there might be changes to the structure

## Compatibility

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

## Installation

Install with your flavour:

* [TER](https://extensions.typo3.org/extension/academic_bite_jobs/)
* Extension Manager
* composer

We prefer composer installation:
```bash
composer req fgtclbfgtclb/academic-bite-jobs
```

## Credits

This extension was created the [FGTCLB GmbH](https://www.fgtclb.com/).

[Find more TYPO3 extensions we have developed](https://github.com/fgtclb/).
