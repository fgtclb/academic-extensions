# TYPO3 extension `academic_projects` (READ-ONLY)

|                  | URL                                                           |
|------------------|---------------------------------------------------------------|
| **Repository:**  | https://github.com/fgtclb/academic-projects                   |
| **Read online:** | https://docs.typo3.org/p/fgtclb/academic-projects/main/en-us/ |
| **TER:**         | https://extensions.typo3.org/extension/academic_projects/     |

## Description

TYPO3 extension for the presentation of projects and research projects of universities
with specifically structured data and typified system categories. List view in the form
of a tile display with filtering, which only provides positive filter results based on
system categories, prevents impossible filter combinations and thus always ensures a
positive user experience.

Examples of structured data: start and end year, areas of expertise, type of
cooperation, funding bodies, duration. In conjunction with the `Contact for Pages`
extension, the extension enables an active user journey and thus shows, for example,
project managers, research participants or cooperation partners.

> [!NOTE]
> This extension is currently in beta state - please notice that there might be changes to the structure

## Compatibility

| Branch | Version       | TYPO3       | PHP                                     |
|--------|---------------|-------------|-----------------------------------------|
| main   | 2.0.x-dev     | ~v12 + ~v13 | 8.1, 8.2, 8.3, 8.4 (depending on TYPO3) |
| 2      | ^2, 2.0.x-dev | ~v12 + ~v13 | 8.1, 8.2, 8.3, 8.4 (depending on TYPO3) |
| 1      | ^1, 1.2.x-dev | v11 + ~v12  | 8.1, 8.2, 8.3, 8.4 (depending on TYPO3) |

## Installation

Install with your flavour:

* [TER](https://extensions.typo3.org/extension/academic_projects/)
* Extension Manager
* composer

We prefer composer installation:

```bash
composer require \
  'fgtclb/category-types':'^2' \
  'fgtclb/academic-projects':'^2'
```

> [!IMPORTANT]
> `2.x.x` is still in development and not all academics extension are fully tested in v12 and v13,
> but can be installed in composer instances to use, test them. Testing and reporting are welcome.

**Testing 2.x.x extension version in projects (composer mode)**

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
  'fgtclb/category-types':'2.*.*@dev' \
  'fgtclb/academic-projects':'2.*.*@dev'
```

## Upgrade from `1.x`

Upgrading from `1.x` to `2.x` includes breaking changes, which needs to be
addressed manualy in case not automatic upgrade path is available. See the
[UPGRADE.md](./UPGRADE.md) file for details.

## Credits

This extension was created by [FGTCLB GmbH](https://www.fgtclb.com/).

[Find more TYPO3 extensions we have developed](https://github.com/fgtclb/).
