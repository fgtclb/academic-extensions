..  _feature-bootstrap-profile-page-layout:

=================================================
Feature: Bootstrap based profile page presentation
=================================================

Description
===========

The profile editor now follows the supplied profile-page design using
Bootstrap 5 utilities. The profile name is the main heading above both content
columns. The sticky image column restores the :guilabel:`Profile image` label;
the personal form keeps :guilabel:`Personal data`. The first content row uses a
responsive ``4 / 8`` grid and stacks below the ``lg`` breakpoint.

Read values are plain text instead of button-shaped controls. A borderless
pencil icon rendered through TYPO3's ``core:icon`` ViewHelper opens the
corresponding field editor. Alternating ``bg-body-tertiary`` rows create the
visual rhythm from the reference design. The ``miscellaneous`` field is shown
separately as the :guilabel:`About me` description.

Every Bootstrap button and visible editor control in the shipped Fluid views
now carries ``rounded-0``. This keeps action links, close controls,
confirmation dialogs and editor sections consistently square.

The name fields and both link URL/title pairs use the new
:file:`Field/Group.html` partial. One pencil opens all controls in a group;
clear empties every editable draft value without a request, cancel restores
every field and save submits only changed values through the existing JSON
endpoint. These three actions use the same registered TYPO3 icons as regular
fields. No controller or persistence behavior changes.

The image and profile columns now live in a dedicated, explicitly stretched
Bootstrap row. The about section is a separate sibling below it, so the sticky
image column has the full height of the adjacent profile fields as its
containing block.

The sticky image's runtime ``top`` offset follows the rendered height of the
fixed ``#page-header.navbar-fixed-top`` plus a 10-pixel visual gap. A
``ResizeObserver`` watches the header's ``border-box`` and updates the value
whenever responsive or scroll-dependent navigation styles change its height or
vertical padding, with a window-resize fallback for older browsers. No fixed
header height or additional stylesheet is required.

Gender has no delete, cancel or save buttons. Changing the select persists its
new value immediately. The restored small :guilabel:`Edit all` button sits
beside the profile name in the page header and toggles both single fields and grouped
rows. While active it uses Bootstrap's active state and reads
:guilabel:`Close all`; activating it again collapses all editors while keeping
unsaved browser-side drafts. The former global footer save/cancel area was
removed, so persistence stays explicit on each field. The compact
:guilabel:`Private` switch sits immediately left of the toggle in its own,
valid sibling form. A successful name update also refreshes the main heading
without a page reload.

The image itself is now passive. A small pencil button overlays its upper-right
corner and opens the image editor. Image upload, deletion, validation,
CKEditor and status-toast behavior remain intact.

Impact
======

Projects using the shipped Fluid files receive the new responsive layout
without adding CSS. Overrides should preserve the documented single-field and
group data attributes from :ref:`profile-editing`.

The :guilabel:`Private` control represents the existing
``skipSync`` setting; this change does not introduce a separate visibility
property.

..  index:: Bootstrap, Fluid, Frontend, Inline editing, Responsive design
