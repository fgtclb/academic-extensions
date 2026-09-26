..  _important-select-item-labels-read-the-documented-path:

===============================================================
Important: Select item labels are read from the documented path
===============================================================

Description
===========

:php:`\FGTCLB\AcademicBase\Controller\GetSelectItemsForTcaManagedTableFieldMethodTrait`
translates the item labels of a TCA select field for a frontend form. It takes
the extension key, :php:`'academic_jobs'` for example, and translated with it
as the extension name. TYPO3 v12 and v13 build the TypoScript path of
:typoscript:`_LOCAL_LANG` from that name as it is given, so they read label
overrides of the items from :typoscript:`plugin.tx_academic_jobs`.

The trait now turns an extension key into the extension name,
:php:`'AcademicJobs'`, before it translates. The labels read
:typoscript:`plugin.tx_academicjobs` and
:typoscript:`plugin.tx_academicjobs_<plugin>`, the paths the TYPO3
documentation names and TYPO3 v14 reads anyway. A name without an underscore
is used as it is given.

Impact
======

Code that calls the trait passes the extension key as before; nothing has to
change there. On TYPO3 v12 and v13, a label override of the items under the
underscored path no longer has an effect and moves to the documented one.

..  index:: Frontend, PHP-API, TypoScript, ext:academic_base
