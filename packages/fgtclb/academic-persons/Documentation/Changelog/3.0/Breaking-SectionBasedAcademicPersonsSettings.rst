..  _breaking-section-based-academic-persons-settings:

================================================
Breaking: Section-based AcademicPersons settings
================================================

Description
===========

The section-based editing schema consists of the ordered :yaml:`profile`,
:yaml:`special`, :yaml:`contractContact` and :yaml:`documentSections` maps in
:file:`Configuration/AcademicsPersonsEdit/Settings.yaml`. The public layout is
configured independently below :yaml:`profile` in
:file:`Configuration/AcademicPersons/Settings.yaml`. The former
:yaml:`profileInformationsTypes` and global :yaml:`validations` maps have been
removed.

Each direct profile and contract-contact field declares its visual ``section``,
base ``fieldType``, frontend ``renderType`` and validators. Special inline
components declare their render type and, where applicable, composed fields.
Each document section declares its label, stored record type, relation field,
optional read-only state, ordered ``rowFields`` and ordered ``actions`` as well
as its own validators. Read-only sections expose only viewing even if a
mutating action is accidentally configured.
Document fields are normalized from ``from``, ``to`` and ``description`` to the
existing DTO properties ``yearStart``, ``yearEnd`` and ``bodytext``.

The shared normalizer creates typed ``ProfileSection``,
``ProfileField``, ``SpecialField``, ``ContractContactSection``,
``DocumentSection`` objects for the edit graph. The public factory creates a
separate ``PublicProfileSettings`` object. Frontend validation consumes the
edit graph directly and remains isolated by document section. Backend TCA does
not consume this graph and is never changed by its validator flags. The
settings cache uses a schema-specific identifier so an exported object from the
removed global schema cannot be restored as an empty section graph after an
extension update.

Impact
======

Every project-specific configuration must be migrated. Move editable maps to
:file:`Configuration/AcademicsPersonsEdit/Settings.yaml`, put the public layout
below :yaml:`profile` in :file:`Configuration/AcademicPersons/Settings.yaml`,
and move every validator list below its field or document section. Integrations
using the internal ``ProfileInformationType`` or global validation-set APIs
must use the new section accessors instead.

Each settings namespace is merged at the top level. An edit override of :yaml:`profile`,
:yaml:`special`, :yaml:`contractContact` or :yaml:`documentSections` must
contain the complete desired map. A public :yaml:`profile` override likewise
must repeat its complete :yaml:`structure` and :yaml:`details` maps. Flush all
TYPO3 caches after migration.

..  index:: Configuration, Frontend, Validation, NotScanned, ext:academic_persons
