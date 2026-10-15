..  _upgrade-from-2-4-to-3-0:

=========================
Upgrading from 2.4 to 3.0
=========================

Version 3.0 drops TYPO3 12, removes the environment and state manager classes
2.4 deprecated and the file upload converter, and ships the form and image
partials the other academic extensions render, with speaking classes and
without a stylesheet. It also brings the console command
:bash:`academic:upgrade:check`, which reports what an upgrade left behind. Work
through the sections in order. Skip a step that does not apply to the
installation.

..  _upgrade-from-2-4-to-3-0-before-you-start:

Before you start
================

Version 3.0 runs on TYPO3 13.4 from 13.4.35 on and on TYPO3 14.3 from 14.3.7
on, with PHP 8.2 or later.

:guilabel:`academic_base` 3.0 needs a new major version of one package:

*   :composer:`fgtclb/environment-state-manager` 2.x, the extension
    :guilabel:`environment_state_manager`. It is declared in
    :file:`composer.json` as `^2.0.1` and in :file:`ext_emconf.php` as
    `2.0.1-2.99.99`. Version 2.4 required 1.x, see
    :ref:`important-environment-state-manager-2`. Composer updates it on its
    own. A classic installation updates it before it activates
    :guilabel:`academic_base` 3.0.

Every other academic extension depends on this one. Upgrade it in the same
deployment as all academic extensions of the installation, and on a classic
installation before them.

These changes have to ship in the same deployment as the update, because the
site fails until they are made:

*   A project that requires :composer:`fgtclb/environment-state-manager` in its
    own :file:`composer.json` raises the requirement to `^2.0.1`, see
    :ref:`upgrade-from-2-4-to-3-0-package`.
*   Code that uses a removed environment and state manager class or the file
    upload converter moves to their replacements, see
    :ref:`upgrade-from-2-4-to-3-0-code`.
*   A subclass or an XCLASS of a plugin controller of an academic extension
    goes, see :ref:`upgrade-from-2-4-to-3-0-extension-points`.

The module paths on this page are those of TYPO3 13, where the upgrade runs.

..  _upgrade-from-2-4-to-3-0-typo3:

Update TYPO3 first
==================

Update TYPO3 to 13.4.35 or later while the site still runs 2.4, which supports
TYPO3 13.4. Version 3.0 no longer installs on TYPO3 12, and Composer and the
Extension Manager refuse it next to an older patch release of TYPO3 13.

Stay on TYPO3 13 until the upgrade is complete when the installation also has
:guilabel:`academic_jobs`, :guilabel:`academic_persons`,
:guilabel:`academic_persons_edit` or :guilabel:`academic_projects`. Their
content element wizards read the column :sql:`tt_content.list_type`, which
TYPO3 14 removes, so they only run on TYPO3 13. Move to TYPO3 14.3.7 or later
once they have run.

See :ref:`breaking-removed-typo3-v12-support` and
:ref:`important-minimum-typo3-versions`.

..  _upgrade-from-2-4-to-3-0-package:

Update the package
==================

..  code-block:: bash

    composer require -W \
        'fgtclb/academic-base':'~3.0.0'

Composer updates :composer:`fgtclb/environment-state-manager` to 2.x with it.
This extension no longer requires the Composer plugin
:composer:`typo3/class-alias-loader`, which loaded the class alias 2.4 kept
for the deprecated namespace. A project that requires
:composer:`fgtclb/environment-state-manager` in its own :file:`composer.json`,
as the upgrade to 2.4 asked of code that uses its classes, raises that
requirement to `^2.0.1` in the same command:

..  code-block:: bash

    composer require -W \
        'fgtclb/environment-state-manager':'^2.0.1' \
        'fgtclb/academic-base':'~3.0.0'

On a classic installation, update :guilabel:`environment_state_manager` to 2.x
from the TER in :guilabel:`Admin Tools > Extensions` first, because
:guilabel:`academic_base` 3.0 cannot be activated without it. Then update
:guilabel:`academic_base` there, and after it every other academic extension of
the installation. Run the commands of this page with
:bash:`typo3/sysext/core/bin/typo3` instead of :bash:`vendor/bin/typo3`.

..  code-block:: bash

    vendor/bin/typo3 cache:flush

..  _upgrade-from-2-4-to-3-0-database:

Update the database
===================

..  code-block:: bash

    vendor/bin/typo3 extension:setup

Or apply the changes in :guilabel:`Admin Tools > Maintenance > Analyze
Database Structure`.

This extension has no table of its own. The comparison applies the changes of
the index definitions TYPO3 13.4.35 and 14.3.7 bring, and the changes of the
other academic extensions, which their upgrade chapters describe.

..  _upgrade-from-2-4-to-3-0-configuration:

Adapt the configuration
=======================

..  _upgrade-from-2-4-to-3-0-upgrade-check:

The upgrade check
-----------------

:guilabel:`academic_base` 3.0 ships the console command
:bash:`academic:upgrade:check`. Without an argument it reads the stored
configuration of the installation and reports what no longer reaches the
academic extensions: a static template that delivers nothing, a page TSconfig
import of a file that is gone, a site that depends on a set a release removed,
an XCLASS of a class that is :php:`final` now. Run it on the staging copy right
after the package update, and work through what it reports before the next
steps:

..  code-block:: bash

    vendor/bin/typo3 academic:upgrade:check

With the key of an academic extension and :bash:`--site` it also compares the
template overrides of a site with the templates that extension ships:

..  code-block:: bash

    vendor/bin/typo3 academic:upgrade:check <extension key> --site=<site identifier>

Each finding, and what to do about it, is described in :ref:`upgrade-check`.

..  _upgrade-from-2-4-to-3-0-wizard-position:

Position of the content element group
-------------------------------------

The group :guilabel:`Academic` of the new content element wizard comes after
the groups of TYPO3 now, and the wizard opens on
:guilabel:`Typical page content` again. A site that wants the group placed as
before sets the position in its page TSconfig:

..  code-block:: typoscript

    mod.wizards.newContentElement.wizardItems.academic.after = special

See :ref:`important-1790839252`, and :ref:`integration-wizard-position` for the
other placements.

..  _upgrade-from-2-4-to-3-0-select-item-labels:

Label overrides of select items on TYPO3 14
-------------------------------------------

On TYPO3 14 the items of a TCA select field that a frontend form of an academic
extension offers read the :typoscript:`_LOCAL_LANG` override of the plugin as
well, not only the one of the extension. An override written for a plugin
reaches those items now. Check that it is meant for them. See
:ref:`important-select-item-labels-read-the-plugin-override-on-typo3-v14`.

..  _upgrade-from-2-4-to-3-0-templates:

Adapt templates and assets
==========================

..  _upgrade-from-2-4-to-3-0-site-stylesheets:

Site stylesheets
----------------

No academic extension ships a stylesheet for its markup in 3.0, and the
templates carry speaking classes with the prefix `ace-` instead of the classes
of the theme they were developed with, see :ref:`styling-shipped`. The site
package styles the markup. How the class system is built, and which classes of
Bootstrap the templates keep, is described in :ref:`styling`. The stylesheet
of the development instances of the academic extensions is a complete example
to start from, see :ref:`styling-project-reference`.

The form field partials and the image partial of this extension are rendered
by the new job form of :guilabel:`academic_jobs` and by every image of a list
item or a page of the other extensions. Move the selectors of the site
stylesheet that styled those fields, their messages and those images to the
new classes, `ace-field`, `ace-control`, `ace-field-errors`, `ace-errors`,
`ace-image`, `ace-figure` and the others the table of
:ref:`breaking-base-speaking-frontend-classes` lists. A Bootstrap theme no
longer shows the message of a field by itself, because it shows
`invalid-feedback` only.

The upgrade chapter of each academic extension names the changes of its own
plugins.

..  _upgrade-from-2-4-to-3-0-partials:

The partials of this extension
------------------------------

The partials below :file:`Resources/Private/Partials/Academic/` are new in 3.0,
so no project overrides them yet. The academic extensions register their path
in the partial root paths of their plugins with the key `-1`. A project that
replaces the partial root paths of an academic plugin completely, instead of
adding a path of its own, lists that path itself:

..  code-block:: typoscript

    plugin.tx_academicpersons.view.partialRootPaths {
        -1 = EXT:academic_base/Resources/Private/Partials/
    }

Without it, the plugin fails on the partial it cannot resolve. See
:ref:`templates-image-override` and :ref:`feature-responsive-image-partial`.

A project that overrides a form field partial passes `invalid` as
`errorClass`, and an override of :file:`Form/Textarea.html` keeps the class
`ace-ckeditor` on a rich text area, which the rich text editor of the job form
starts on. See :ref:`templates-form`.

..  _upgrade-from-2-4-to-3-0-icons:

Frontend icons
--------------

The icons a visitor sees come from a frontend icon registry of this extension
now, which every extension and site package fills through its own
:file:`Configuration/FrontendIcons.php`. The templates of the academic
extensions render them with the icon view helper of this extension, no longer
with :html:`<core:icon>`. A site package that replaced an icon of an academic
extension in its :file:`Configuration/Icons.php` registers the replacement in
:file:`Configuration/FrontendIcons.php`, under the identifier the extension
uses in 3.0, and depends on the extension it replaces the icon of. See
:ref:`feature-1791061865`, :ref:`icons-override` and the upgrade chapter of the
extension that renders the icon.

..  _upgrade-from-2-4-to-3-0-code:

Adapt your code
===============

..  _upgrade-from-2-4-to-3-0-environment-state-manager:

The environment and state manager
---------------------------------

The classes of the namespaces :php:`\FGTCLB\AcademicBase\Environment`,
:php:`\FGTCLB\AcademicBase\Core12\Environment` and
:php:`\FGTCLB\AcademicBase\Core13\Environment` are removed, together with the
class alias of :php:`\FGTCLB\AcademicBase\Environment\StateBuildContext`. Code
that still uses one of them raises a fatal error. Require
:composer:`fgtclb/environment-state-manager` and use its namespace
:php:`\FGTCLB\EnvironmentStateManager`, as the upgrade to 2.4 described.

Version 2.x of :guilabel:`environment_state_manager` ships the namespaces
:php:`\FGTCLB\EnvironmentStateManager\Core13` and
:php:`\FGTCLB\EnvironmentStateManager\Core14`, and no longer
:php:`\FGTCLB\EnvironmentStateManager\Core12`. Code that named a class of
:php:`Core12` names the one of the running TYPO3 version. See
:ref:`breaking-removed-internal-environment-state-manager-subsystem` and
:ref:`important-environment-state-manager-2`.

..  _upgrade-from-2-4-to-3-0-file-upload-converter:

The file upload converter
-------------------------

:php:`\FGTCLB\AcademicBase\Extbase\Property\TypeConverter\FileUploadConverter`
is removed, together with the language file
:file:`EXT:academic_base/Resources/Private/Language/locallang.xlf` and its
German translation, which held its error messages. Code that registered the
converter for an argument of its own uses the native file upload handling of
Extbase, a :php:`FileUploadConfiguration` on the argument. An override of one
of the removed labels can go. See :ref:`breaking-removed-file-upload-converter`.

..  _upgrade-from-2-4-to-3-0-extension-points:

Subclasses and XCLASSes
-----------------------

The chapter :ref:`developers-extension-points` names what a project may build
on in every academic extension. Everything else may change in any release, and
every plugin controller is :php:`final` in 3.0, so a subclass or an XCLASS of a
controller no longer loads. Move such code to the events the chapter lists.
:bash:`academic:upgrade:check` reports every XCLASS of an academic class, as an
error where the class is :php:`final` or gone, and as a notice where it is a
domain model, which a project may extend. See
:ref:`important-extension-point-policy`.

..  _upgrade-from-2-4-to-3-0-plugin-context:

Listeners of the plugin events
------------------------------

*   The events one plugin rendering dispatches share one plugin action
    context now. A listener that checked for the class
    :php:`\FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContext`
    checks for
    :php:`\FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface`
    instead, because the persons plugins hand the view event their own
    context. See :ref:`important-1790607225`.
*   :php:`PluginControllerActionContext::getContentObjectRenderer()` returns
    :php:`null` for a request attribute that is not a content object renderer,
    instead of failing. A :php:`try` around the call can go. See
    :ref:`important-plugin-controller-action-context-content-object`.

..  _upgrade-from-2-4-to-3-0-check:

Check the result
================

*   :bash:`vendor/bin/typo3 academic:upgrade:check` exits with ``0``. A
    notice alone does not fail it. Run it with an extension key and
    :bash:`--site` for every academic extension whose templates the project
    overrides, see :ref:`upgrade-check-site`.
*   On a classic installation, :guilabel:`environment_state_manager` 2.x is
    listed as active in :guilabel:`Admin Tools > Extensions`.
*   The new content element wizard opens on :guilabel:`Typical page content`
    and shows the group :guilabel:`Academic` after the groups of TYPO3, or
    where the page TSconfig of the site places it.
*   A field of the job form of :guilabel:`academic_jobs` that fails
    validation shows its message right below it, see
    :ref:`important-form-partials-mark-rejected-fields`.
*   With :guilabel:`academic_persons` installed, the images of the profile
    cards and of the public profile are rendered as a :html:`<picture>` and
    are styled by the site stylesheet.
*   A search of the site package and of own extensions for
    `AcademicBase\Environment`, `EnvironmentStateManager\Core12` and
    `FileUploadConverter` finds nothing any more.
