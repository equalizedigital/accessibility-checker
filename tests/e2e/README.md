# Playwright specs — Accessibility Checker (drafts)

**Status: DRAFT / uncommitted.** Written from a manual test wave against `release/1.50.0`
in WordPress Playground; the findings and evidence are in
`/opt/data/.hermes/plans/2026-09-23_0226-release-1.50.0-playground-test-wave.md`.

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
| `elementor.spec.js` | The editor↔preview wiring: `after:save` triggers a rescan; autosave does **not**; the listener detaches on `pagehide`; the listener survives a slow editor init; the scan is scoped to the page edit area | Last three are `test.fixme` drafts — see the comments in-file |
| `email-opt-in.spec.js` | Modal opens with dialog semantics and an inert background, and restores on close | Mirrors the PRO-1013 / dialog-semantics changes |
| `admin.spec.js` | Admin pages load clean (no PHP notices/fatals); the Meetup link points at the DFW group | Cheap, catches activation-level regressions |

## Known gaps these drafts still do not close

Keyboard-only navigation and AT announcements in the modal; screenshot/visual diffs for the
style hardening; cross-browser (Chromium only); multisite; the PHP 7.4–8.2 matrix (CI
covers that); Pro plugin interactions; responsive/zoom layouts; translated-locale rendering.

## Not wired into CI (yet)

`src/pageScanner/helpers/scanContext.js` scoping is asserted here, but that behaviour only
exists once the scan-scope PR is in. Until then the scoping spec will fail against
`release/1.50.0` — that is expected, not a flake.
