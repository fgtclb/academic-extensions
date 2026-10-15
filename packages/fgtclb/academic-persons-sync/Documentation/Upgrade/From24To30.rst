..  _upgrade-from-2-4-to-3-0:

=========================
Upgrading from 2.4 to 3.0
=========================

Version 3.0 of this extension drops TYPO3 12 and changes no configuration, no
database column and no API. The upgrade is the update of TYPO3 and of the
package, made together with the extensions it depends on. Work through the
sections in order. Skip a step that does not apply to the installation.

..  _upgrade-from-2-4-to-3-0-before-you-start:

Before you start
================

Version 3.0 runs on TYPO3 13.4 from 13.4.35 on and on TYPO3 14.3 from 14.3.7
on, with PHP 8.2 or later.

:guilabel:`academic_persons_sync` 3.0 declares no new dependency of its own. It
needs these packages. Upgrade them first or in the same deployment, in this
order, as their own upgrade chapters describe:

*   :composer:`fgtclb/environment-state-manager` 2.x, which
    :guilabel:`academic_base` 3.0 and :guilabel:`academic_persons` 3.0
    require. Version 2.4 required 1.x. Composer updates it on its own. A
    classic installation updates :guilabel:`environment_state_manager` before
    it activates either of them.
*   :composer:`fgtclb/academic-base` 3.0, the same release as this extension.
*   :composer:`fgtclb/academic-persons` 3.0, the same release as this
    extension.
*   :composer:`fgtclb/academic-persons-edit` 3.0, the same release as this
    extension.

This extension needs no change of a project in the same deployment. The
upgrade chapters of the extensions above name theirs.

The module paths on this page are those of TYPO3 13, where the upgrade runs.

..  _upgrade-from-2-4-to-3-0-typo3:

Update TYPO3 first
==================

Update TYPO3 to 13.4.35 or later while the site still runs 2.4, which supports
TYPO3 13.4. Version 3.0 no longer installs on TYPO3 12, and Composer and the
Extension Manager refuse it next to an older patch release of TYPO3 13.

Stay on TYPO3 13 until the upgrade is complete. This extension requires
:guilabel:`academic_persons` and :guilabel:`academic_persons_edit`, whose
content element wizards read the column :sql:`tt_content.list_type`, which
TYPO3 14 removes, so they only run on TYPO3 13. The same holds for the wizards
of :guilabel:`academic_jobs` and :guilabel:`academic_projects` when the
installation also has them. Move to TYPO3 14.3.7 or later once they have run.

See :ref:`breaking-removed-typo3-v12-support` and
:ref:`important-minimum-typo3-versions`.

..  _upgrade-from-2-4-to-3-0-package:

Update the package
==================

..  code-block:: bash

    composer require -W \
        'fgtclb/academic-base':'~3.0.0' \
        'fgtclb/academic-persons':'~3.0.0' \
        'fgtclb/academic-persons-edit':'~3.0.0' \
        'fgtclb/academic-persons-sync':'~3.0.0'

On a classic installation, update :guilabel:`environment_state_manager` to 2.x
from the TER in :guilabel:`Admin Tools > Extensions` first, then update
:guilabel:`academic_base`, :guilabel:`academic_persons`,
:guilabel:`academic_persons_edit` and :guilabel:`academic_persons_sync` there,
in this order. :guilabel:`academic_persons_edit` 3.0 also needs the system
extension :guilabel:`rte_ckeditor` to be active, as its own upgrade chapter
describes. Run the commands of this page with
:bash:`typo3/sysext/core/bin/typo3` instead of :bash:`vendor/bin/typo3`.

..  code-block:: bash

    vendor/bin/typo3 cache:flush

..  _upgrade-from-2-4-to-3-0-database:

Update the database
===================

..  code-block:: bash

    vendor/bin/typo3 extension:setup

Or apply the changes in :guilabel:`Admin Tools > Maintenance > Analyze
Database Structure`.

This extension has no table of its own. The comparison applies the changes of
the index definitions TYPO3 13.4.35 and 14.3.7 bring, and the changes of
:guilabel:`academic_persons` and :guilabel:`academic_persons_edit`, which their
upgrade chapters describe.

..  _upgrade-from-2-4-to-3-0-configuration:

Adapt the configuration
=======================

..  _upgrade-from-2-4-to-3-0-upgrade-check:

The upgrade check
-----------------

The console command :bash:`academic:upgrade:check` of :guilabel:`academic_base`
reads the stored configuration of the installation and reports what no longer
reaches the academic extensions. Run it on the staging copy right after the
package update:

..  code-block:: bash

    vendor/bin/typo3 academic:upgrade:check

This extension ships no templates, so there is no template override of it to
compare. Run the check with the keys of the other academic extensions, as
their upgrade chapters describe. See the `upgrade check
<https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/UpgradeCheck/Index.html>`__
of :guilabel:`academic_base`.

..  _upgrade-from-2-4-to-3-0-check:

Check the result
================

*   Open a frontend user of the type
    :guilabel:`Academic Persons: External User`. The tab
    :guilabel:`Profiles` lists the assigned profiles, as in 2.4.
*   The upgrade check exits with ``0``, see
    :ref:`upgrade-from-2-4-to-3-0-upgrade-check`.
