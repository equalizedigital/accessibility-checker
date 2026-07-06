---
name: branch-and-pr
description: Branching, commit, and PR conventions for the equalizedigital/accessibility-checker repo — branch naming from Linear issues, conventional commits, PR template, base branch, and the issue → fix → PR → CI/CodeRabbit workflow.
---

# Branch & PR conventions (accessibility-checker)

## Standard workflow

1. **Issue** — read the Linear (or GitHub) issue, reproduce the problem, branch from `develop`.
2. **Fix** — implement with a regression test where feasible.
3. **Test locally** — run the checks covering the changed surface (see the `tests-and-lint` skill) before pushing.
4. **Open the PR** against `develop` using the template.
5. **Wait for CI to complete** — including the CodeRabbit review check: it goes green once CodeRabbit has posted its review *or* decided it has no input. Don't treat the PR as review-ready, and don't ask for human review, until CI (CodeRabbit included) is green.
6. **Address feedback** — fix CI failures and triage CodeRabbit comments (they're suggestions, not mandates — push back on wrong ones), then re-request review as needed.

## Replying to review comments

When you fix something a review comment raised, reply to that comment with the commit hash that fixed it and a one-line description of what changed to solve it (e.g. "Fixed in `abc1234` — moved the extension check before the video-tag test so `.ogg` audio no longer matches."). Comments you disagree with get a reply with reasoning, not silence. Never resolve a thread without a reply.

## Branches

- Base branch for feature/fix PRs: **`develop`** (not `main`). Releases go through `release/x.y.z` branches.
- Branch names: `william/<linear-id>-<kebab-slug>` for Linear-tracked work, e.g. `william/pro-1168-audio-block-detected-as-video-block…`. For untracked work use `william/no-issue/<slug>`. (Other devs use their own first name; automation uses `automation/…` or `claude/…`.)
- Push to `origin` (equalizedigital) — William has push access; the `pattonwebz` fork remote is rarely needed.

## Commits

Conventional-commit style prefixes: `fix:`, `feat:`, `perf:`, `refactor:`, `chore:`, `docs:`, `test:`. Imperative, lower-case after the prefix, no trailing period.

## PRs

- Open against `develop` with `gh pr create -R equalizedigital/accessibility-checker -B develop`.
- Title convention when Linear-tracked: `PRO-1168: fix: <description>` (Linear ID prefix + conventional prefix).
- The PR template (`.github/PULL_REQUEST_TEMPLATE.md`) has a checklist: link to the main issue, and tests covering changes — actually address both.
- Before opening: run the checks relevant to the change (see the `tests-and-lint` skill), and regenerate `docs/hooks.md` if hooks changed (`composer generate-hooks-docs`).

## Linear

Issues live in Linear (team prefix `PRO` and others). The Linear MCP tools are available for reading/updating issues; attach the PR to the issue or mention the ID in the branch/title so Linear auto-links.
