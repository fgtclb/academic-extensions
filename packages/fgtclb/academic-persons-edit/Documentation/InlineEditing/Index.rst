..  index:: Inline editing, JSON, Frontend
..  _inline-profile-editing:

======================
Inline profile editing
======================

The :guilabel:`Inline profile editing` content element renders the profile
resolved by the controller for the authenticated frontend user. The shipped
:file:`Resources/Private/Templates/InlineProfile/Index.html` view provides a
responsive form for the profile fields and a read-only overview of the assigned
contracts.

The frontend module
:file:`Resources/Public/JavaScript/frontend/profile.js` saves the form without
reloading the page. It compares the current values with their initial state and
sends only changed properties. An empty string explicitly clears a property;
properties missing from the request remain unchanged.

View data
=========

The controller assigns the following variables to the Fluid template:

..  list-table::
    :header-rows: 1

    *   - Variable
        - Description
    *   - ``{profile}``
        - Profile resolved for the authenticated frontend user, or :php:`null`
          when no editable profile exists.
    *   - ``{genderOptions}``
        - Select options keyed by the allowed non-empty values from the profile
          gender TCA field.
    *   - ``{validations}``
        - Effective ``profile`` validation set. It controls required, disabled,
          read-only and input-type attributes in the view.
    *   - ``{cancelUrl}``
        - Optional URL used by the cancel action.
    *   - ``{data}`` and ``{record}``
        - Current content element data and its record object.

JSON update endpoint
====================

The form URL is generated through ``f:uri.action`` with page type
:php:`1733735`. Requests must use ``POST`` and send the
``Content-Type: application/json`` header.

..  code-block:: json
    :caption: Partial profile update

    {
      "profile": 123,
      "data": {
        "firstName": "Jane",
        "website": "",
        "skipSync": false
      }
    }

The endpoint accepts only a positive integer profile UID and a JSON object in
``data``. Unknown profile properties and gender values not configured in TCA
are rejected. The current frontend user must be authenticated and the requested
profile must be assigned to that user.

..  list-table:: Response status codes
    :header-rows: 1

    *   - Status
        - Error identifier
        - Meaning
    *   - ``200``
        - —
        - The submitted properties were validated and persisted.
    *   - ``400``
        - ``invalid_json`` or ``invalid_payload``
        - The request body is not valid JSON or has an invalid structure.
    *   - ``401``
        - ``authentication_required``
        - No frontend user is authenticated.
    *   - ``403``
        - ``profile_not_editable``
        - The profile is not assigned to the current frontend user.
    *   - ``405``
        - ``method_not_allowed``
        - The endpoint was called with a method other than ``POST``.
    *   - ``422``
        - ``invalid_profile_data`` or ``validation_failed``
        - A property is unknown, a gender is invalid or configured validators
          rejected one or more values.
    *   - ``500``
        - ``internal_server_error``
        - An unexpected error occurred. Details are written to the TYPO3 log,
          not exposed in the JSON response.

Validation errors include an ``errors`` object keyed by property path. The
shipped JavaScript maps these entries back to the corresponding form controls.

..  code-block:: json
    :caption: Validation error response

    {
      "success": false,
      "error": "validation_failed",
      "message": "The submitted profile data is invalid.",
      "errors": {
        "website": [
          "The given subject was not a valid URL."
        ]
      }
    }

Gender values
=============

Allowed gender values are read from the following TCA path:

..  code-block:: php

    $GLOBALS['TCA']['tx_academicpersons_domain_model_profile']
        ['columns']['gender']['config']['items']

Empty values are excluded from the select options but remain valid in an update
request so an existing value can be cleared. The comparison is strict and
case-sensitive.

Customising the view
====================

Override :file:`InlineProfile/Index.html` and
:file:`InlineProfile/Field.html` through the regular template and partial root
paths. Keep these attributes and classes when the shipped JavaScript is reused:

*   ``data-academic-persons-inline-edit`` on the form,
*   ``data-update-url`` and ``data-profile-uid`` on the form,
*   ``data-inline-profile-status`` on the status element,
*   ``academic-persons-inline-edit__field`` on every editable control, and
*   one ``invalid-feedback`` element in the closest ``.mb-3`` or
    ``.form-check`` wrapper.

The JavaScript supports multiple inline editor forms on one page and preserves
the partial-update rule by sending only fields changed since the last successful
save.
