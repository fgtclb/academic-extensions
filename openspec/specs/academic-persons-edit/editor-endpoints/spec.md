# academic-persons-edit/editor-endpoints Specification

## Purpose
Defines how the endpoints of the frontend profile editor of
`academic_persons_edit` are addressed, which an integrator overriding the
editor template has to follow, and the request guard every endpoint applies,
on TYPO3 v13 and v14.

## Requirements

### Requirement: Each endpoint family is addressed under its own controller name

The editor template SHALL address every endpoint through the profile editing
plugin (`tx_academicpersonsedit_profileediting`) and the page type `1733735`,
with the controller name of its family:

- `Profile` for the profile fields, the synchronisation switch, the
  visibility switch, the image upload and the image removal,
- `Document` for opening, creating, updating, hiding, deleting and sorting a
  document or contract,
- `ContractContact` for opening, creating, updating, hiding, deleting and
  sorting a contact of a contract.

The action names of the endpoints, the payloads they accept and the answers
they give SHALL stay as they are. This SHALL apply on TYPO3 v13 and v14
alike.

#### Scenario: Shipped template

- **WHEN** a person opens a profile in the editor
- **THEN** the addresses the page hands to the editor script name the
  controller `Document` for the six document endpoints, `ContractContact`
  for the six contact endpoints and `Profile` for the others, on TYPO3 v13
  and v14

#### Scenario: Override that takes over the shipped addresses

- **WHEN** a site overrides the editor template and builds the addresses as
  the shipped template does
- **THEN** a person adds, changes, hides, sorts and deletes documents and
  contacts as with the shipped template

#### Scenario: Override that keeps a document endpoint under Profile

- **WHEN** a site overrides the editor template and still addresses the
  creation of a document under the controller name `Profile`
- **THEN** the request fails and no document is stored

### Requirement: Every endpoint refuses a request the editor did not send

Every endpoint of the editor that answers as JSON, and the image upload,
SHALL answer a request without the header `X-Requested-With: XMLHttpRequest`
with status 400 and the error `invalid_request`, and SHALL store nothing of
it, whether the visitor is logged in or not. This SHALL hold for every
endpoint of every family, on TYPO3 v13 and v14.

#### Scenario: Document endpoint without the header

- **WHEN** a logged in person posts the creation of a document without the
  header
- **THEN** the response is 400 `invalid_request` and no document is stored

#### Scenario: Contact endpoint without the header

- **WHEN** a logged in person posts the deletion of a contact without the
  header
- **THEN** the response is 400 `invalid_request` and the contact is kept

#### Scenario: Image upload without the header

- **WHEN** a logged in person uploads a profile image without the header
- **THEN** the response is 400 `invalid_request` and no file and no file
  reference is stored

### Requirement: A JSON endpoint answers an anonymous request with JSON

Every endpoint that answers as JSON SHALL answer a request of a visitor who is
not logged in with status 401 and the error `authentication_required`, as
JSON and not with the access denied page of the site, and SHALL store
nothing. The image upload SHALL keep answering such a request with the access
denied response of the site. This SHALL apply on TYPO3 v13 and v14.

#### Scenario: Anonymous document request

- **WHEN** a visitor who is not logged in posts the sorting of a document
  section with the header the editor sends
- **THEN** the response is 401 `authentication_required` as JSON and the
  order is unchanged

#### Scenario: Anonymous contact request

- **WHEN** a visitor who is not logged in posts the form request of a contact
  with the header the editor sends
- **THEN** the response is 401 `authentication_required` as JSON

### Requirement: A write listener sees the controller name of the endpoint

A listener offered a write of the editor SHALL find, in the plugin action
context it is handed, the controller name of the endpoint family that
received the write, as listed above, and the action name of the endpoint.

#### Scenario: Document creation

- **WHEN** a person adds an entry to the `vita` section
- **THEN** the plugin action context of the write offer names the controller
  `Document` and the action `createDocument`

#### Scenario: Profile field write

- **WHEN** a person changes a profile field
- **THEN** the plugin action context of the write offer names the controller
  `Profile` and the action `update`
