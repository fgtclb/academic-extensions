..  _important-select-item-labels-read-the-plugin-override-on-typo3-v14:

===================================================================
Important: Select item labels read the plugin override on TYPO3 v14
===================================================================

Description
===========

:php:`\FGTCLB\AcademicBase\Controller\GetSelectItemsForTcaManagedTableFieldMethodTrait`
hands the plugin request on when it translates the item labels, on TYPO3 v14.
The core reads the :typoscript:`_LOCAL_LANG` override of a plugin only from
that request there, so the override of the plugin now reaches the items as
well. Before, only the override of the extension did.

The path of the overrides is the one of
:ref:`important-select-item-labels-read-the-documented-path`.

Impact
======

Code that calls the trait passes the same arguments as before; nothing has to
change there.

..  index:: Frontend, PHP-API, TypoScript, ext:academic_base
