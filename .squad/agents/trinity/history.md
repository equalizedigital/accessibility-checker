# Project Context

- **Owner:** Steve Jones
- **Project:** accessibility-checker — WordPress plugin by Equalize Digital for in-post accessibility scanning (WCAG 2.1 compliance, axe-core based, no external API required, no per-page fees)
- **Stack:** PHP 7.4+, WordPress, React/JavaScript, Webpack, PHPUnit, Jest, ESLint, PHPCS
- **Frontend source:** `src/` (React, JS, CSS) → compiled to `build/`
- **Key frontend areas:** `src/sidebar/` (block editor sidebar), `src/frontendFixes/` (user-facing fixes)
- **i18n:** All JS strings use `wp.i18n` functions
- **WCAG helpers:** `src/sidebar/utils/wcagHelpers.js` exports `shouldDisplayWcagNumber()` — returns false for internal '0.x' pseudo-WCAG numbers (Best Practice, Non-WCAG, Manual Testing)
- **New Window Warning fix:** supports `.anww-no-icon`/`.edac-nww-no-icon` (skip icon) and `.anww-no-tooltip`/`.edac-nww-no-tooltip` (skip tooltip) modifier classes
- **Block editor option:** `edac_show_metabox_in_block_editor` — '0' for new installs, '1' for reactivations
- **Lint commands:** `npm run lint:js`, fixer: `npm run lint:js:fix`
- **Test commands:** `npm run test:jest`
- **Build:** `npm run build` (production), `npm run dev` (watch)
- **Created:** 2026-04-09

## Learnings

<!-- Append new learnings below. Each entry is something lasting about the project. -->
