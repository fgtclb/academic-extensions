.. _important-ace-666-academic-base:

========================================================
Important: A hidden academic content type stays selected
========================================================

Description
===========

The academic extensions hide their content elements for the whole installation
and bring them back per component, see
:ref:`breaking-site-sets-and-page-tsconfig-restructured`. On a page where a
component was not enabled, an existing content element of that component opened
with a :guilabel:`Type` field that had no matching option. TYPO3 selected the
first option instead, and saving the record replaced the content type without a
warning. TYPO3 shows its "invalid value" option for an unknown type, but not for
a type that page TSconfig hides.

Such a content element now shows its stored type as the selected option,
followed by :guilabel:`(not enabled on this page)`, and saving the record keeps
it. This applies on TYPO3 v12 and v13.

Impact
======

*   Choosing another type and saving changes it as before, and the hidden type is
    not offered again afterwards.
*   A new content element, and the new content element wizard, still only offer
    the types enabled on the page.
*   Only content types of the :guilabel:`Academic` group are kept. Content types
    of other extensions keep the behaviour of TYPO3.

**Content elements that were already rewritten are not repaired.** Their former
type is not stored in the record, so they cannot be found automatically. The
record history of such a content element shows the change for as long as the
history is kept; correct the type by hand there.

Affected Installations
======================

Every installation with an academic content element on a page where its
component is not enabled: a site configured through :sql:`sys_template` records
that does not include the page TSconfig entry of the component, on TYPO3 v13 a
page tree that uses a different site set, or a page that restricts the content
types with :typoscript:`TCEFORM.tt_content.CType.keepItems` or
:typoscript:`TCEFORM.tt_content.CType.removeItems`.

.. index:: Backend, TSConfig, ext:academic_base
