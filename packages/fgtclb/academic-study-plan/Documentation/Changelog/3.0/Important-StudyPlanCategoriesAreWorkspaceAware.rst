.. _important-1787907823:

========================================================
Important: Study plan categories are now workspace aware
========================================================

Description
===========

The table :sql:`tx_academicstudyplan_domain_model_category` was the only one of
the three record tables of this extension without :php:`'versioningWS' => true`
in its TCA :php:`ctrl` section. It now carries the flag, like
:sql:`tx_academicstudyplan_domain_model_semester` and
:sql:`tx_academicstudyplan_domain_model_module` already did.

The two of them were declared workspace aware because they are inline children
of the workspace aware :sql:`tt_content`. Categories are related to a module
through the intermediate table
:sql:`tx_academicstudyplan_module_category_mm` instead, so the same reasoning
applies to them, but nothing pointed it out — neither TYPO3 nor the test suite
reports a missing declaration for that relation type.

Impact
======

The flag is what :php:`\TYPO3\CMS\Core\Database\Schema\DefaultTcaSchema` derives
the :sql:`t3ver_oid`, :sql:`t3ver_wsid`, :sql:`t3ver_state` and
:sql:`t3ver_stage` columns and their index from, so the table needs those
columns added. **Run the database analyzer once after updating**, in the
:guilabel:`Admin Tools > Maintenance` module or with
:bash:`vendor/bin/typo3 extension:setup`.

This is not optional and it does not wait for someone to open a workspace. A
workspace aware table is queried with a :php:`WorkspaceRestriction` in the live
workspace too, so until the analyzer has run, both of these raise a database
error about the unknown columns:

*   the backend list of study plan categories — :php:`DatabaseRecordList` adds
    the restriction unconditionally, with the backend user's workspace,
    including workspace :php:`0`;
*   the frontend rendering of any **translated** study plan — the language
    overlay in :php:`\TYPO3\CMS\Core\Domain\Repository\PageRepository` selects
    the overlay record with a :php:`FrontendRestrictionContainer`, which carries
    the restriction by default.

..  warning::

    **On SQLite the command line path does not work.**
    :bash:`vendor/bin/typo3 extension:setup` reports success, creates the index
    over :sql:`t3ver_oid` and :sql:`t3ver_wsid`, and does **not** add the four
    columns. The database is then left with an index over columns that do not
    exist, and every later schema operation aborts with
    :php:`Doctrine\DBAL\Schema\Index::_addColumn(): Argument #1 ($column) must
    be of type string, null given`. Nothing is printed when it happens — the
    failing statement is collected into a result array the command does not
    show.

    A plain column addition is applied correctly on the same database, so this
    is specific to a table gaining columns and an index over exactly those new
    columns in one step — which is what this change is. Verified on TYPO3 v13.

    This is a TYPO3 Core defect, tracked as `forge issue #110422
    <https://forge.typo3.org/issues/110422>`__ with a fix under review that is
    scheduled for TYPO3 v13.4, v14.3 and main.

    Take the schema from a database built with the new state rather than
    migrating an existing one, and check afterwards that
    :sql:`tx_academicstudyplan_domain_model_category` really carries the four
    columns. Installations on MySQL, MariaDB or PostgreSQL are not affected.

Once the columns are there, categories can be created and changed in a
workspace, and any consumer that gates on :php:`ctrl.versioningWS` — the
workspaces module, and third party extensions that restrict themselves to
versionable tables — sees the table.

That also flips how an editor's change is stored. In a workspace with live
editing enabled, a category edit previously went straight to live, because
TYPO3 permits live editing only for tables that are not workspace aware. It now
becomes a workspace version that has to be published.

What does **not** change is the frontend rendering of the study plan itself.
This extension selects semesters, modules and categories with its own queries,
which add no :php:`WorkspaceRestriction` and perform no version overlay, and
they resolve their relations through live uids. A workspace preview therefore
keeps showing the live study plan. The one exception is a side effect of the
language handling: the *translation* of a record is fetched through
:php:`PageRepository`, which does overlay it, so in a workspace preview a
translated category can differ from its default language row. That asymmetry is
not new — it applies to the semester and module tables in the same way — and it
is tracked separately.

Affected Installations
======================

Every installation of this extension. No existing record is touched and no
rendered output changes — but the database analyzer has to run, and until it
does the two places named under *Impact* are broken. Development instances
built from a committed database snapshot need the same treatment.

.. index:: Database, TCA, ext:academic_study_plan
