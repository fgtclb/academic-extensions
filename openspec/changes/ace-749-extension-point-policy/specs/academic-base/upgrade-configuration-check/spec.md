## MODIFIED Requirements

### Requirement: XCLASS registrations of academic classes are reported
The check SHALL report an XCLASS registration for a class of an academic
extension as a warning, and as an error when the replaced class is final. An
XCLASS registration for a domain model of an academic extension SHALL be
reported as a notice, because registering a subclass of a model is the
supported way for a project to add fields to it, on TYPO3 v13 and v14.

#### Scenario: XCLASS of a final class
- **WHEN** an installation registers an XCLASS for a final academic class
- **THEN** the check reports an error naming both classes

#### Scenario: XCLASS of a class that is not final
- **WHEN** an installation registers an XCLASS for an academic class that is
  not final and is not a domain model
- **THEN** the check reports a warning naming both classes

#### Scenario: XCLASS of a domain model
- **WHEN** an installation registers an XCLASS for a domain model of an
  academic extension, such as the profile of `academic_persons`
- **THEN** the check reports a notice naming both classes
- **AND** the upgrade check command does not fail because of it

#### Scenario: XCLASS of a class the installed version does not have
- **WHEN** an installation registers an XCLASS for a class of an academic
  extension that the installed version no longer ships
- **THEN** the check reports an error naming both classes
