..  _important-ace-897-academic-contacts4pages:

===================================================================
Important: A new upgrade wizard migrates the plugins of version 1.x
===================================================================

Description
===========

Version 1.x registered the page contacts plugin as `ContactsList`, so its
content elements carry the type :sql:`academiccontacts4pages_contactslist`.
From 2.0.0 on the plugin is registered as `List`, and its content elements
carry the type :sql:`academiccontacts4pages_list`.

The upgrade wizard ``academicContact4pages_pluginUpgradeWizard`` of 2.0.0 to
2.3.4 was meant to rename that type. It looked for
:sql:`academiccontact4pages_contactslist`, a misspelled value no release ever
stored, so it never found a content element. It reported that there was
nothing to do, and :bash:`vendor/bin/typo3 upgrade:run` marks a wizard that
reports so as done.

The upgrade wizard :guilabel:`Migrate the academic_contacts4pages plugins of
version 1.x` replaces it, with the identifier
``academicContacts4pages_pluginUpgradeWizard``. It renames
:sql:`academiccontacts4pages_contactslist` to
:sql:`academiccontacts4pages_list` in every content element: in every
language, hidden or deleted, and in every workspace version.

It also carries over the permission of backend user groups. A group may only
edit a content element whose type it is explicitly allowed, so a group that
lists ``tt_content:CType:academiccontacts4pages_contactslist`` in
:guilabel:`Explicitly allow field values` is given
``tt_content:CType:academiccontacts4pages_list`` in its place. The other
values of the group keep their order, and a group that already has the new
value does not get it twice.

Impact
======

Once the wizard has run, a content element of version 1.x renders the
contacts of its page, and the backend shows it as the page contacts plugin.
Before, the frontend showed the error message of TYPO3 for a content element
type without a rendering definition in its place, and the backend showed an
unknown content element type. Editors of a group that was allowed the old
type can edit the migrated content elements.

The wizard is offered as long as such a content element or such a backend
user group exists, also in an installation where the previous wizard is
marked as done. TYPO3 records that flag by the class of a wizard, and the new
wizard is a class of its own. The flag of the previous wizard stays in the
registry and is harmless: no wizard of that class is registered any more, so
nothing shows or reads it.

Version 1.x had no plugin options. A migrated content element therefore
carries none and lists the contacts the way a newly added plugin does by
default. Open it in the backend to choose other options.

Affected Installations
======================

Installations upgraded from version 1.x that still have content elements of
the type :sql:`academiccontacts4pages_contactslist`, or backend user groups
allowed to edit it. An installation that started with 2.0.0 or later has
neither, and the wizard is not offered there.

Migration
=========

Run the upgrade wizard in :guilabel:`Admin Tools > Upgrade > Upgrade Wizard`
or on the command line:

..  code-block:: bash

    vendor/bin/typo3 upgrade:run academicContacts4pages_pluginUpgradeWizard

TypoScript, page TSconfig or templates of a site package that name the old
type, for example ``tt_content.academiccontacts4pages_contactslist`` or an
entry of ``TCEFORM.tt_content.CType.removeItems``, have to be changed to
``academiccontacts4pages_list`` by hand.

.. index:: Backend, Database, ext:academic_contacts4pages
