# Playwright specs — Accessibility Checker

Written from a manual test wave against `release/1.50.0` in WordPress Playground.

## Why a separate suite

`tests/jest` covers each changed module in isolation — mocked DOM, mocked `elementor`,
mocked Thickbox. Everything here is the integration layer those tests structurally cannot
reach: a real editor, a real published page, a real modal, a real HTTP request.

## Prerequisites

1. Build the plugin assets (the Playground mount serves `build/`):
   `npm install && npm run build`
2. `npx playwright install chromium`
3. Nothing else — `global-setup.js` boots WordPress Playground itself.

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
| `email-opt-in.spec.js` | Modal opens with dialog semantics and an inert background, and restores on close | Mirrors the PRO-1013 / dialog-semantics changes |
| `admin.spec.js` | Admin pages load clean (no PHP notices/fatals); the Meetup link points at the DFW group | Cheap, catches activation-level regressions |

## Known gaps these drafts still do not close

Keyboard-only navigation and AT announcements in the modal; screenshot/visual diffs for the
style hardening; cross-browser (Chromium only); multisite; the PHP 7.4–8.2 matrix (CI
covers that); Pro plugin interactions; responsive/zoom layouts; translated-locale rendering.

## CI

A manually-triggered workflow (`.github/workflows/e2e-tests.yml`, "E2E Tests" in the
Actions tab → "Run workflow") runs this suite. It is not on the standard PR/push triggers —
Elementor's editor boot alone costs 2–3 minutes, so it stays opt-in rather than on every push.
