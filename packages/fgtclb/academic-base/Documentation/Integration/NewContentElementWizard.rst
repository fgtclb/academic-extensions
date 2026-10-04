..  index:: Integration; New content element wizard
..  _integration-wizard:

==========================
New content element wizard
==========================

Since TYPO3 v13 the new content element wizard is built from TCA. Every
academic content element is an item of the type field of
:sql:`tt_content` in the group `academic`, which this extension registers,
and the wizard shows the items of a group in the order the extensions
registered them. Page TSconfig below
:typoscript:`mod.wizards.newContentElement.wizardItems` changes what the wizard
shows. Everything on this page applies to TYPO3 v13 and v14 alike, except the
ordering of elements with `before` and `after`, which needs TYPO3 v14.

The behaviour of the wizard this page describes is covered by the functional
test :file:`Tests/Functional/Backend/NewContentElementWizardTest.php` of this
extension, which renders the wizard on both versions with the page TSconfig
shown here.

..  contents::
    :local:
    :depth: 1

..  _integration-wizard-visible:

Which academic elements the wizard offers
=========================================

Every academic extension hides its content elements for the whole
installation and brings each one back through the set or the page TSconfig
file of its component, see the :guilabel:`Configuration` chapter of the
extension. The wizard follows the type field: a type that
:typoscript:`TCEFORM.tt_content.CType.removeItems` removes is missing from the
wizard as well.

..  _integration-wizard-position:

Where the group appears
=======================

The page TSconfig this extension loads for the whole installation labels the
group and gives it no position:

..  code-block:: typoscript

    mod.wizards.newContentElement.wizardItems.academic {
      header = LLL:EXT:academic_base/Resources/Private/Language/locallang_be.xlf:content.ctype.group.label
    }

Without a position the groups follow the order of their TCA registration, and
the group follows the groups of TYPO3, because this extension registers it
after them. An extension that registers a group of its own later adds it
behind. On an installation without other groups and positions, the wizard
shows:

#.  :guilabel:`Typical page content` (`default`)
#.  :guilabel:`Lists` (`lists`)
#.  :guilabel:`Menu` (`menu`)
#.  :guilabel:`Form elements` (`forms`)
#.  :guilabel:`Special elements` (`special`)
#.  :guilabel:`Plugins` (`plugins`)
#.  :guilabel:`Academic` (`academic`)

A group without elements is not shown.

To show the group first, place it before :guilabel:`Typical page content`:

..  code-block:: typoscript

    mod.wizards.newContentElement.wizardItems.academic.before = default

A single `before` or `after` changes how TYPO3 orders all groups. With one,
TYPO3 orders them by those settings: the groups named in them come first, in
the order the settings ask for, and every other group follows in the
alphabetical order of its identifier. The setting above therefore shows
:guilabel:`Academic`, :guilabel:`Typical page content`,
:guilabel:`Form elements`, :guilabel:`Lists`, :guilabel:`Menu`,
:guilabel:`Plugins` and :guilabel:`Special elements`, in this order.

Before version 3.0 this extension placed the group with `after = special`. That
showed :guilabel:`Special elements` and :guilabel:`Academic` first, followed by
the other groups in alphabetical order. A site that wants this placement back
sets it again:

..  code-block:: typoscript

    mod.wizards.newContentElement.wizardItems.academic.after = special

The `before` and `after` of every extension and of the site take part in the
same ordering, so an installation that positions groups of its own gets a
different result.

..  _integration-wizard-labels:

Renaming the group and its elements
===================================

The header of the group and the title and description of an element are
replaced by page TSconfig. An element is addressed by its content type, the
value of the type field:

..  code-block:: typoscript

    mod.wizards.newContentElement.wizardItems.academic {
      header = University
      elements.academicpersons_list {
        title = Staff directory
        description = All people of a department, with a letter navigation
      }
    }

Each value may be a plain text or an `LLL:` reference.

:typoscript:`TCEFORM.tt_content.CType.altLabels.<type>` renames the type in the
type field of the content element only. The wizard keeps its own title, so a
project that wants both renamed sets both.

..  _integration-wizard-hide:

Hiding an element or the group
==============================

:typoscript:`removeItems` of the group hides one element in the wizard, and
:typoscript:`removeItems` of the wizard hides a whole group:

..  code-block:: typoscript

    # Hides one element. Its type stays selectable in the type field.
    mod.wizards.newContentElement.wizardItems.academic.removeItems := addToList(academicpersons_selectedcontracts)

    # Hides the whole group.
    mod.wizards.newContentElement.wizardItems.removeItems := addToList(academic)

To hide an element in the type field too, remove the type with
:typoscript:`TCEFORM.tt_content.CType.removeItems`, which the wizard follows.

The option `show` of TYPO3 v12 is no longer read, see
`Breaking: #102834 <https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/13.0/Breaking-102834-RemoveItemsFromNewContentElementWizard.html>`__.

..  _integration-wizard-order:

Ordering the elements
=====================

On TYPO3 v14, `before` and `after` order the elements inside a group, as they
order the groups. TYPO3 added this in version 14.2
(`Feature: #87435 <https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/14.2/Feature-87435-MakeNewContentElementWizardItemsSortOrderConfigurable.html>`__):

..  code-block:: typoscript

    mod.wizards.newContentElement.wizardItems.academic.elements {
      academicpersons_listanddetail.before = academicpersons_list
    }

TYPO3 v13 reads `before` and `after` of a group only, and ignores them on an
element. There, the order comes from defining the elements again in page
TSconfig. An element whose :typoscript:`tt_content_defValues` set the type and
nothing else replaces the one TYPO3 builds from TCA, and the elements defined
again follow the elements that are not, in the order of the page TSconfig.
Define every element of the group again, with the icon, the title and the
description it should show:

..  code-block:: typoscript

    mod.wizards.newContentElement.wizardItems.academic.elements {
      academicpersons_listanddetail {
        iconIdentifier = tx-academicpersons-plugin-persons
        title = LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:plugin.listAndDetail.label
        tt_content_defValues.CType = academicpersons_listanddetail
      }
      academicpersons_list {
        iconIdentifier = tx-academicpersons-plugin-persons
        title = LLL:EXT:academic_persons/Resources/Private/Language/locallang_be.xlf:plugin.list.label
        tt_content_defValues.CType = academicpersons_list
      }
      # ... every other element of the group
    }

This works on TYPO3 v14 as well. An element defined again does not follow a
later change of its icon, title or description in the extension, so prefer
`before` and `after` once an installation runs TYPO3 v14.

The content types of every academic extension are listed in
:ref:`Permission sets <integration-permission-sets>`.
