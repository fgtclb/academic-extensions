.. _important-migrating-profile-information-years-to-dates:

=============================================================
Important: Migrating the timeline years into the date columns
=============================================================

Description
===========

:ref:`breaking-profile-information-timeline-uses-dates` replaces the three
integer year columns of :sql:`tx_academicpersons_domain_model_profile_information`
with date columns. The database analyzer offers to drop the old ones, and an
integer year carries no month and no day, so **there is a decision to take before
anything is applied**: which day of which month a bare year becomes.

This extension does not take that decision for an installation. It ships no
registered upgrade wizard, and nothing in it writes a date it was not given —
a synthetic first of January is a guess, and a guess that reaches a publication
list is worse than an empty field.

For developers and integrators
==============================

What the extension does ship is the part every project would otherwise write
again: the query, the completion rules and the guard that never overwrites a
date that is already there. :php:`\FGTCLB\AcademicPersons\Upgrades\AbstractMigrateProfileInformationDatesUpgradeWizard`
is :php:`abstract readonly`, carries **no** :php:`#[UpgradeWizard]` attribute and
is therefore registered nowhere. A project subclasses it, adds the attribute and
chooses the two completion rules:

..  code-block:: php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MySitePackage\Upgrades;

    use FGTCLB\AcademicBase\Date\DateCompletion;
    use FGTCLB\AcademicBase\Date\DateCompletionEdge;
    use FGTCLB\AcademicPersons\Upgrades\AbstractMigrateProfileInformationDatesUpgradeWizard;
    use TYPO3\CMS\Core\Database\ConnectionPool;
    use TYPO3\CMS\Install\Attribute\UpgradeWizard;

    #[UpgradeWizard('mySitePackage_migrateProfileInformationDates')]
    final readonly class MigrateProfileInformationDatesUpgradeWizard
        extends AbstractMigrateProfileInformationDatesUpgradeWizard
    {
        public function __construct(ConnectionPool $connectionPool)
        {
            parent::__construct(
                $connectionPool,
                // `year` and `year_start`: the first of January.
                new DateCompletion(),
                // `year_end`: the 31st of December.
                new DateCompletion(DateCompletionEdge::LAST, DateCompletionEdge::LAST),
            );
        }
    }

A :php:`readonly` class may only be extended by a :php:`readonly` class, so the
subclass repeats the keyword.

The two :php:`DateCompletion` objects are the four rules an integrator can name,
on their two axes: :php:`DateCompletionEdge::FIRST` or :php:`::LAST` for the
month of the year, and the same for the day of the month. A last day is the last
day of the *resulting* month, so a last day of a last month is the 31st of
December and never the 31st of January.

The wizard reads the old integer columns and writes the new date columns **only
where the target is still empty**, so it never overwrites a date somebody
entered, and it implements :php:`RepeatableInterface` — running it twice changes
nothing the second time. When the old columns are gone from the schema it
reports that there is nothing to do instead of failing.

The order matters. Run the wizard **before** the analyzer drops the old columns,
and take a backup first. An installation whose years are not all worth the same
month and day is better served by an export, a spreadsheet and a one-off import
than by any wizard, and the wizard's own completion rules are then applied to
whatever is left.

Impact
======

An installation that applies the schema change without migrating first keeps
every timeline entry, its title, its link and its text, and loses the years. An
installation that runs a subclass of the shipped wizard first keeps them as the
dates its own rules produce.

..  index:: Backend, Database, ext:academic_persons
