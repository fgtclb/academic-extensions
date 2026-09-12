## ADDED Requirements

### Requirement: Options without a matching program are disabled
The program finder SHALL disable every option that, combined with the values
already selected in the other selects of the same finder, matches no program
in the element's storage. It SHALL enable such an option again as soon as the
selection that excluded it is cleared or changed. This SHALL happen without a
page reload.

#### Scenario: A selection excludes an option of another type
- **WHEN** a visitor selects the degree "Master" and the topic "Robotics" is
  carried only by Bachelor programs
- **THEN** the option "Robotics" is disabled and the page does not reload

#### Scenario: Clearing the selection restores the option
- **WHEN** the visitor resets the degree select to its empty value
- **THEN** the option "Robotics" is selectable again

### Requirement: The finder states how many programs match
The program finder SHALL show the number of programs that match all current
selections and SHALL update it on every change of a selection.

#### Scenario: Count follows the selection
- **WHEN** three programs are in storage, two of them carry the degree
  "Bachelor", and the visitor selects "Bachelor"
- **THEN** the finder states that two programs match

#### Scenario: No JavaScript, no count
- **WHEN** a visitor's browser does not run the finder's script
- **THEN** the submit button shows its plain label without a number

### Requirement: A changed count is announced
The program finder SHALL announce a changed number of matching programs to
assistive technology as a polite status message, without moving the focus
and without interrupting current speech. It SHALL announce nothing when the
page loads.

#### Scenario: A selection changes the count
- **WHEN** a screen reader user selects "Bachelor" and the number of matching
  programs changes from three to two
- **THEN** the screen reader announces that two programs match, while the
  focus stays on the select

#### Scenario: Page load
- **WHEN** the page with the finder has loaded and the visitor has changed
  nothing
- **THEN** no count is announced

### Requirement: A preselected value narrows from the start
The program finder SHALL treat a category the editor preselected as a
selection as soon as the page has loaded.

#### Scenario: Degree preselected by the editor
- **WHEN** the editor preselected "Bachelor" and no Bachelor program carries
  the topic "Robotics"
- **THEN** "Robotics" is disabled once the page has loaded

### Requirement: The finder works without JavaScript
The program finder SHALL remain usable without JavaScript: every option that
at least one program in storage carries is selectable, and submitting opens
the program list with the chosen filter.

#### Scenario: JavaScript is unavailable
- **WHEN** a visitor's browser does not run the finder's script
- **THEN** only options no program carries are disabled, and the submitted
  selection filters the program list as it does without this change
