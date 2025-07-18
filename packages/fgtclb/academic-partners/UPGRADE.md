# Upgrade 2.0

## X.Y.Z

### BREAKING: Removed partials

Some partials got removed as the templating structure has changed. Those partials include:

`Resources/Private/Layouts/Default.html`
`Resources/Private/Pages/AcademicPartners.html`
`Resources/Private/Partials/Categories.html`
`Resources/Private/Partials/Partner/Items.html`
`Resources/Private/Partials/Partner/SingleItem.html`
`Resources/Private/Partials/Partnerships/ListItem.html`
`Resources/Private/Partials/Partnerships/TeaserItem.html`

### BREAKING: Move translations to their belonging locallang files

As some labels used for the backend were maintained in the locallang files for frontend usage,
these labels were organized accordingly.

### BREAKING: Changed identifier of backend layout

The identifier of the backend layout was changed to unfiy the naming in the academic extensions

> [!NOTE]
> The default templating now supports basic bootstrap styling and is semantically optimized
> to also not lack any major accessibility.

## 2.0.1

## 2.0.0

### Switch to `EXT:category_types` v2

Category type based handling has been streamlined and centralized within the `EXT:category_types` extension
and the known implementation based on deprecated TYPO3 Enumeration has been replaced with a modern PHP API
provided by the `EXT:category_type` extension.

Extension specific category types are now grouped and are now defined by newly introduced yaml file format,
following a concrete convention to look and auto-register these files.

`EXT:academic_partners` now ships a default set of partner related category types, which can be found
in [Configuration/CategoryTypes.yaml](./Configuration/CategoryTypes.yaml).

`EXT:academic_partners` related category types can be extended by any other TYPO3 extension providing a
`Configuration/CategoryTypes.yaml` file containing category-types using the group-identifier `partners`.

`Configuration/CategoryTypes.yaml` format uses following syntax:

```yaml
types:
  - identifier: <unique_identifier>
    title: '<type-translation-lable-using-full-LLL-syntax'
    group: partners
    icon: '<icon-file-using-EXT-syntax-needs-to-be-an-as-svg>'
```
