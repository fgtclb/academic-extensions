..  _important-1790774501:

=======================================================
Important: A job list whose request fails shows no jobs
=======================================================

Description
===========

The service that asks the B-ITE API for the postings kept the last response it
received. On a page with two job lists, a second job list whose request failed
therefore showed the postings of the first one, under its own heading and
settings.

The service keeps nothing between two requests any more. A job list whose
request fails shows the message that no jobs are available, and the error is
logged as before.

Impact
======

A job list shows only the postings of its own request. The behaviour is the
same on TYPO3 v12 and v13. Nothing has to be configured.

Affected Installations
======================

Installations with more than one :guilabel:`Bite Jobs List` content element on
a page, and only while the B-ITE API does not answer one of their requests.

..  index:: Frontend, NotScanned, ext:academic_bite_jobs
