..  _breaking-contract-configuration-and-accessible-profile-editing:

================================================================
Breaking: Contract configuration and accessible profile editing
================================================================

Description
===========

The Contract document definition now lives in the top-level :yaml:`contracts`
map. Its ordered form fields are configured below :yaml:`contracts.fields`.
Physical-address, email and phone fields are grouped below
:yaml:`contracts.contactSections.<section>.fields`;
:yaml:`documentSections.contracts` contains only :yaml:`type: contracts`. Site
packages must move existing Contract and ``contractContact`` overrides to that
structure.

Contract contact controls expose the standard ``street-address``,
``postal-code``, ``address-level2``, ``country``, ``email`` and ``tel`` browser
autocomplete purposes from this configuration.

All profile-editing JSON requests now expose a wait cursor and ``aria-busy``
state which is cleared after both success and failure. Dynamic profile,
document, Contract-contact and image controls have explicit label/error
relationships, ``aria-invalid`` state and deterministic focus transfer. A
document draft is discarded when another document is opened; navigation,
blur, change and editor teardown never submit it.

Impact
======

Integrations overriding Contract configuration must adopt the new
:yaml:`contracts.fields` and
:yaml:`contracts.contactSections.<section>.fields` paths. Template overrides
must preserve the documented labels, error IDs, ``aria-*`` bindings and busy
state hooks.

..  index:: Accessibility, AJAX, Contracts, Frontend, NotScanned
