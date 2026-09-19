## 1. Order the four methods

- [x] 1.1 `findAll()` of the address, e-mail, phone number and profile
      information repositories orders by `uid` ascending, with the reason in a
      comment, as `ContractRepository::findAll()` does.
- [x] 1.2 The four test classes compare uids in result order; the
      `sortedUids()` helpers are removed and the docblocks say what the order
      assertion can and cannot prove.
- [x] 1.3 Mutations: drop the ordering — green on SQLite and PostgreSQL
      alike, since none of the tables is workspace aware here; order by
      `sorting` instead — red on SQLite for the three contact repositories,
      green for profile information, whose fixture does not run `sorting`
      against uid order. Measured on v13 and v12.

## 2. Definition of done

- [x] 2.1 `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` green on
      TYPO3 v13 and v12, each after its own `composerUpdate`; the persons
      repository tests on PostgreSQL for both.
- [x] 2.2 `Documentation/Changelog/2.4/` entry in `academic_persons`;
      `docs/` needs no change, rule 3 already covers the case.
- [x] 2.3 Commit message in TYPO3 Core format, `ACE-431` verified.
