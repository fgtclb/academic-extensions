..  _important-ace-923-academic-projects:

===========================================================================
Important: FlexForm upgrade wizard migrates "Insert plugin" elements of 1.x
===========================================================================

Description
===========

The upgrade wizard ``academicProjects_flexFormUpgradeWizard`` migrates the
settings of 1.x project lists. It named the wizard
``academicProjects_pluginUpgradeWizard`` as a prerequisite, so that it would run
second, on the content elements the plugin wizard creates. TYPO3 accepts only
prerequisites there, such as the database being up to date, and ignores another
wizard without a message. Nothing enforced the order.

A bare ``vendor/bin/typo3 upgrade:run`` asks every pending wizard whether it has
anything to do before it runs the first one, and records a wizard that has
nothing to do as done. On an installation upgraded from 1.x, the project lists
are still :guilabel:`Insert plugin` elements at that moment. The FlexForm wizard
only looked at content elements of the types ``academicprojects_projectlist``
and ``academicprojects_projectlistsingle``, found none and was recorded as
done. The plugin wizard then converted the elements, and their settings of 1.x,
``settings.hide_completed_projects``, ``settings.filter.options`` and
``settings.sorting.options``, were never migrated. Running
``vendor/bin/typo3 upgrade:run academicProjects_flexFormUpgradeWizard`` before
the plugin wizard had the same result.

The FlexForm wizard now also migrates the :guilabel:`Insert plugin` elements of
the two plugins, and names no other wizard any more. The two wizards can run in
either order, by name, one after the other in
:guilabel:`Admin Tools > Upgrade > Upgrade Wizard`, or together in one bare
``vendor/bin/typo3 upgrade:run``.

Impact
======

The project lists of 1.x keep the selection they had, also when all upgrade
wizards run at once.

Affected Installations
======================

Installations upgraded from 1.x that ran a bare ``vendor/bin/typo3 upgrade:run``,
or ran the FlexForm wizard before the plugin wizard. The FlexForm wizard is
recorded as done there and is not offered again by itself. Their project lists
fall back to the defaults of these settings: they show completed projects as
well, and they show the filter and the sorting where 1.x hid them.

Up to 2.3 the wizard migrated nothing in any order, see
:ref:`important-1785796200`. The same recovery applies to both.

Migration
=========

Mark the wizard undone and run it, in
:guilabel:`Admin Tools > Upgrade > Upgrade Wizard` or, on TYPO3 v13, on the
command line:

..  code-block:: bash

    vendor/bin/typo3 upgrade:mark:undone academicProjects_flexFormUpgradeWizard
    vendor/bin/typo3 upgrade:run academicProjects_flexFormUpgradeWizard

TYPO3 v12 has no command to mark a wizard undone. There it is marked undone in
the Install Tool, and ``upgrade:run`` runs it afterwards.

Running it again is safe. It only rewrites a FlexForm that still holds one of
the old setting names, and a setting that a save in the backend stored since
the upgrade is kept, see :ref:`important-ace-847-academic-projects`.

..  index:: Backend, FlexForm, ext:academic_projects
