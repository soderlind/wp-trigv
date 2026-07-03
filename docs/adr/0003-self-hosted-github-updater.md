# 3. Self-hosted distribution via GitHub updater

Date: 2026-07-03

## Status

Accepted

## Context

The plugin is distributed outside the WordPress.org repository (self-hosted).
Without wp.org, sites get no automatic update mechanism by default. We need
in-dashboard updates and a repeatable release build.

## Decision

Distribute from GitHub and deliver updates with
`soderlind/wordpress-github-updater` (built on
`yahnis-elsts/plugin-update-checker`), initialised from the main plugin file
via `Soderlind\WordPress\GitHubUpdater::init(...)`. Releases are built by the
library's GitHub Actions workflow templates (`on-release-add-zip.yml` for
release-triggered zip builds, `manually-build-zip.yml` for manual builds),
which run `composer install --no-dev` so `vendor/` (Action Scheduler, updater,
update-checker) is packaged into the release zip.

## Consequences

- No WordPress.org review constraints; faster release cadence.
- Updates surface in the normal WP plugin-update UI via GitHub releases.
- Release zips must bundle `vendor/` and `build/`; CI enforces this.
- Optional `TRIGV_GITHUB_TOKEN` constant raises GitHub API rate limits.
