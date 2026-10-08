# Profile editor access

The profile editing plugin of `academic_persons_edit` works on records of one
person: the profile, its contracts, the email addresses, phone numbers and
physical addresses of a contract, and the profile information records. A
logged in frontend user may reach the records of their own profiles and no
others. This page is the rule, where it is enforced, and what a new action has
to do to stay inside it.

## What "own" means

A profile belongs to the frontend users its `frontend_users` relation names.
The column is `l10n_mode: exclude`, so the relation of the **default language
record** decides, for the record and for every translation of it. A relation a
translation carries of its own is not consulted.

A child record belongs to whoever owns the profile above it, found by following
its parent pointer: `contract` for email addresses, phone numbers and physical
addresses, `profile` for contracts and profile information records. Every record
on the way is resolved to its default language record first and its pointer is
read there, because that is the record Extbase loads and overlays for a
translated uid. A pointer only a translation carries is not followed.

A profile may name more than one frontend user, each of them owns it.

Rows are read with the deleted restriction only. A hidden contract of an own
profile is still the owner's, a record below a deleted parent belongs to nobody.
Whether a hidden or scheduled record can be shown is decided elsewhere,
ownership only answers whose it is.

`ProfileOwnershipService::isOwnedByFrontendUser()` implements this.

## Where it is enforced

`AbstractActionController::initializeAction()` checks every argument of an
action whose type is one of the six domain models, before Extbase maps it. Two
reasons for that place rather than the action body:

- **No action can forget it.** A new action that takes a `Profile`, `Contract`,
  `Email`, `PhoneNumber`, `Address` or `ProfileInformation` is covered without
  a line of its own.
- **Mapping already has effects.** The profile image upload stores the file in
  the storage while the `profile` argument is mapped, before the action runs. A
  check in the action would come after the file.

The check reads the argument the way Extbase does, from the request arguments,
where a form body overrides the query string. The cHash covers the query string
only, so the identity a form posts is never trusted on its own.

A refused request is answered with the access denied response of the site,
propagated with a `PropagateResponseException`, exactly like a request without
a logged in user. A record that does not exist is answered the same way, so the
response does not tell which uids exist. The response is propagated rather than
returned: on TYPO3 v12 and v13 the status of an Extbase plugin response is sent
with `header()`, not passed on to the frontend response.

## The profile image upload

The converter stores an uploaded image under the name
`initializeAddImageAction()` configures, and without a name it would keep the
name the client sent, in the folder all profiles share. That method therefore
reads the profile uid exactly like the central check, asserts ownership itself,
and builds the name from the profile row with the deleted restriction only, so a
hidden, scheduled or group restricted profile of the user is named as well. When
no owned profile resolves, the upload is refused with the same access denied
response, never stored under the client's name.

## Actions that take a plain uid

The `toggleVisibility` actions of the email address, phone number and physical
address controllers take the record as an `int`, because a typed argument would
not resolve a record that is already hidden. The central check does not know
which table such an integer refers to, so these actions call
`assertRecordIsOwnedByCurrentFrontendUser()` with the table themselves, first
thing in the action. A new action with an integer uid argument has to do the
same.

## Tests

- `Tests/Functional/Service/ProfileOwnershipServiceTest.php` pins the rule:
  translations, the parent chain, deleted and hidden records.
- `Tests/Functional/Plugins/*OwnershipTest.php`, one per controller, request
  every action for a record of another user and expect the access denied
  response with nothing written, and request the actions that render or write
  for an own record and expect them to work. The foreign record is named in the
  body of a POST request, the forms are the ones the plugin renders for the own
  record. One test names it in the query string of a link with a valid cHash.
- `Tests/Functional/Plugins/ProfileImageUploadTargetNameTest.php` pins the
  upload file name, and the refusal when no owned profile resolves. It calls the
  initialization method directly, because no upload completes in a functional
  test.

## See also

- [Form data transformation](form-data-transformation.md): what happens to a
  submitted value once the request is allowed.
- [Validation settings](validation-settings.md): the per-property flags of the
  same forms.
- [Functional tests](../testing/functional-tests.md): running the plugin tests.
