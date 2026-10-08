.. _important-ace-871-academic-projects:

===================================================================
Important: FlexForm upgrade wizard migrates the setting name of 1.x
===================================================================

Description
===========

Version 1.x of the project list plugins stored the checkbox "Hide completed
projects" as ``settings.hide_completed_projects``. The upgrade wizard
``academicProjects_flexFormUpgradeWizard`` looked for
``settings.hideCompletedProjects`` instead, a name no released version stored,
so it never migrated that setting. A project list of 1.x lost its selection and
showed completed projects as well, the default of ``settings.activeState``.

The wizard now migrates ``settings.hide_completed_projects`` with the meaning it
had in 1.x: a checked box (``1``) becomes ``settings.activeState = active``,
anything else ``all``. ``settings.hideCompletedProjects`` is still migrated the
same way.

The wizard is now also offered only while a project list still stores one of
the old settings, ``settings.hide_completed_projects``,
``settings.hideCompletedProjects``, ``settings.filter.options`` or
``settings.sorting.options``. Before, it was offered as soon as a project list
existed, and rewrote every project list when it ran.

Impact
======

The project lists of 1.x get back the selection they had, on the next run of the
wizard.

Affected Installations
======================

Installations upgraded from 1.x that already ran the wizard. It is recorded as
done there and is not offered again by itself. Mark it undone and run it:

..  code-block:: bash

    vendor/bin/typo3 upgrade:mark:undone academicProjects_flexFormUpgradeWizard
    vendor/bin/typo3 upgrade:run academicProjects_flexFormUpgradeWizard

Saving a project list in the backend keeps a stored setting the form no longer
shows, so a list saved in 2.x carries the old setting next to the new one. Such
a list keeps its stored 2.x value, ``settings.activeState``,
``settings.hideFilter`` or ``settings.hideSorting``, and only loses the old
setting. A list that was not saved since the upgrade gets the meaning the
setting had in 1.x.

.. index:: Backend, FlexForm, ext:academic_projects
