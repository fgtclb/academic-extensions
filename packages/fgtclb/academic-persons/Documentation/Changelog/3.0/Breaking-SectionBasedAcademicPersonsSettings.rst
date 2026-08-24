..  _breaking-section-based-academic-persons-settings:

================================================
Breaking: Section-based AcademicPersons settings
================================================

Description
===========

The settings schema in
:file:`Configuration/AcademicPersons/Settings.yaml` now consists of the ordered
:yaml:`profile`, :yaml:`special`, :yaml:`contractContact` and
:yaml:`documentSections` maps. The former
:yaml:`profileInformationsTypes` and global :yaml:`validations` maps have been
removed.

Each direct profile and contract-contact field declares its visual ``section``,
base ``fieldType``, frontend ``renderType`` and validators. Special inline
components declare their render type and, where applicable, composed fields.
Each document section declares its label, stored
record type, relation field, optional read-only state and its own validators.
Document fields are normalized from ``from``, ``to`` and ``description`` to the
existing DTO properties ``yearStart``, ``yearEnd`` and ``bodytext``.

``AcademicPersonsSettingsFactory`` now creates typed ``ProfileSection``,
``ProfileField``, ``SpecialField``, ``ContractContactSection`` and
``DocumentSection`` objects. TCA and frontend validation
consume these objects directly. Document validation is emitted as type-specific
``columnsOverrides`` so one section can no longer influence another record type.
The settings cache uses a schema-specific identifier so an exported object from
the removed global schema cannot be restored as an empty section graph after an
extension update.

Impact
======

Every project-specific :file:`Configuration/AcademicPersons/Settings.yaml` must
be migrated. Replace the former global maps with the new section-based schema and
move every validator list below its field or document section. Integrations using
the internal ``ProfileInformationType`` or global validation-set APIs must use
the new section accessors instead.

Settings are still merged at the top level. An override of :yaml:`profile`,
:yaml:`special`, :yaml:`contractContact` or :yaml:`documentSections` must
contain the complete desired map. Flush all TYPO3
caches after migration.

..  index:: Backend, Configuration, Frontend, TCA, NotScanned, ext:academic_persons
