## Purpose

Defines what the category filter of the study plan renders for the categories
its modules carry, and what it does with a title or a colour that an editor
typed.

## ADDED Requirements

### Requirement: A category title is rendered as text
The category filter SHALL render the title of a category as text, whatever
characters it contains, both as the label of the filter button and in the
label it announces to assistive technology.

#### Scenario: A title that contains markup
- **WHEN** an editor gives a category a title containing `<`, `>` or a quote,
  and a module of a rendered study plan carries that category
- **THEN** the filter button shows that title as the editor typed it, and
  nothing the title names becomes an element of the page

### Requirement: A category colour is a colour or nothing
The category filter SHALL apply the colour of a category only when it is a
colour, and SHALL leave the category without a colour otherwise.

#### Scenario: A colour that is not a colour
- **WHEN** the colour of a category is a value that could close the
  declaration it is written into and open another
- **THEN** the filter button carries no colour, and no declaration but the one
  the template renders

### Requirement: An ordinary category is unaffected
For a category whose title and colour contain nothing of the above, the filter
SHALL render exactly what it rendered before.

#### Scenario: The categories of an ordinary installation
- **WHEN** a study plan renders the filter for categories with ordinary titles
  and hexadecimal colours
- **THEN** the buttons, their labels, their colours and their behaviour are
  unchanged
