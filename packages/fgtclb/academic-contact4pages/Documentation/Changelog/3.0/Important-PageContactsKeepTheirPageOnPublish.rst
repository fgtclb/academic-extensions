.. _important-page-contacts-keep-their-page-on-publish:

==========================================================================
Important: Page contacts keep their page and role when either is published
==========================================================================

Description
===========

A page contact points at its page and at its contacts role. Both pointers are
declared as relations, so TYPO3 treated them like any other relation when a
page or a role was edited in a workspace: the first edit versions the page
contacts together with the page or role, and TYPO3 pointed each contact
version at the version row of the page or role, which publishing deletes.

Published from the workspace module, TYPO3 writes the live page or role into
the page contacts once more after publishing, and nothing was lost, except in
two cases:

*   The workspace was published from the live workspace: by the scheduler
    through the publish date of the workspace (:bash:`workspace:autopublish`),
    or by other code that publishes as a user of the live workspace.
*   The page and the contacts role were both edited in the same workspace. The
    page contacts then lost their page, from the workspace module as well.

In both cases the contact belonged to no page, or had a role that no longer
existed. The contacts list of the page showed nothing, and a later edit of the
page no longer included the contact.

Both pointers now name their table in :php:`dontRemapTablesOnCopy`. A page
contact keeps its live page and role through a publish from either place, and
a copied page or role still gets copies of its page contacts that point at the
copy. The address selects of a page contact work the same way, see
:ref:`important-ace-481-academic-contacts4pages-workspaces`.

Impact
======

Neither the database schema nor an upgrade wizard is involved.

Page contacts that lost their page or role this way are not repaired. TYPO3
logs the uid of a published version only in :sql:`sys_log`, and on TYPO3 v13
and v14 in :sql:`sys_history` as well, and both are routinely cleaned, so the
original page or role cannot be told reliably. These queries list the live
page contacts whose page or role no longer exists:

..  code-block:: sql

    SELECT c.uid, c.pid, c.page
      FROM tx_academiccontacts4pages_domain_model_contact AS c
      LEFT JOIN pages AS p ON p.uid = c.page
     WHERE c.deleted = 0 AND c.t3ver_wsid = 0
       AND c.page > 0 AND p.uid IS NULL;

    SELECT c.uid, c.pid, c.role
      FROM tx_academiccontacts4pages_domain_model_contact AS c
      LEFT JOIN tx_academiccontacts4pages_domain_model_role AS r ON r.uid = c.role
     WHERE c.deleted = 0 AND c.t3ver_wsid = 0
       AND c.role > 0 AND r.uid IS NULL;

Open each of them in the list module, :guilabel:`Web > List` on TYPO3 v13
and :guilabel:`Content > Records` on TYPO3 v14, on the page given as
:sql:`pid`, and select its page or role again, or delete it and add the
contact on its page anew.

Affected Installations
======================

Installations that publish workspaces through their publish date, or that edit
a page and a contacts role in the same workspace. Every release of version 2
has the same defect and can hold such page contacts, so check an installation
that is upgraded from version 2.

.. index:: Backend, TCA, ext:academic_contacts4pages
