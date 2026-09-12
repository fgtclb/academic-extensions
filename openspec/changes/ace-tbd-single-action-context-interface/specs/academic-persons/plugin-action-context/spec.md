## Purpose

Defines what an event listener can rely on from the plugin action context that
the persons plugins hand to their events, so one listener works for persons
and for the other academic plugins alike.

## ADDED Requirements

### Requirement: The persons context is accepted as the shared academic context
The context carried by a persons plugin event SHALL be usable wherever the
shared plugin action context of the academic extensions is expected, on TYPO3
v13 and v14.

#### Scenario: Listener typed against the shared context
- **WHEN** a listener of the persons list event passes the event's context to
  code that expects the shared academic plugin action context
- **THEN** the call succeeds without a type error

### Requirement: The persons context exposes the content element
The context of a persons plugin event SHALL provide the content element that
holds the plugin, and SHALL provide none when the context was not built for a
content element.

#### Scenario: Persons list plugin
- **WHEN** a listener of the persons list event reads the content element from
  the context
- **THEN** it receives the content element record of that plugin

#### Scenario: Profile page title
- **WHEN** a listener of the profile title placeholder event reads the content
  element from the context while the page title is built
- **THEN** it receives no content element and no error

### Requirement: Listeners typed against the persons context keep working
Listeners written against the persons plugin action context SHALL keep working
unchanged in every 3.x release.

#### Scenario: Existing project listener
- **WHEN** a project listener types the persons context in its signature and
  the persons detail plugin renders
- **THEN** the listener is called with the context as before
