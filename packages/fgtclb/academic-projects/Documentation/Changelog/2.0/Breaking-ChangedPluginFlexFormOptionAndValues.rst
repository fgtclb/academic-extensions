..  include:: /Includes.rst.txt

..  _breaking-1771362979:

===================================================
Breaking: Changed plugin FlexForm option and values
===================================================

Description
===========

FlexForm options of following plugins has changed:

* `academicprojects_projectlist`
* `academicprojects_projectlistsingle`

Changed options:

* `settings.hideCompletedProjects` => `settings.activeState`
* `settings.filter.options` => `settings.hideFilter`
* `settings.sorting.options` => `settings.hideSorting`

In case of `settings.activeState` that also includes supported values for
the field, which also matches possible values of newly added PHP Enum
:php:`\FGTCLB\AcademicProjects\Domain\Model\Dto\ActiveState`:

+-----------+------------------------+----------------------+
| FlexForm  | Enum                   | Old Value            |
+===========+========================+======================+
| all       | ActiveState::ALL       | any value except `1` |
+-----------+------------------------+----------------------+
| active    | ActiveState::ACTIVE    | 1                    |
+-----------+------------------------+----------------------+
| completed | ActiveState::COMPLETED | no old value         |
+-----------+------------------------+----------------------+

Impact
======

Plugin usage with custom set old options uses fallback values of
new options and will behave not in the expected way until options
are migrated using the provided wizard.

Further, plugin output may lead to unexpected output not adoption
for changed option names in case fluid templates are overridden,
either in a project site package extension or another extension.


Affected installations
======================

All TYPO3 instances upgrade from `1.x` to `2.x` of this extension
and having at least one of the mentioned plugins used as content
object, either as content element or direct rendering in TypoScript.

Also extensions overriding fluid templates of `EXT:academics_projects`.


Migration
=========

An TYPO3 UpgradeWizard `academicProjects_flexFormUpgradeWizard` is provided
to migrate old plugin FlexForm options and values to the new settings and
values, in case of `activeState` also transforming the value based on mapping
mentioned in the table above.

..  important::

    The upgrade wizard should be executed after the plugin `list_type` to `CType`
    upgrade wizard has been executed and before lowlevel flexform data cleanup
    (v13) has been executed.

Furthermore, overridden fluid template in projects needs to adopt for changed
options listed above in the description.

..  index:: Backend, FullyScanned, ext:about
