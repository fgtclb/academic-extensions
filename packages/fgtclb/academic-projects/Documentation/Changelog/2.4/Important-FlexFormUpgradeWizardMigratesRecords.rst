.. _important-1785796200:

=======================================================
Important: The FlexForm upgrade wizard migrates records
=======================================================

Description
===========

The upgrade wizard `academicProjects_flexFormUpgradeWizard`
(`FGTCLB\\AcademicProjects\\Upgrades\\FlexFormUpgradeWizard`) never migrated a
single content element. It built the `WHERE` constraint of its `UPDATE`
statement on the query builder of the enclosing `SELECT`:

..  code-block:: php

    $updateQueryBuilder->update('tt_content')
        ->set('pi_flexform', $this->array2xml($flexFormData))
        ->where(
            $queryBuilder->expr()->in(                                   // select builder
                'uid',
                $queryBuilder->createNamedParameter($record['uid'], …)   // select builder
            )
        )
        ->executeStatement();

A named parameter is bound to the query builder that created it, so the
statement carried a placeholder whose value was never bound to it — while
`set()` had created a placeholder of its own on the update builder, holding the
new FlexForm XML. The first record therefore ran

..  code-block:: sql

    UPDATE tt_content SET pi_flexform = :dcValue1 WHERE uid IN (:dcValue1)

comparing the XML against `uid`, and every following record referred to a
placeholder that did not exist on the update builder at all.

The constraint is now built on the builder that executes it, with `eq()` since
the value is always a single uid:

..  code-block:: php

    ->where(
        $updateQueryBuilder->expr()->eq(
            'uid',
            $updateQueryBuilder->createNamedParameter($record['uid'], Connection::PARAM_INT)
        )
    )

Impact
======

The wizard now performs the FlexForm migration it announces:
`settings.hideCompletedProjects` becomes `settings.activeState` (value `1` maps
to `active`, anything else to `all`), `settings.filter.options` becomes
`settings.hideFilter` and `settings.sorting.options` becomes
`settings.hideSorting`, each keeping its value.

Previously the outcome depended on how many affected content elements existed:

*   The first record compared the new FlexForm XML against `uid`, because the
    placeholder of the update builder and the one of the select builder
    happened to carry the same name. MySQL, MariaDB and SQLite reported
    success and changed nothing, PostgreSQL aborted with a
    `Doctrine\\DBAL\\Exception\\DriverException`, *invalid input syntax for
    integer*.
*   Every record after it referenced a placeholder that was never bound to the
    update statement. Doctrine DBAL rejects such a statement before it reaches
    the database, so the wizard aborted on every DBMS with a
    `Doctrine\\DBAL\\ArrayParameters\\Exception\\MissingNamedParameter`,
    *Named parameter "dcValueN" does not have a bound value*.

Affected Installations
======================

Installations that ran the upgrade wizard of a previous version. The plugin
FlexForm of `academicprojects_projectlist` and
`academicprojects_projectlistsingle` content elements still carries the old
setting names, and the plugins fall back to their default behaviour for those
settings.

Migration
=========

Run the upgrade wizard again. An installation that already executed it has it
recorded as done, so it has to be marked undone first — in the Install Tool
under :guilabel:`Upgrade > Upgrade Wizard`, or on the command line:

..  code-block:: bash

    vendor/bin/typo3 upgrade:mark:undone academicProjects_flexFormUpgradeWizard
    vendor/bin/typo3 upgrade:run academicProjects_flexFormUpgradeWizard

Running it again is safe: the wizard only rewrites a FlexForm that still
contains one of the old setting names and leaves every other record untouched.

.. index:: Database, FlexForm, Backend, NotScanned
