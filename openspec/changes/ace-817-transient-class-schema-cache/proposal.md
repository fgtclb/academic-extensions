## Why

TYPO3 core writes the Extbase class schemata from the destructor of the
reflection service. When the garbage collector runs that destructor inside
another `serialize()`, the cache entry is written with back references into
the outer payload under a valid signature, and the next read fails with
`unserialize(): Error at offset … AuthenticatedMessageDeserializer.php:71`. In
a functional run it hits whichever test class runs after a certain set of
classes in the same process, so every change of the test set moves it. ACE-725
found it, seven test classes on `main` carry their own copy of the
workaround since, and `ContactsProcessorTest` of `academic_contacts4pages` hit
it next in CI on TYPO3 v14. The defect is reported to TYPO3 core as forge
#110909, with a patch under review. Until a fix reaches both supported core
versions, the repository needs the workaround in one place that no test class
can miss.

## What Changes

- A base class in `packages-dev/testing-helper` between the test case of
  `sbuerk/typo3-site-based-test-trait` and every functional test case of the
  repository. It merges the configuration every test instance starts from
  under what a test class configures, so the test class wins. Today that is
  one setting: the `extbase` cache uses `TransientMemoryBackend`.
- The twelve abstract test cases of the extensions, and `AbstractSeedTestCase`
  and `SnapshotManifestTest` of `packages-dev/dev-site`, extend it.
- A unit test in `packages-dev/monorepo-shared` fails for a functional test
  case that does not extend it.
- Two functional tests in `packages-dev/testing-helper` assert the backend of
  the `extbase` cache of a test instance: transient by default, the one a class
  configures otherwise.
- The seven per-class workarounds are removed, with their docblocks.
- `AGENTS.md` and `docs/` describe the base class, the defect and the check.

## Non-goals

- Fixing the defect in TYPO3 core. It is reported there, and the testing
  framework could make the setting its own default later.
- Production code and configuration. No extension changes what it ships, and
  no installation observes a difference. There is no changelog entry.
- Other defaults for the test instance. The base class is the place for them,
  but this change adds none.
- Branch `2`, where `FrontendPluginRenderingTrait` sets the same backend by
  default since ACE-742.

## Capabilities

### New Capabilities

None. The change is test tooling, never shipped, and sets `skip_specs: true`.

### Modified Capabilities

None.

## Impact

- Test code of all twelve extensions under `packages/fgtclb/`: the abstract
  test case of each, and the seven test classes in `academic-base`,
  `academic-partners`, `academic-persons`, `academic-persons-edit` (two),
  `academic-programs` and `academic-projects` that carried the workaround.
- `packages-dev/testing-helper` (the base class and its two functional tests),
  `packages-dev/dev-site` (two classes), `packages-dev/monorepo-shared` (the
  check).
- TYPO3 v13 and v14 alike. Both read a `TransientBackendInterface` backend
  without `unserialize()`, and `TransientMemoryBackend` exists on both.
- Contributors: a new abstract test case or a direct functional test class
  extends the base class, or the `unit` run fails and says so.
