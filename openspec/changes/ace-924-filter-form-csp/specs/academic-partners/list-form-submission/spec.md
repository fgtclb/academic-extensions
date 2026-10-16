## Purpose

Describes how a visitor submits the sorting and filter form of the partner
list and map: by changing a select, also where a site enforces the frontend
Content Security Policy of TYPO3, and with a button without JavaScript.

## ADDED Requirements

### Requirement: A select submits the form under the enforced frontend policy

Changing a sorting or category select of the partner list or map SHALL submit
the form on a site that enforces the frontend Content Security Policy of
TYPO3 and inherits its default policy, on TYPO3 v13 and v14. The policy of
such a site SHALL allow the handler of the selects for event handler
attributes only, and SHALL leave the rules for script elements as they are.

#### Scenario: Visitor changes a select of the partner list

- **WHEN** a site enforces the frontend policy of TYPO3 and a visitor opens
  the partner list
- **THEN** the policy of the response allows the handler every select of the
  form carries for event handler attributes
- **AND** the rules of the policy for script elements allow no handler hash
  and no inline script

#### Scenario: Visitor changes a select of the partner map

- **WHEN** a site enforces the frontend policy of TYPO3 and a visitor opens
  the partner map
- **THEN** the policy of the response allows the handler every select of the
  form carries for event handler attributes

### Requirement: The form can be submitted without JavaScript

The form of the partner list and map SHALL render a submit button that only a
browser without scripting shows, labelled in the language of the visitor. A
submission through it SHALL be answered like any submission of the form, with
the redirect to the filtered list, on TYPO3 v13 and v14.

#### Scenario: Visitor without JavaScript filters the list

- **WHEN** a visitor whose browser runs no script selects the region
  "Americas" and submits the form with its button
- **THEN** the response is a redirect to the partner list filtered by that
  region

#### Scenario: Visitor with JavaScript opens the list

- **WHEN** a visitor whose browser runs scripts opens the partner list or map
- **THEN** the form shows no submit button

### Requirement: A policy that allows inline scripts keeps its fallback

On a site whose frontend policy allows inline scripts in the directive a
browser reads for event handler attributes without a directive of their own,
and that declares no such directive itself, the partner list and map SHALL NOT
add one, on TYPO3 v13 and v14. A directive for event handler attributes that
the site declares itself SHALL stay as the site declares it, with the hash of
the handler of the form added unless that directive allows inline scripts
itself.

#### Scenario: Site allows inline scripts

- **WHEN** a site enforces the frontend policy of TYPO3 and its own policy
  allows inline scripts, and a visitor opens the partner list or map
- **THEN** the policy of the response has no directive for event handler
  attributes, and inline handlers follow the rule of the site for scripts

#### Scenario: Site declares a directive for event handler attributes

- **WHEN** a site that allows inline scripts also extends the directive for
  event handler attributes by a hash of its own, and a visitor opens the
  partner list or map
- **THEN** the directive of the response carries the hash of the site and the
  one of the handler of the form

#### Scenario: Site allows inline scripts for event handler attributes

- **WHEN** a site extends the directive for event handler attributes by
  `'unsafe-inline'`, and a visitor opens the partner list or map
- **THEN** the directive of the response carries `'unsafe-inline'` and no hash,
  so the browser applies it
