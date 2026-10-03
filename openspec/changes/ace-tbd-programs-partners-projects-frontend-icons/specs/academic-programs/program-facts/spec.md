## MODIFIED Requirements

### Requirement: Credit points are shown as a fact with an icon
The credit points fact SHALL render with its own icon and label, like a
category type fact. The icon SHALL be a frontend icon: it SHALL be shipped in
the frontend icons of the extension only, a site package SHALL replace it by
registering the same identifier in its own frontend icons
(`Configuration/FrontendIcons.php`), and a registration of that identifier in
the backend icons (`Configuration/Icons.php`) SHALL NOT change the facts. The
icon SHALL keep the wrapper markup it had before, with the identifier
`tx-academicprograms-info-credit-points`. This SHALL hold on TYPO3 v13 and
v14.

#### Scenario: Credit points fact
- **WHEN** a program with 180 credit points is shown and the setting lists
  `creditPoints`
- **THEN** the fact shows an icon, the credit points label and 180

#### Scenario: A site package replaces the credit points icon
- **WHEN** a site package registers its own drawing for
  `tx-academicprograms-info-credit-points` in its frontend icons
- **THEN** the program page, the details content element and the program
  card show that drawing for the credit points fact

#### Scenario: A replacement in the backend icons only
- **WHEN** a site package registers its own drawing for
  `tx-academicprograms-info-credit-points` in its backend icons only
- **THEN** the credit points fact keeps the drawing the extension ships

#### Scenario: The backend does not know the icon
- **WHEN** an integrator asks the backend icons of TYPO3 for
  `tx-academicprograms-info-credit-points`
- **THEN** the identifier is not registered there

#### Scenario: Site styles keep applying
- **WHEN** a site styles the icon of the credit points fact by its wrapper
  element, its `icon-tx-academicprograms-info-credit-points` class or its
  `data-identifier` attribute
- **THEN** the style applies as before

## ADDED Requirements

### Requirement: Category type facts show the frontend icon of their type
Wherever the facts of a program are shown, on the program page, in the program
details content element and on the program card, a category type fact SHALL
show the frontend icon of its type: the frontend icon declared for the type in
the category type configuration when it declares one, its icon otherwise, and
a drawing a site package registers for that type in its frontend icons in
place of either. The backend SHALL keep showing the declared icon of the type.
The icon SHALL keep the wrapper markup it had before, with the identifier
`category_types.programs.<type>`. This SHALL hold on TYPO3 v13 and v14.

#### Scenario: A type with a frontend icon of its own
- **WHEN** a site package declares a type of the group `programs` with an icon
  and a separate frontend icon, and the facts of a program list that type
- **THEN** the fact shows the frontend icon
- **AND** the category type select of a category record shows the icon

#### Scenario: A type without a frontend icon
- **WHEN** a type of the group `programs` declares an icon and no frontend
  icon
- **THEN** the fact of that type shows the icon

#### Scenario: A site package replaces a shipped type icon for the frontend
- **WHEN** a site package registers its own drawing for
  `category_types.programs.degree` in its frontend icons
- **THEN** the degree fact shows that drawing on the program page, in the
  details content element and on the program card
