..  _breaking-vue-inline-profile-views:

===================================================
Breaking: Inline profile views are rendered by Vue
===================================================

Description
===========

The inline profile frontend is now a Vue 3 Composition API application. Its
source lives below
:file:`Resources/Private/TypeScript/frontend/` and the existing frontend build
writes the distributed ES modules below
:file:`Resources/Public/JavaScript/frontend/`.

The former package-local Jest setup below
:file:`Resources/Public/Development/` has been removed; build, lint and
typechecking use the repository-wide :file:`Build/Scripts/runTests.sh` suites.

Structured document workflows and profile-image editing no longer use
Bootstrap modals. Documents use one animated Vue collapse that is teleported
below the selected section heading for new records or directly into the
selected row for preview, edit and delete. The profile overview remains visible
and exactly one document collapse is active. The profile-image editor also
stays in the overview and
uses Vue ``Teleport`` to animate into a bordered full-width target above the
profile grid. Its duplicate image-preview column is hidden while the profile
fields expand to the complete row width below the cropper. Closing the editor
restores and scrolls to the image-preview column. A persisted image is rendered
as a normal preview and cannot be used as crop input; CropperJS starts only for
a newly selected local file. The return scroll runs together with the Vue leave
transition, suppresses native scroll anchoring for that phase and keeps a small
margin above the restored preview. Contracts
retain a separate reactive document kind and currently use the generic
read-only collapse renderer; dedicated contract content can replace that
renderer later without changing the row-action or collapse-target contract.

The editor target keeps a ``2rem`` margin above the viewport after opening.
The leave transition collapses the complete grid row, including its spacing and
border, so removing the teleported editor no longer causes a final layout jump.
The image preview remains hidden and the profile fields retain their full width
until that transition has completed; the responsive profile grid is restored
atomically without an intermediate wrapped row. Its final position is calculated
before the collapse, so the return movement has one continuous direction.

Unused event-listener initializers from the former Vanilla implementation and
the removed modal styling hooks are no longer shipped. The generated public ES
modules remain in place because TYPO3 loads those build artifacts at runtime.

Document drag handles are hidden below Bootstrap's ``md`` breakpoint. The
explicit sort controls remain available on mobile.

Impact
======

Installations using the shipped templates require no configuration change.
Template overrides of the inline profile editor that render the removed
``InlineProfile/Documents/Modal`` or ``InlineProfile/Image/Modal`` partials no
longer work. The former ``InlineProfile/Documents/View`` and
``InlineProfile/Image/View`` partials are now named
``InlineProfile/Documents/Editor`` and ``InlineProfile/Image/Editor``. JavaScript
overrides of the generated files are overwritten by the frontend build.

Affected installations
======================

Installations with Fluid overrides for either removed modal partial or with
custom JavaScript based on their Bootstrap modal hooks.

Developers invoking the removed package-local npm scripts are affected as
well.

Migration
=========

Render ``InlineProfile/Documents/Editor`` and ``InlineProfile/Image/Editor``
inside the ``data-academic-persons-inline-edit`` root. The same root must
contain the profile-specific ``data-ie-image-editor-target`` referenced by the
image partial's Vue ``Teleport``. Each document section must provide a
``data-ie-document-add-collapse-target`` and every record row a
``data-ie-document-item-collapse-target`` for the document partial's dynamic
``Teleport``. Keep the documented endpoint and row data attributes, and move
JavaScript customizations to TypeScript below
:file:`Resources/Private/TypeScript/` so the normal build produces the public
assets.

Use ``buildJs``, ``checkJsBuildClean``, ``lintTypescript`` and ``typecheckJs``
through :file:`Build/Scripts/runTests.sh` instead of installing dependencies
below the extension's public resources.

..  index:: Frontend, Fluid, JavaScript, TypeScript, Vue, ext:academic_persons_edit
