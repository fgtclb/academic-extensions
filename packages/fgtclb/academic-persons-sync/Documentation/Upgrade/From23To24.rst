..  _upgrade-from-2-3-to-2-4:

=========================
Upgrading from 2.3 to 2.4
=========================

Version 2.4 of this extension changes no configuration, no database column and
no API. The upgrade is the package update, made together with the extensions it
depends on. Work through the sections in order. Skip a step that does not apply
to the installation.

..  _upgrade-from-2-3-to-2-4-before-you-start:

Before you start
================

Version 2.4 runs on TYPO3 12.4 from 12.4.22 on and on TYPO3 13.4, with PHP 8.1
or later. That is the same as for 2.3, so the TYPO3 core does not have to
change for this upgrade.

:guilabel:`academic_persons_sync` 2.4 declares no new dependency of its own. It
needs these packages. Upgrade them first or in the same deployment, in this
order, as their own upgrade chapters describe:

*   :composer:`fgtclb/environment-state-manager` 1.x, new in 2.4, which
    :guilabel:`academic_base` 2.4 and :guilabel:`academic_persons` 2.4 require.
    Composer installs it on its own. A classic installation installs
    :guilabel:`environment_state_manager` before it activates either of them.
*   :composer:`fgtclb/academic-base` 2.4, the same release as this extension.
*   :composer:`fgtclb/academic-persons` 2.4, the same release as this
    extension.
*   :composer:`fgtclb/academic-persons-edit` 2.4, the same release as this
    extension.

..  _upgrade-from-2-3-to-2-4-package:

Update the package
==================

..  code-block:: bash

    composer require -W \
        'fgtclb/academic-base':'~2.4.0' \
        'fgtclb/academic-persons':'~2.4.0' \
        'fgtclb/academic-persons-edit':'~2.4.0' \
        'fgtclb/academic-persons-sync':'~2.4.0'

A project that requires branch ``2`` as ``2.x-dev`` keeps an inline alias such
as ``2.x-dev as 2.4.x-dev`` working, see :ref:`important-1790796665`.

On a classic installation, install :guilabel:`environment_state_manager` 1.x
from the TER in :guilabel:`Admin Tools > Extensions` first, then update
:guilabel:`academic_base`, :guilabel:`academic_persons`,
:guilabel:`academic_persons_edit` and :guilabel:`academic_persons_sync` there,
in this order. Run the commands of this page with
:bash:`typo3/sysext/core/bin/typo3` instead of :bash:`vendor/bin/typo3`.

..  code-block:: bash

    vendor/bin/typo3 cache:flush

..  _upgrade-from-2-3-to-2-4-check:

Check the result
================

*   Open a frontend user of the type
    :guilabel:`Academic Persons: External User`. The tab with the assigned
    profiles is labelled :guilabel:`Profiles`. Version 2.3 read its label from
    a file that does not exist, so the tab had no label.
