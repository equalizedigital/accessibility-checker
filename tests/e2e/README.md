# Playwright specs — Accessibility Checker

Written from a manual test wave against `release/1.50.0` in WordPress Playground.

## Why a separate suite

`tests/jest` covers each changed module in isolation — mocked DOM, mocked `elementor`,
mocked Thickbox. Everything here is the integration layer those tests structurally cannot
reach: a real editor, a real published page, a real modal, a real HTTP request.

## Prerequisites

1. **Node 22 or 24.** `@wp-playground/cli` runs PHP as WASM via `--experimental-wasm-jspi`,
   which is not yet reliable on very new Node majors (Node 26 has been seen failing to
   find/build a matching native binary). If `nvm` is installed, `nvm use 22` before
   running the suite.
2. **Google Chrome installed.** `playwright.config.js` and `global-setup.js` both pin
   `channel: 'chrome'` (the system browser) rather than Playwright's own bundled
   Chromium, so there is nothing to `playwright install` — but Chrome itself has to
   already be on the machine. This also sidesteps Playwright's bundled Chromium not
   supporting some older Linux distros.
3. Build the plugin assets (the Playground mount serves `build/`):
   `npm install && npm run build`
4. Nothing else — `global-setup.js` boots WordPress Playground itself.

## Running

```bash
npm run test:e2e
```

or directly:

```bash
E2E_BASE_URL=http://127.0.0.1:9400 npx playwright test --config tests/e2e/playwright.config.js
```

`global-setup.js` starts the Playground CLI (PHP-WASM in Node) with a blueprint that
installs Elementor and mounts this working copy, waits for it to be ready, and saves a
logged-in storage state to `.auth/admin.json`.

If something already answers on the base URL, that instance is reused and the blueprint is
skipped — useful while iterating, since a boot costs 2–3 minutes. Two things follow from
reusing one:

- An instance booted before `e2e-helpers.php` existed has no `edac-e2e/v1` routes, and
  nothing here can install the mu-plugin into a running instance. Setup fails with that
  message rather than letting `resetOptIn()` fail mid-test: stop the instance and run again.
- The post-type purge test in `settings.spec.js` runs only against an instance this run
  booted itself (the `.auth/disposable-instance` marker), because unchecking a post type
  deletes that post type's saved scan data for the whole site.

## Playground tuning

`E2E_PLAYGROUND_VERSION` (default pinned in `global-setup.js`) and `E2E_PLAYGROUND_WORKERS`
(default 6, the minimum Playground recommends) control the CLI. Playwright itself stays on one
worker: the specs share one WordPress instance and some change site options.

## Timings — read this before "fixing" a timeout

Playground runs PHP as WASM in Node. On a Raspberry Pi a single admin page load takes
**5–20s**, and booting with Elementor takes **2–3 minutes**. The config therefore uses very
generous, env-tunable timeouts, `workers: 1`, and no retries (a retry would just re-pay the
boot cost). If you move this to normal CI hardware, scale `E2E_SLOW` down.

Elementor specifically needs `E2E_EDITOR_TIMEOUT` (default 240s) just to render its editor.

## What each spec covers

| Spec | Covers | Notes |
|------|--------|-------|
| `highlighter.spec.js` | Panel renders with its controls (front end and inside the Elementor preview); description renders title / WCAG link / severity badge; the scan still reports the expected rules on a page with known issues; no `elementor-clickable` on a published page | The last one is the negative case for the clickable-links fix |
| `elementor.spec.js` | The editor↔preview wiring: `after:save` triggers a rescan; autosave does **not**; the listener detaches on `pagehide`; the listener survives a slow editor init; the scan is scoped to the page edit area | Only the scan-scope case runs — the other four are `test.fixme`, see the comments in-file. The save/rescan case is fixme'd for an environment reason, not a product bug: Elementor 4.3.1 (the version `global-setup.js`'s blueprint installs) crashes inside its own `beforeSave` hook under WordPress Playground |
| `email-opt-in.spec.js` | Modal opens with dialog semantics and an inert background, and restores on close | Mirrors the PRO-1013 / dialog-semantics changes. Resets the user's seen-modal meta first through a test-only REST route (`e2e-helpers.php`, installed as a mu-plugin by `global-setup.js`). That route hands back what it found and the spec puts it back in `afterEach` (`restoreOptIn()`), so a reused instance keeps the admin's own opt-in state |
| `gutenberg.spec.js` | Block editor: the meta box Details tab lists the fixture's known rules; the sidebar opens from the header and reports problems; saving triggers a rescan and refetches sidebar data | The Meta Boxes pane starts collapsed (`expandMetaBoxes()` opens it by keyboard — the resize handle blocks a click). The panel falls back to the Summary tab when scan data arrives, so the Details test retries click + assertion together. Every editor open turns off the WordPress onboarding guide (`dismissWelcomeGuide()`): on an instance whose admin never closed it, it opens over the canvas and the editor behind it is inert, so nothing in it can be clicked |
| `settings.spec.js` | A checkbox and a radio persist across a save; "Block Editor Metabox" off and a post type unchecked each remove the meta box from the editor | Each test reads what the option was and restores it in `finally` — the Playground instance is shared. Unchecking a post type purges that post type's saved scan data site-wide, so that test is skipped unless this run booted the instance itself |
| `dismiss.spec.js` | Dismiss an issue from the Details tab with a reason and comment; it persists across a reload; undoing restores it | Posts to the REST `dismiss-issue/<id>` route, not admin-ajax |
| `admin.spec.js` | Admin pages load clean (no PHP notices/fatals); the Meetup link points at the DFW group | Cheap, catches activation-level regressions |

## Known gaps these drafts still do not close

Keyboard-only navigation and AT announcements in the modal; the standalone issues page and global-ignore flows; screenshot/visual diffs for the
style hardening; cross-browser (Chromium only); multisite; the PHP 7.4–8.2 matrix (CI
covers that); Pro plugin interactions; responsive/zoom layouts; translated-locale rendering.

## CI

A manually-triggered workflow (`.github/workflows/e2e-tests.yml`, "E2E Tests" in the
Actions tab → "Run workflow") runs this suite. It is not on the standard PR/push triggers —
Elementor's editor boot alone costs 2–3 minutes, so it stays opt-in rather than on every push.
