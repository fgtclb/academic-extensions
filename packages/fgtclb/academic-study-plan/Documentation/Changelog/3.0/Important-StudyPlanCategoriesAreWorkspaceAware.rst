.. _important-1787907823:

=====================================================
Important: Study plan records are now workspace aware
=====================================================

Description
===========

The three record tables of this extension,
:sql:`tx_academicstudyplan_domain_model_semester`,
:sql:`tx_academicstudyplan_domain_model_module` and
:sql:`tx_academicstudyplan_domain_model_category`, carry
:php:`'versioningWS' => true` in their TCA :php:`ctrl` section now. None of
them did in 2.4.

Semesters and modules are inline children of the workspace aware
:sql:`tt_content`, and TYPO3 v14 declares such a child workspace aware on its
own and logs a deprecation for it. Categories are related to a module through
the intermediate table :sql:`tx_academicstudyplan_module_category_mm` instead,
so the same reasoning applies to them, but nothing points it out. Neither TYPO3
nor the test suite reports a missing declaration for that relation type.

Impact
======

The flag is what :php:`\TYPO3\CMS\Core\Database\Schema\DefaultTcaSchema` derives
the :sql:`t3ver_oid`, :sql:`t3ver_wsid`, :sql:`t3ver_state` and
:sql:`t3ver_stage` columns and their index from, so the three tables need those
columns added. **Run the database analyzer once after updating**, in the
:guilabel:`Admin Tools > Maintenance` module or with
:bash:`vendor/bin/typo3 extension:setup`.

This is not optional and it does not wait for someone to open a workspace. A
workspace aware table is queried with a :php:`WorkspaceRestriction` in the live
workspace too, so until the analyzer has run, both of these raise a database
error about the unknown columns:

*   the backend list of the study plan records. :php:`DatabaseRecordList` adds
    the restriction unconditionally, with the backend user's workspace,
    including workspace :php:`0`.
*   the frontend rendering of every study plan. The queries of the study plan
    carry the restriction since this version, see :ref:`important-1787924557`,
    and the language overlay in
    :php:`\TYPO3\CMS\Core\Domain\Repository\PageRepository` selects the
    overlay record with a :php:`FrontendRestrictionContainer`, which carries it
    by default.

..  warning::

    **On SQLite the command line path does not work.**
    :bash:`vendor/bin/typo3 extension:setup` reports success, creates the index
    over :sql:`t3ver_oid` and :sql:`t3ver_wsid`, and does **not** add the four
    columns. The database is then left with an index over columns that do not
    exist, and every later schema operation aborts with
    :php:`Doctrine\DBAL\Schema\Index::_addColumn(): Argument #1 ($column) must
    be of type string, null given`. Nothing is printed when it happens, the
    failing statement is collected into a result array the command does not
    show.

    A plain column addition is applied correctly on the same database, so this
    is specific to a table gaining columns and an index over exactly those new
    columns in one step, which is what this change is. Verified on TYPO3 v13.

    This is a TYPO3 Core defect, tracked as `forge issue #110422
    <https://forge.typo3.org/issues/110422>`__ with a fix under review that is
    scheduled for TYPO3 v13.4, v14.3 and main.

    Take the schema from a database built with the new state rather than
    migrating an existing one, and check afterwards that each of the three
    tables really carries the four columns. Installations on MySQL, MariaDB or
    PostgreSQL are not affected.

Once the columns are there, study plan records can be created and changed in a
workspace, and any consumer that gates on :php:`ctrl.versioningWS`, such as the
workspaces module or a third party extension that restricts itself to
versionable tables, sees the three tables.

That also flips how an editor's change is stored. In a workspace with live
editing enabled, an edit of a semester, module or category previously went
straight to live, because TYPO3 permits live editing only for tables that are
not workspace aware. It now becomes a workspace version that has to be
published. What the frontend shows of such a version in a workspace preview is
described in :ref:`important-1787924557`.

Affected Installations
======================

Every installation of this extension. No existing record is touched, but the
database analyzer has to run, and until it does the two places named under
*Impact* are broken. Development instances
built from a committed database snapshot need the same treatment.

.. index:: Database, TCA, ext:academic_study_plan
