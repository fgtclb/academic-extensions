## Purpose

Defines what the B-ITE job list of `academic_bite_jobs` requests from the
B-ITE API, how an installed extension changes the request and the returned
postings, and what a failed request renders.

## ADDED Requirements

### Requirement: The default request is unchanged
Without an extension changing it, the job list SHALL request the postings of
the configured listing key with channel 0, locale `de`, the first page, no
filter and the configured sorting.

#### Scenario: No extension installed
- **WHEN** the job list renders and no installed extension changes the request
- **THEN** the request sent to B-ITE carries the configured listing key, channel 0, locale `de`, offset 0, an empty filter and the configured sort field and direction

### Requirement: Extensions change the request before it is sent
An installed extension SHALL be able to change every part of the request
before it is sent, knowing the plugin settings and the current request.

#### Scenario: Extension adds a custom field filter
- **WHEN** an installed extension adds a filter on the B-ITE custom field `zuordnung`
- **THEN** the request sent to B-ITE contains that filter

#### Scenario: Extension sets the locale
- **WHEN** an installed extension sets the locale to `en`
- **THEN** the request sent to B-ITE carries the locale `en`

### Requirement: Extensions change the postings before they are rendered
An installed extension SHALL be able to remove, change and add postings, and
add values to a posting, after the response is decoded and before the list
renders, knowing the decoded response and the plugin settings. The configured
limit SHALL apply to the list the extension returns.

#### Scenario: Extension removes a posting
- **WHEN** an installed extension removes one posting of the response
- **THEN** the job list does not render that posting

#### Scenario: Extension adds a value to each posting
- **WHEN** an installed extension adds a relation name to every posting
- **THEN** the job list templates can read the relation name of every posting

#### Scenario: Limit after the extension
- **WHEN** the limit is 2 and an installed extension leaves three postings
- **THEN** the job list renders two postings

### Requirement: A failed request renders no postings
When the request to B-ITE fails, the job list SHALL render no postings and
SHALL log the error, also when another job list on the same page received
postings before.

#### Scenario: Second job list on the page fails
- **WHEN** two job lists render on one page, the first request succeeds and the second fails
- **THEN** the second job list renders no postings
