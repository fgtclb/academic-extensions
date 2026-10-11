.. _important-ace-458-academic-bite-jobs-wizard-show:

==================================================================
Important: The wizard entry no longer lists the element under show
==================================================================

Description
===========

Version 2.4 moved the entry of the content element in the new content element
wizard into :file:`Configuration/TSconfig/List/page.tsconfig`, together with the
line :typoscript:`show := addToList(academicbitejobs_list)` that TYPO3 v12 needs
to offer it, see :ref:`important-1787054400`.

That line is dropped. TYPO3 v13 and v14 build the wizard from TCA (Feature:
#102834), and :php:`NewContentElementController` has no reader for the
:typoscript:`show` list on either version. Both drop an element whose value
appears in :typoscript:`TCEFORM.tt_content.CType.removeItems` before rendering,
so the component page TSconfig still decides where the element is offered.

Impact
======

None. The content element is offered where the page TSconfig of its component
is included, as before.

Affected Installations
======================

None have to act. A site package that copied the :typoscript:`show` line can
drop it.

.. index:: TSConfig, Backend, ext:academic_bite_jobs
