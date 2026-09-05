..  _breaking-profile-information-years-use-native-dates:

=========================================================
Breaking: Profile-information years use native SQL dates
=========================================================

Description
===========

The :sql:`year`, :sql:`year_start` and :sql:`year_end` columns of
:sql:`tx_academicpersons_domain_model_profile_information` have changed from
integer years to nullable native :sql:`DATE` columns. The corresponding
``ProfileInformation`` properties now use nullable :php:`\DateTime` values.
Only a calendar date is persisted; no time or time zone is stored.

The existing property and column names remain unchanged to preserve the public
relation and template API.

The TCA uses ``type = datetime`` together with ``format = date``,
``dbType = date`` and ``nullable = true``. This date-only configuration is
supported by both TYPO3 13 and TYPO3 14 and renders the backend date picker
without persisting a time value.

A new :sql:`year_only` boolean column and the corresponding
``ProfileInformation::$yearOnly`` property control presentation independently
from storage. When enabled, all configured dates of that record are rendered as
four-digit years while their complete calendar dates remain stored. Existing
records default to the complete-date presentation.

Impact
======

Code that passes integers to ``setYear()``, ``setYearStart()`` or
``setYearEnd()`` must pass :php:`\DateTime` objects instead. Templates that
render these properties directly must format the date explicitly.

Existing integer data cannot be converted losslessly because it contains no
month or day. Before applying the TYPO3 database schema update, export and map
all existing values to intentional complete dates. This extension deliberately
does not assume January 1 or another synthetic date. Apply the schema change
only after that project-specific data migration has completed.

Run TYPO3's database schema analyzer after updating the extension so the new
:sql:`year_only` column is created.

..  index:: Database, Date, Extbase, Migration
