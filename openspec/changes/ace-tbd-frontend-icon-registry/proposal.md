## Why

The academic extensions render every frontend icon through the icon registry
and the `core:icon` ViewHelper of TYPO3, a service documented "for all icons
in the TYPO3 backend". The registry loads about 790 core backend icons, the
TCA record icons and the flags on first use, and a site can only replace a
frontend icon by registering it for the backend as well. The extensions need
a registry of their own for what a visitor sees, with the same file format and
the same icon providers, before any of them can move its frontend icons there.
This change provides it and moves nothing yet.

## What Changes

- `academic_base` (`packages/fgtclb/academic-base`) reads
  `Configuration/FrontendIcons.php` from every active extension, in the format
  of `Configuration/Icons.php`, merged in package loading order so a later
  package replaces an icon. Every icon provider of TYPO3 and the `currentColor`
  provider of `academic_base` can be used.
- Extensions contribute icons from code through a new event while the registry
  is built. A `FrontendIcons.php` entry wins over a contributed icon.
- The merged registry is cached with the system caches, under a key that
  follows the set of installed packages, and `cache:warmup` builds it.
- A Fluid ViewHelper `icon` in the namespace of `academic_base` renders an
  icon of this registry only, with the arguments of `core:icon` and markup
  identical to what `core:icon` renders for the same provider and file.
- An identifier the frontend registry does not know renders the
  `default-not-found` drawing of TYPO3, which `academic_base` registers in its
  own `FrontendIcons.php`. There is no fallback to the backend registry.
- A testing helper trait in `packages-dev/testing-helper` asserts frontend
  registrations, for the follow-up changes of the extensions.
- TYPO3 v13 and v14 behave the same. What a provider renders for a file
  differs between the core versions exactly as it does for `core:icon` today.

## Non-goals

- Moving any icon or switching any template. Every extension keeps
  `Configuration/Icons.php` and `core:icon` until its own change.
- Renaming an identifier or slimming the markup.
- A runtime `registerIcon()`, icon deprecations, or a JSON or JavaScript icon
  API (the server side of pull request #618 is superseded by this registry).
- A global Fluid namespace.

## Capabilities

### New Capabilities

- `academic-base/frontend-icons`: registering icons for the frontend, how a
  later package replaces one, how a frontend template renders one, and what
  an unknown identifier renders.

### Modified Capabilities

None.

## Impact

- `academic_base`: new classes below `Classes/Imaging/`, `Classes/Event/`,
  `Classes/EventListener/` and `Classes/ViewHelpers/`, a
  `Configuration/FrontendIcons.php` with the placeholder, the extension points
  page, the configuration chapter and a `Feature` changelog entry.
- `packages-dev/testing-helper`: a fifteenth functional test trait.
- `docs/architecture/icons.md` describes both registries, and the counts in
  `AGENTS.md` and `docs/` that this change moves are updated.
- Integrators and site packages: nothing to do. A site package may already
  ship `Configuration/FrontendIcons.php`, which takes effect once a template
  renders the icon with the new ViewHelper. The follow-up changes of the
  extensions name what moves.
