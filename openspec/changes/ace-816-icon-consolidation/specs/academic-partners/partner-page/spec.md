## MODIFIED Requirements

### Requirement: Category type icons are the frontend icons of the type
Wherever the partner page, the partner card of the partner list, the
partnerships list or the partnerships teaser shows a category type with its
icon, the icon SHALL be the frontend icon of that type: the frontend icon
declared for the type in the category type configuration when it declares
one, its icon otherwise, and a drawing a site package registers for that type
in its frontend icons (`Configuration/FrontendIcons.php`) in place of either.
The backend SHALL keep showing the declared icon of the type. The icon SHALL
keep the wrapper markup it had before, with the identifier
`category_types.partners.<type>`. The four shipped types SHALL show a Font
Awesome Free solid drawing, inlined and drawn in the colour of the surrounding
text, the collaboration type the shared partnership drawing of the academic
base extension. This SHALL hold on TYPO3 v13 and v14.

#### Scenario: A type with a frontend icon of its own
- **WHEN** a site package declares a type of the group `partners` with an icon
  and a separate frontend icon, and a partner carries a category of that type
- **THEN** the partner page, the partner card, the partnerships list and the
  partnerships teaser show the frontend icon for that type
- **AND** the category type select of a category record shows the icon

#### Scenario: A shipped type without a frontend icon
- **WHEN** a partner carries a category of the shipped type `partner_type` and
  no site package replaces its icon
- **THEN** the four places show the icon the extension declares for the
  partner type, inlined and in the text colour

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

## ADDED Requirements

### Requirement: Page type, content elements and records carry icons of their own
The academic partner page type SHALL show the icon
`tx-academicpartners-doktype-partner`. The partner content elements SHALL
show their icon in the page module, in the content element type select and in
the new content element wizard: the partner list
`tx-academicpartners-plugin-list`, the partner map
`tx-academicpartners-plugin-map`, the partners linked to a page
`tx-academicpartners-plugin-partners` and the partner logo teaser
`tx-academicpartners-plugin-partnerships-teaser`. Partnership records SHALL
show `tx-academicpartners-record-partnership` and role records
`tx-academicpartners-record-role`. The page type and the partners linked to a
page SHALL share one drawing under two identifiers, so a site package
replaces either one alone. Every partner page offered in the partner select of a partnership SHALL
carry the page type icon. All of them SHALL be drawn in the colour of the
surrounding text in both backend colour schemes. The identifiers of 2.x,
`academic-partners`, `tx_academicpartners_domain_model_partnership` and
`tx_academicpartners_domain_model_role`, SHALL no longer be registered. This
SHALL hold on TYPO3 v13 and v14.

#### Scenario: Editor adds a partner content element
- **WHEN** an editor opens the new content element wizard on a page whose site
  enables a partner content element
- **THEN** the wizard entry shows the same icon the content element shows in
  the page module

#### Scenario: Editor selects the partner of a partnership
- **WHEN** an editor opens the partner select of a partnership record
- **THEN** every offered partner page shows the icon of the academic partner
  page type

#### Scenario: Site package replaces the content element icon only
- **WHEN** a site package registers its own drawing for
  `tx-academicpartners-plugin-partners` in its backend icons
- **THEN** the content element of the partners linked to a page shows that
  drawing
- **AND** the other three partner content elements keep their drawings
- **AND** the academic partner page type keeps the shipped drawing

#### Scenario: Site still names a 2.x identifier
- **WHEN** a site names `academic-partners` as an icon, for example in page
  TSconfig of a wizard entry
- **THEN** the backend shows the not-found icon of TYPO3 for it
