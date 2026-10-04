## Why

Frontend JavaScript that picks an icon by its identifier at runtime has no way
to get the markup of that icon. The JavaScript icon API of TYPO3 asks a backend
route that answers a logged-in backend user only and serves the backend icon
registry, so it fails on every frontend page. The one option left is a
`<template>` per icon to clone. The frontend icon registry of
`academic_base` lets the server answer without exposing a backend icon.

## What Changes

- `academic_base` (`packages/fgtclb/academic-base`) renders the icons a
  template names into a JSON icon map on the page, a data block the browser
  neither executes nor checks against the Content Security Policy. Each value
  is the inline markup the template would render, so the replacement of an
  icon in a site package reaches the script unchanged.
- The same icons are answered at `_academic/icons.json` below the base of every
  site and site language, up to 32 per request, without a session or a cookie.
  A version token handed out with the JSON map lets the answer be cached for a
  year.
- A JavaScript module of `academic_base` reads the JSON maps of the page first
  and asks the endpoint for the rest, one request for the icons asked for
  together and each icon once per page.
- Only icons of the frontend icon registry are served, and of those neither
  the placeholder nor an icon whose provider does not inline an SVG file.
- The JSON icon map, the endpoint and the module are public API of
  `academic_base`, listed on its extension points page, each announced by a
  Feature entry and changed only in a major release with a Breaking entry.
  The PHP classes behind them are not public API.
- The icon overview page of the development seed demonstrates both paths.
- TYPO3 v13 and v14 behave the same. The inline markup of the core SVG
  provider is sanitised on v14 only, exactly as when a template renders it.

## Non-goals

- Migrating an existing template or script. The `<template>` clones of
  `academic_persons_edit` stay.
- Overlays, icon states, alternative markups and `localStorage`.
- Serving a core icon or a backend record icon, or any other allow-list than
  the frontend icon registry.
- Public PHP classes. An integrator uses the view helper, the endpoint and
  the module, never the classes behind them.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-base/frontend-icons`: adds the JSON icon map, the icon endpoint and
  the JavaScript icon factory, all serving the frontend icon registry.

## Impact

- `academic_base`: a renderer, a ViewHelper and a frontend middleware below
  `Classes/`, `Configuration/RequestMiddlewares.php`, the first TypeScript
  module with `Configuration/JavaScriptModules.php` and its committed build, a
  fixture extension, the manual with its extension points page and three
  `Feature` changelog entries.
- The path `_academic/icons.json` below the base of every site is answered by
  `academic_base`, a page with that slug can no longer be reached there. A web
  server rule that serves `*.json` as static files has to pass it on.
- `packages-dev/dev-site`: a section of the icon overview element and a module
  of its own, no seed change.
- `docs/`: the pages on icons, frontend assets and testing, and the counts
  this change moves.
