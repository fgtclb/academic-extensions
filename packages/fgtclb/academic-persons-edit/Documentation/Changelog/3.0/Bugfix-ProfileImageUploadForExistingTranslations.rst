..  _bugfix-profile-image-upload-for-existing-translations:

===============================================================
Bugfix: Upload profile images when translations already exist
===============================================================

Uploading the first image of a default-language profile now succeeds when a
profile translation already exists. The image reference and its assignment are
submitted to the TYPO3 DataHandler in one data map, allowing the Core language
synchronization to resolve the shared temporary record identifier.

Previously, the reference was persisted first and then submitted to the profile
as a table-prefixed identifier. TYPO3 v14 treated that identifier as a new
localized file reference without a page id. The request failed with an internal
server error although the first persistence step could leave the image assigned.

..  index:: Frontend, FAL, ext:academic_persons_edit
