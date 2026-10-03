## ADDED Requirements

### Requirement: Category type icons are the frontend icons of the type
Wherever the partner page, the partner card of the partner list, the
partnerships list or the partnerships teaser shows a category type with its
icon, the icon SHALL be the frontend icon of that type: the frontend icon
declared for the type in the category type configuration when it declares
one, its icon otherwise, and a drawing a site package registers for that type
in its frontend icons (`Configuration/FrontendIcons.php`) in place of either.
The backend SHALL keep showing the declared icon of the type. The icon SHALL
keep the wrapper markup it had before, with the identifier
`category_types.partners.<type>`. No icon of the extension changes its
registration. This SHALL hold on TYPO3 v13 and v14.

#### Scenario: A type with a frontend icon of its own
- **WHEN** a site package declares a type of the group `partners` with an icon
  and a separate frontend icon, and a partner carries a category of that type
- **THEN** the partner page, the partner card, the partnerships list and the
  partnerships teaser show the frontend icon for that type
- **AND** the category type select of a category record shows the icon

#### Scenario: A shipped type without a frontend icon
- **WHEN** a partner carries a category of the shipped type `region` and no
  site package replaces its icon
- **THEN** the four places show the icon the extension declares for the
  region

#### Scenario: A site package replaces a shipped type icon for the frontend
- **WHEN** a site package registers its own drawing for
  `category_types.partners.region` in its frontend icons
- **THEN** the four places show that drawing for the region
- **AND** the backend keeps showing the declared icon of the region

#### Scenario: Site styles keep applying
- **WHEN** a site styles a category type icon by its wrapper element, its
  `icon-category_types.partners.<type>` class or its `data-identifier`
  attribute
- **THEN** the style applies as before
