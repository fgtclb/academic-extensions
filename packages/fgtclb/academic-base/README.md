# TYPO3 Extension `Academic Base` (READ-ONLY)

|                  | URL                                                   |
|------------------|-------------------------------------------------------|
| **Repository:**  | https://github.com/fgtclb/academic-base               |
| **Read online:** | -                                                     |
| **TER:**         | https://extensions.typo3.org/extension/academic_base/ |

## Description

This extension provides shared functionalities and configurations, which are
used by more than one of the academic extensions. Additionally, this extension
now acts as an anchor that academic extensions are always installed with the
same version even if not directly related to each other to avoid a to big shift
in projects and mitigates oversights in project dependency management.

> [!NOTE]
> This extension is currently in beta state - please notice that there might be changes to the structure

## Compatibility

| Branch | Version       | TYPO3     | PHP                                          |
|--------|---------------|-----------|----------------------------------------------|
| main   | ^3, 3.x-dev   | v13 + v14 | 8.2, 8.3, 8.4, 8.5                           |
| 2, 2.x | ^2, 2.x-dev   | v12 + v13 | 8.1, 8.2, 8.3, 8.4, 8.5 (depending on TYPO3) |
| 1      | -             | v11 + v12 | not available for 1.x                        |

## Installation

Install with your flavour:

* [TER](https://extensions.typo3.org/extension/academic_base/)
* Extension Manager
* composer

We prefer composer installation:

```bash
composer require 'fgtclb/academic-persons':'^2'
```

> [!IMPORTANT]
> `3.x.x` is still in development and not all academics extension are fully tested in v13,
> but can be installed in composer instances to use, test them. Testing and reporting are welcome.

**Testing 3.x.x extension version in projects (composer mode)**

It is already possible to use and test the `2.x` version in composer based instances,
which is encouraged and feedback of issues not detected by us (or pull-requests).

Your project should configure `minimum-stabilty: dev` and `prefer-stable` to allow
requiring each extension but still use stable versions over development versions:

```shell
composer config minimum-stability "dev" \
&& composer config "prefer-stable" true
```

and installed with:

```shell
composer require \
  'fgtclb/academic-persons':'3.*.*@dev'
```

## Credits

This extension was created by [FGTCLB GmbH](https://www.fgtclb.com/).

[Find more TYPO3 extensions we have developed](https://github.com/fgtclb/).
